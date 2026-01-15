<?php

namespace App\Filament\Resources;

use App\Enums\WorkspaceRole;
use App\Filament\Resources\BoardResource\Pages;
use App\Filament\Resources\BoardResource\RelationManagers;
use App\Models\Board;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class BoardResource extends Resource
{
    protected static ?string $model = Board::class;

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
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
                    ->required(),
                TextInput::make('name')->required()->maxLength(255),
                Toggle::make('is_default')->label('Default'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('is_default')->badge()->formatStateUsing(fn (bool $state) => $state ? 'DEFAULT' : '—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
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
            ->whereHas('project.workspace.workspaceUsers', fn (Builder $query) => $query->where('user_id', $userId));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BoardColumnsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoards::route('/'),
            'create' => Pages\CreateBoard::route('/create'),
            'edit' => Pages\EditBoard::route('/{record}/edit'),
            'kanban' => Pages\BoardKanban::route('/{record}/kanban'),
        ];
    }
}
