<?php

namespace App\Filament\Pages\Development;

use App\Filament\Resources\BoardResource;
use App\Models\Board;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;

class Boards extends BaseDevelopmentPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Boards';

    protected static ?string $slug = 'development/boards';

    protected static string $view = 'filament.pages.development.boards';

    public function table(Table $table): Table
    {
        return $table
            ->query(Board::query()->where('project_id', $this->project->id))
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('is_default')->badge()->formatStateUsing(fn (bool $state) => $state ? 'DEFAULT' : '—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('kanban')
                    ->label('Abrir Kanban')
                    ->url(fn (Board $record) => BoardResource::getUrl('kanban', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

