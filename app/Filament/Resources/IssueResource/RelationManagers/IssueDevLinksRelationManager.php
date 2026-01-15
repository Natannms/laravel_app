<?php

namespace App\Filament\Resources\IssueResource\RelationManagers;

use App\Models\Repository;
use App\Models\IssueDevLink;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class IssueDevLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'devLinks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('repository_id')
                    ->label('Repositório')
                    ->options(fn () => Repository::query()
                        ->where('project_id', $this->getOwnerRecord()->project_id)
                        ->orderBy('full_name')
                        ->pluck('full_name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('link_type')->label('Tipo')->required()->maxLength(50)->default('MANUAL'),
                TextInput::make('status')->label('Status')->required()->maxLength(50)->default('OPEN'),
                TextInput::make('branch_name')->label('Branch')->maxLength(255),
                TextInput::make('pr_mr_url')->label('PR/MR URL')->url()->maxLength(255),
                TextInput::make('pr_mr_id')->label('PR/MR ID')->maxLength(100),
                TextInput::make('commit_sha')->label('Commit SHA')->maxLength(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->latest())
            ->columns([
                TextColumn::make('repository.full_name')->label('Repo')->sortable()->searchable(),
                TextColumn::make('branch_name')->label('Branch')->searchable(),
                TextColumn::make('pr_mr_id')->label('PR/MR')->searchable(),
                TextColumn::make('commit_sha')->label('Commit')->limit(10),
                TextColumn::make('status')->label('Status')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_pr')
                    ->label('Abrir PR/MR')
                    ->visible(fn (IssueDevLink $record) => (string) $record->pr_mr_url !== '')
                    ->url(fn (IssueDevLink $record) => $record->pr_mr_url)
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ]);
    }
}
