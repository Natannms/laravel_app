<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Enums\WorkspaceRole;
use App\Filament\Resources\BoardResource\Pages\BoardKanban;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BoardKanbanDnDTest extends TestCase
{
    use RefreshDatabase;

    public function test_move_issue_reorders_and_persists_positions(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Main',
            'key' => 'PRJ',
        ]);

        $board = Board::query()->where('project_id', $project->id)->where('is_default', true)->firstOrFail();

        $todo = BoardColumn::query()
            ->where('board_id', $board->id)
            ->where('name', 'To Do')
            ->firstOrFail();

        $doing = BoardColumn::query()
            ->where('board_id', $board->id)
            ->where('name', 'In Progress')
            ->firstOrFail();

        $a = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todo->id,
            'type' => IssueType::Task->value,
            'title' => 'A',
            'position_in_column' => 1024,
        ]);

        $b = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todo->id,
            'type' => IssueType::Task->value,
            'title' => 'B',
            'position_in_column' => 2048,
        ]);

        Livewire::actingAs($user)
            ->test(BoardKanban::class, ['record' => $board])
            ->call('moveIssue', (string) $b->id, (string) $doing->id, 0);

        $bFresh = $b->fresh();
        $this->assertSame($doing->id, $bFresh->board_column_id);
        $this->assertSame(1024, (int) $bFresh->position_in_column);

        $aFresh = $a->fresh();
        $this->assertSame($todo->id, $aFresh->board_column_id);
        $this->assertSame(1024, (int) $aFresh->position_in_column);
    }
}

