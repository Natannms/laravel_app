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

class BoardKanbanMoveAnyColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_move_issue_to_empty_previous_column_persists(): void
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

        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $doing->id,
            'type' => IssueType::Task->value,
            'title' => 'A',
            'position_in_column' => 1024,
        ]);

        $this->assertSame(0, Issue::query()->where('board_column_id', $todo->id)->count());

        Livewire::actingAs($user)
            ->test(BoardKanban::class, ['record' => $board])
            ->call('moveIssue', (string) $issue->id, (string) $todo->id, null);

        $fresh = $issue->fresh();
        $this->assertSame($todo->id, $fresh->board_column_id);
        $this->assertSame(1024, (int) $fresh->position_in_column);
    }

    public function test_move_blocked_issue_to_done_is_rejected_and_does_not_change_column(): void
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

        $done = BoardColumn::query()
            ->where('board_id', $board->id)
            ->where('name', 'Done')
            ->firstOrFail();

        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todo->id,
            'type' => IssueType::Task->value,
            'title' => 'Blocked',
            'blocked' => true,
            'blocked_reason' => 'Waiting',
            'position_in_column' => 1024,
        ]);

        Livewire::actingAs($user)
            ->test(BoardKanban::class, ['record' => $board])
            ->call('moveIssue', (string) $issue->id, (string) $done->id, null);

        $fresh = $issue->fresh();
        $this->assertSame($todo->id, $fresh->board_column_id);
    }
}

