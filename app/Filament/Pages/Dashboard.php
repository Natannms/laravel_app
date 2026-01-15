<?php

namespace App\Filament\Pages;

use App\Filament\Resources\BoardResource;
use App\Models\Board;
use App\Models\Project;
use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Dashboard extends FilamentDashboard implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.dashboard';

    public function mount(): void
    {
        session()->forget('current_project_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->projectsQuery())
            ->columns([
                TextColumn::make('workspace.name')->label('Workspace')->sortable()->searchable(),
                TextColumn::make('name')->label('Projeto')->sortable()->searchable(),
                TextColumn::make('key')->label('Key')->sortable()->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Project $record) => $this->kanbanUrlForProject($record))
            ->defaultSort('created_at', 'desc');
    }

    private function projectsQuery(): Builder
    {
        $userId = Auth::id();
        if (! $userId) {
            return Project::query()->whereRaw('1=0');
        }

        return Project::query()
            ->whereHas('workspace.workspaceUsers', fn (Builder $q) => $q->where('user_id', $userId))
            ->with('workspace');
    }

    private function kanbanUrlForProject(Project $project): ?string
    {
        $boardId = Board::query()
            ->where('project_id', $project->id)
            ->where('is_default', true)
            ->value('id');

        if (! $boardId) {
            return null;
        }

        return BoardResource::getUrl('kanban', ['record' => $boardId]);
    }
}
