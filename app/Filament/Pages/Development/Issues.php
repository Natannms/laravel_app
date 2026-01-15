<?php

namespace App\Filament\Pages\Development;

use App\Models\Issue;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;

class Issues extends BaseDevelopmentPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Issues';

    protected static ?string $slug = 'development/issues';

    protected static string $view = 'filament.pages.development.issues';

    public function table(Table $table): Table
    {
        return $table
            ->query(Issue::query()->where('project_id', $this->project->id)->with(['column', 'sprint']))
            ->columns([
                TextColumn::make('issue_key')->label('Key')->sortable()->searchable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('title')->sortable()->searchable()->limit(90),
                TextColumn::make('column.name')->label('Coluna')->sortable(),
                TextColumn::make('sprint.name')->label('Sprint')->sortable(),
                TextColumn::make('blocked')->badge()->formatStateUsing(fn (bool $state) => $state ? 'BLOCKED' : 'OK'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Issue $record) => route('filament.admin.resources.issues.edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

