<?php

namespace App\Filament\Resources\WorkspaceResource\RelationManagers;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceUser;
use App\Services\PermissionResolver;
use App\Services\WorkspaceInvitationService;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
        return $this->canInviteMembers();
    }

    public function canEdit($record): bool
    {
        return $this->canUpdateMemberRole();
    }

    public function canDelete($record): bool
    {
        return $this->canRemoveMembers();
    }

    protected function canInviteMembers(): bool
    {
        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        $workspaceId = $this->getOwnerRecord()->id;
        return app(PermissionResolver::class)->has(Auth::user(), 'workspace_members.invite', (string) $workspaceId, null);
    }

    protected function canUpdateMemberRole(): bool
    {
        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        $workspaceId = $this->getOwnerRecord()->id;
        return app(PermissionResolver::class)->has(Auth::user(), 'workspace_members.update_role', (string) $workspaceId, null);
    }

    protected function canRemoveMembers(): bool
    {
        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        $workspaceId = $this->getOwnerRecord()->id;
        return app(PermissionResolver::class)->has(Auth::user(), 'workspace_members.remove', (string) $workspaceId, null);
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
                Tables\Actions\Action::make('invite')
                    ->label('Convidar')
                    ->visible(fn () => $this->canInviteMembers())
                    ->form([
                        TextInput::make('invite_url')
                            ->label('Link do convite')
                            ->disabled()
                            ->dehydrated(false)
                            ->extraInputAttributes([
                                'x-init' => 'try { navigator.clipboard.writeText(\\$el.value) } catch (e) {}',
                            ]),
                    ])
                    ->mountUsing(function (\Filament\Forms\Form $form) {
                        $workspaceId = (string) $this->getOwnerRecord()->id;
                        $service = app(WorkspaceInvitationService::class);

                        [, $token] = $service->createInvitation(
                            workspaceId: $workspaceId,
                            createdByUserId: Auth::id() ? (string) Auth::id() : null,
                        );

                        $url = url('/invite/' . $token);

                        Notification::make()
                            ->title('Link copiado')
                            ->body($url)
                            ->success()
                            ->send();

                        $form->fill([
                            'invite_url' => $url,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Fechar')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => $this->canUpdateMemberRole()),
                Tables\Actions\DeleteAction::make()->visible(fn () => $this->canRemoveMembers()),
                Tables\Actions\RestoreAction::make()->visible(fn () => $this->canRemoveMembers()),
                Tables\Actions\ForceDeleteAction::make()->visible(fn () => $this->canRemoveMembers()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(fn () => $this->canRemoveMembers()),
                    Tables\Actions\RestoreBulkAction::make()->visible(fn () => $this->canRemoveMembers()),
                    Tables\Actions\ForceDeleteBulkAction::make()->visible(fn () => $this->canRemoveMembers()),
                ]),
            ]);
    }
}
