<?php

namespace App\Filament\Pages;

use App\Enums\IssueType;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class BacklogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static string $view = 'filament.pages.backlog';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBacklogQuery())
            ->columns([
                TextColumn::make('issue_key')->label('Key')->sortable()->searchable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('title')->sortable()->searchable()->limit(70),
                TextColumn::make('project.name')->label('Projeto')->sortable()->searchable(),
                SelectColumn::make('board_column_id')
                    ->label('Coluna')
                    ->options(fn (Issue $record) => BoardColumn::query()
                        ->whereHas('board', fn (Builder $q) => $q->where('project_id', $record->project_id))
                        ->orderBy('position')
                        ->pluck('name', 'id')
                        ->all())
                    ->rules(['required']),
                SelectColumn::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn (Issue $record) => $this->workspaceUserOptionsForIssue($record))
                    ->searchable(),
                TextColumn::make('blocked')->badge()->formatStateUsing(fn (bool $state) => $state ? 'BLOCKED' : 'OK'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Projeto')
                    ->options(fn () => Project::query()
                        ->whereHas('workspace.workspaceUsers', fn (Builder $q) => $q->where('user_id', Auth::id()))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(IssueType::cases())->mapWithKeys(fn (IssueType $t) => [$t->value => $t->value])->all()),
                SelectFilter::make('epic_id')
                    ->label('Epic')
                    ->options(fn () => Issue::query()
                        ->where('type', IssueType::Epic->value)
                        ->whereHas('project.workspace.workspaceUsers', fn (Builder $q) => $q->where('user_id', Auth::id()))
                        ->orderBy('issue_key')
                        ->get(['id', 'issue_key', 'title'])
                        ->mapWithKeys(fn (Issue $issue) => [$issue->id => "{$issue->issue_key} {$issue->title}"])
                        ->all())
                    ->searchable(),
                SelectFilter::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn () => User::query()
                        ->whereHas('workspaceUsers', fn (Builder $q) => $q->where('user_id', Auth::id()))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
                TernaryFilter::make('blocked')->label('Blocked'),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('block')
                    ->label('Bloquear')
                    ->visible(fn (Issue $record) => ! $record->blocked)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('blocked_reason')->label('Motivo')->required()->rows(3),
                        \Filament\Forms\Components\Select::make('blocked_by_issue_id')
                            ->label('Bloqueado por issue (opcional)')
                            ->options(fn (Issue $record) => Issue::query()
                                ->where('project_id', $record->project_id)
                                ->orderBy('issue_key')
                                ->get(['id', 'issue_key', 'title'])
                                ->mapWithKeys(fn (Issue $i) => [$i->id => "{$i->issue_key} {$i->title}"])
                                ->all())
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
                Tables\Actions\Action::make('move_to_sprint')
                    ->label('Mover para sprint')
                    ->form([
                        Select::make('sprint_id')
                            ->label('Sprint')
                            ->options(fn (Issue $record) => Sprint::query()
                                ->where('project_id', $record->project_id)
                                ->where('status', '!=', \App\Enums\SprintStatus::Closed->value)
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Issue $record, array $data) {
                        $record->sprint_id = $data['sprint_id'];
                        $record->save();
                    }),
                Tables\Actions\EditAction::make()
                    ->url(fn (Issue $record) => route('filament.admin.resources.issues.edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function getBacklogQuery(): Builder
    {
        $userId = Auth::id();
        if (! $userId) {
            return Issue::query()->whereRaw('1=0');
        }

        return Issue::query()
            ->with(['project', 'column'])
            ->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class])
            ->whereNull('sprint_id')
            ->whereHas('project.workspace.workspaceUsers', fn (Builder $q) => $q->where('user_id', $userId));
    }

    private function workspaceUserOptionsForIssue(Issue $issue): array
    {
        $workspaceId = $issue->project?->workspace_id;
        if (! $workspaceId) {
            $workspaceId = Project::query()->whereKey($issue->project_id)->value('workspace_id');
        }
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
