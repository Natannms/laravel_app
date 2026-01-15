<?php

namespace App\Filament\Pages\Development;

use App\Models\Sprint;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;

class Sprints extends BaseDevelopmentPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Sprints';

    protected static ?string $slug = 'development/sprints';

    protected static string $view = 'filament.pages.development.sprints';

    public function table(Table $table): Table
    {
        return $table
            ->query(Sprint::query()->where('project_id', $this->project->id))
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('status')->sortable(),
                TextColumn::make('position')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Sprint $record) => route('filament.admin.resources.sprints.edit', ['record' => $record])),
            ])
            ->defaultSort('position');
    }
}

