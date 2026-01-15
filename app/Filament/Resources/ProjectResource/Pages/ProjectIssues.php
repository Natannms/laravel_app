<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Issue;
use App\Models\Project;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProjectIssues extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.pages.project-issues';

    public Project $record;

    public function mount(Project $record): void
    {
        $this->record = $record;

        abort_unless(
            Auth::check() && $record->workspace->workspaceUsers()->where('user_id', Auth::id())->exists(),
            403
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Issue::query()->where('project_id', $this->record->id))
            ->columns([
                TextColumn::make('issue_key')->label('Key')->sortable()->searchable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('title')->sortable()->searchable()->limit(90),
                TextColumn::make('column.name')->label('Coluna')->sortable(),
                TextColumn::make('sprint.name')->label('Sprint')->sortable(),
                TextColumn::make('blocked')->badge()->formatStateUsing(fn (bool $state) => $state ? 'BLOCKED' : 'OK'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Issue $record) => route('filament.admin.resources.issues.edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

