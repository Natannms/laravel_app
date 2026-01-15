<?php

namespace App\Filament\Resources\WorkspaceResource\RelationManagers;

use App\Models\WorkspaceUser;
use App\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WorkspaceGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $title = 'Groups';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label('Nome')->required()->maxLength(255),
                Textarea::make('description')->label('Descrição')->rows(3)->columnSpanFull(),
                Select::make('members')
                    ->label('Pessoas')
                    ->relationship(
                        name: 'members',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn (Builder $query) => $query->where('workspace_id', $this->getOwnerRecord()->id)->with('user'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (WorkspaceUser $record) => (string) ($record->user?->name ?? $record->user?->email ?? 'Usuário'))
                    ->saveRelationshipsUsing(function (Select $component, Model $record, $state): void {
                        unset($component);
                        $before = $record->members()->pluck('workspace_users.id')->map(fn ($v) => (string) $v)->all();
                        $record->members()->sync($state ?? []);
                        $after = $record->members()->pluck('workspace_users.id')->map(fn ($v) => (string) $v)->all();

                        $actorId = Auth::id();
                        if ($actorId) {
                            AuditLog::query()->create([
                                'workspace_id' => $this->getOwnerRecord()->id,
                                'actor_user_id' => (string) $actorId,
                                'action' => 'workspace_group.members_synced',
                                'target_type' => 'workspace_group',
                                'target_id' => (string) $record->id,
                                'before' => ['members' => $before],
                                'after' => ['members' => $after],
                            ]);
                        }
                    })
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('description')->label('Descrição')->limit(60),
                TextColumn::make('members_count')->counts('members')->label('Pessoas')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
