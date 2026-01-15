<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Board;
use App\Models\Project;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\BoardResource;

class ProjectKanban extends Page
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.pages.project-kanban';

    protected ?string $maxContentWidth = 'full';

    public Project $record;

    public function mount(Project $record): void
    {
        $this->record = $record;

        abort_unless(
            Auth::check() && $record->workspace->workspaceUsers()->where('user_id', Auth::id())->exists(),
            403
        );

        $board = Board::query()
            ->where('project_id', $record->id)
            ->where('is_default', true)
            ->firstOrFail();

        redirect()->to(BoardResource::getUrl('kanban', ['record' => $board]));
    }

    public function getBoardProperty(): Board
    {
        return Board::query()
            ->where('project_id', $this->record->id)
            ->where('is_default', true)
            ->firstOrFail();
    }
}
