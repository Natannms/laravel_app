<?php

namespace App\Filament\Resources\WorkspaceResource\RelationManagers;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceUser;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WorkspaceUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'workspaceUsers';

    public function canCreate(): bool
    {
        return $this->canManageMembers();
    }

    public function canEdit($record): bool
    {
        return $this->canManageMembers();
    }

    public function canDelete($record): bool
    {
        return $this->canManageMembers();
    }

    protected function canManageMembers(): bool
    {
        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        $workspaceId = $this->getOwnerRecord()->id;
        $membership = WorkspaceUser::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();

        if (! $membership) {
            return false;
        }

        $role = $membership->role instanceof WorkspaceRole ? $membership->role->value : (string) $membership->role;

        return in_array($role, [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value], true);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Usuário')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'email',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $workspaceId = $this->getOwnerRecord()->id;
                            $excludedIds = WorkspaceUser::withTrashed()
                                ->where('workspace_id', $workspaceId)
                                ->pluck('user_id');

                            return $query->whereNotIn('id', $excludedIds);
                        },
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} ({$record->email})")
                    ->searchable()
                    ->preload()
                    ->disabled(fn (?WorkspaceUser $record) => filled($record))
                    ->required(),
                Select::make('role')
                    ->label('Role')
                    ->options(collect(WorkspaceRole::cases())->mapWithKeys(fn (WorkspaceRole $role) => [$role->value => $role->value])->all())
                    ->default(WorkspaceRole::Dev->value)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([
                TextColumn::make('user.name')->label('Usuário')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('role')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->visible(fn () => $this->canManageMembers()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => $this->canManageMembers()),
                Tables\Actions\DeleteAction::make()->visible(fn () => $this->canManageMembers()),
                Tables\Actions\RestoreAction::make()->visible(fn () => $this->canManageMembers()),
                Tables\Actions\ForceDeleteAction::make()->visible(fn () => $this->canManageMembers()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(fn () => $this->canManageMembers()),
                    Tables\Actions\RestoreBulkAction::make()->visible(fn () => $this->canManageMembers()),
                    Tables\Actions\ForceDeleteBulkAction::make()->visible(fn () => $this->canManageMembers()),
                ]),
            ]);
    }
}
