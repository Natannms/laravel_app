<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Auth;
use App\Enums\WorkspaceRole;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('workspace_id')
                    ->relationship(
                        name: 'workspace',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'workspaceUsers',
                            fn (Builder $q) => $q->where('user_id', Auth::id())->whereIn('role', [
                                WorkspaceRole::Owner->value,
                                WorkspaceRole::Admin->value,
                            ]),
                        ),
                    )
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (is_string($state) && $state !== '') {
                            $set('key', Project::previewKeyFromName($state));
                        }
                    }),
                TextInput::make('key')
                    ->label('Key (auto)')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Gerado automaticamente a partir do nome. O número pode variar se já existir.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workspace.name')->label('Workspace')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('key')->sortable()->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn (Project $record) => static::getUrl('kanban', ['record' => $record]))
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
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
            ->whereHas('workspace.workspaceUsers', fn (Builder $query) => $query->where('user_id', $userId));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'kanban' => Pages\ProjectKanban::route('/{record}/kanban'),
            'issues' => Pages\ProjectIssues::route('/{record}/issues'),
        ];
    }
}
