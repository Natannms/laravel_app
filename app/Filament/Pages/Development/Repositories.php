<?php

namespace App\Filament\Pages\Development;

use App\Models\Repository;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;

class Repositories extends BaseDevelopmentPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Repositories';

    protected static ?string $slug = 'development/repositories';

    protected static string $view = 'filament.pages.development.repositories';

    public function table(Table $table): Table
    {
        return $table
            ->query(Repository::query()->where('project_id', $this->project->id))
            ->columns([
                TextColumn::make('provider')->sortable(),
                TextColumn::make('full_name')->sortable()->searchable(),
                TextColumn::make('is_private')->badge()->formatStateUsing(fn (bool $state) => $state ? 'PRIVATE' : 'PUBLIC'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Repository $record) => route('filament.admin.resources.repositories.edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

