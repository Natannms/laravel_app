<?php

namespace App\Filament\Resources;

use App\Enums\IssueType;
use App\Filament\Resources\IssueResource\Pages;
use App\Filament\Resources\IssueResource\RelationManagers;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use App\Enums\WorkspaceRole;
use App\Enums\SprintStatus;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('project_id')
                    ->relationship(
                        name: 'project',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'workspace.workspaceUsers',
                            fn (Builder $q) => $q->where('user_id', Auth::id())->whereIn('role', [
                                WorkspaceRole::Owner->value,
                                WorkspaceRole::Admin->value,
                                WorkspaceRole::Manager->value,
                                WorkspaceRole::Dev->value,
                            ]),
                        ),
                    )
                    ->required()
                    ->live(),
                TextInput::make('issue_key')
                    ->label('Issue Key (auto)')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('type')
                    ->options(collect(IssueType::cases())->mapWithKeys(fn (IssueType $t) => [$t->value => $t->value])->all())
                    ->required()
                    ->live(),
                TextInput::make('title')->required()->maxLength(255),
                Textarea::make('description')->rows(6)->columnSpanFull(),
                Select::make('board_column_id')
                    ->label('Status')
                    ->options(fn (Get $get): array => self::boardColumnOptions($get('project_id')))
                    ->searchable()
                    ->required(),
                Select::make('sprint_id')
                    ->label('Sprint')
                    ->options(fn (Get $get): array => self::sprintOptions($get('project_id')))
                    ->searchable(),
                Select::make('epic_id')
                    ->label('Epic')
                    ->options(fn (Get $get): array => self::epicOptions($get('project_id')))
                    ->searchable()
                    ->visible(fn (Get $get) => (string) $get('type') !== IssueType::Epic->value),
                Select::make('parent_id')
                    ->label('Parent (apenas SUBTASK)')
                    ->options(fn (Get $get): array => self::parentOptions($get('project_id')))
                    ->searchable()
                    ->visible(fn (Get $get) => (string) $get('type') === IssueType::Subtask->value)
                    ->required(fn (Get $get) => (string) $get('type') === IssueType::Subtask->value),
                Select::make('assignees')
                    ->label('Assignees')
                    ->relationship(
                        name: 'assignees',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, Get $get) {
                            $projectId = (string) ($get('project_id') ?? '');
                            if ($projectId === '') {
                                $query->whereRaw('1=0');
                                return;
                            }

                            $workspaceId = Project::query()->whereKey($projectId)->value('workspace_id');
                            if (! $workspaceId) {
                                $query->whereRaw('1=0');
                                return;
                            }

                            $query->whereHas(
                                'workspaceUsers',
                                fn (Builder $q) => $q->where('workspace_id', $workspaceId)->whereIn('role', [
                                    WorkspaceRole::Owner->value,
                                    WorkspaceRole::Admin->value,
                                    WorkspaceRole::Manager->value,
                                    WorkspaceRole::Dev->value,
                                ]),
                            );
                        },
                    )
                    ->multiple()
                    ->searchable(),
                Select::make('reporter_id')
                    ->label('Reporter')
                    ->options(fn (Get $get): array => self::workspaceUserOptions($get('project_id')))
                    ->searchable(),
                TextInput::make('estimate_hours')->numeric()->minValue(0),
                Toggle::make('blocked')->live(),
                Textarea::make('blocked_reason')
                    ->label('Motivo do bloqueio')
                    ->rows(3)
                    ->visible(fn (Get $get) => (bool) $get('blocked')),
                Select::make('blocked_by_issue_id')
                    ->label('Bloqueado por issue')
                    ->options(fn (Get $get): array => self::parentOptions($get('project_id')))
                    ->searchable()
                    ->visible(fn (Get $get) => (bool) $get('blocked')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('issue_key')->label('Key')->sortable()->searchable(),
                TextColumn::make('project.name')->sortable()->searchable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('title')->sortable()->searchable()->limit(50),
                TextColumn::make('column.name')->label('Status')->sortable(),
                TextColumn::make('sprint.name')->label('Sprint')->sortable(),
                TextColumn::make('blocked')->badge()->formatStateUsing(fn (bool $state) => $state ? 'BLOCKED' : 'OK'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('block')
                    ->label('Bloquear')
                    ->visible(fn (Issue $record) => ! $record->blocked)
                    ->form([
                        Textarea::make('blocked_reason')->label('Motivo')->required()->rows(3),
                        Select::make('blocked_by_issue_id')
                            ->label('Bloqueado por issue (opcional)')
                            ->options(fn (Issue $record) => self::parentOptions($record->project_id))
                            ->searchable(),
                    ])
                    ->action(function (Issue $record, array $data) {
                        $record->blocked = true;
                        $record->blocked_reason = $data['blocked_reason'] ?? null;
                        $record->blocked_by_issue_id = $data['blocked_by_issue_id'] ?? null;
                        $record->save();

                        Notification::make()->title('Issue bloqueada')->success()->send();
                    }),
                Tables\Actions\Action::make('unblock')
                    ->label('Desbloquear')
                    ->visible(fn (Issue $record) => (bool) $record->blocked)
                    ->action(function (Issue $record) {
                        $record->blocked = false;
                        $record->blocked_reason = null;
                        $record->blocked_by_issue_id = null;
                        $record->save();

                        Notification::make()->title('Issue desbloqueada')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = Auth::id();
        if (! $userId) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->whereHas('project.workspace.workspaceUsers', fn (Builder $query) => $query->where('user_id', $userId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\IssueCommentsRelationManager::class,
            RelationManagers\IssueAttachmentsRelationManager::class,
            RelationManagers\IssueDevLinksRelationManager::class,
            RelationManagers\IssueActivityRelationManager::class,
        ];
    }

    private static function boardColumnOptions(?string $projectId): array
    {
        if (! $projectId) {
            return [];
        }

        return BoardColumn::query()
            ->whereHas('board', fn (Builder $q) => $q->where('project_id', $projectId))
            ->orderBy('position')
            ->pluck('name', 'id')
            ->all();
    }

    private static function sprintOptions(?string $projectId): array
    {
        if (! $projectId) {
            return [];
        }

        return Sprint::query()
            ->where('project_id', $projectId)
            ->where('status', '!=', SprintStatus::Closed->value)
            ->orderBy('position')
            ->pluck('name', 'id')
            ->all();
    }

    private static function epicOptions(?string $projectId): array
    {
        if (! $projectId) {
            return [];
        }

        return Issue::query()
            ->where('project_id', $projectId)
            ->where('type', IssueType::Epic->value)
            ->orderBy('issue_key')
            ->pluck('title', 'id')
            ->all();
    }

    private static function parentOptions(?string $projectId): array
    {
        if (! $projectId) {
            return [];
        }

        return Issue::query()
            ->where('project_id', $projectId)
            ->orderBy('issue_key')
            ->get(['id', 'issue_key', 'title'])
            ->mapWithKeys(fn (Issue $issue) => [$issue->id => "{$issue->issue_key} {$issue->title}"])
            ->all();
    }

    private static function workspaceUserOptions(?string $projectId): array
    {
        if (! $projectId) {
            return [];
        }

        $workspaceId = Project::query()->whereKey($projectId)->value('workspace_id');
        if (! $workspaceId) {
            return [];
        }

        return User::query()
            ->whereHas('workspaceUsers', fn (Builder $q) => $q->where('workspace_id', $workspaceId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
