<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BacklogSprintBoardFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backlog_to_sprint_and_board_move_persist_and_audit(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $todoId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('name', 'To Do')
            ->value('id');

        $doingId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('name', 'In Progress')
            ->value('id');

        $sprint = Sprint::query()->create([
            'project_id' => $project->id,
            'name' => 'Sprint',
            'status' => SprintStatus::Planned->value,
            'position' => 1,
        ]);

        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todoId,
            'type' => IssueType::Task->value,
            'title' => 'Backlog item',
            'sprint_id' => null,
        ]);

        $issue->sprint_id = $sprint->id;
        $issue->save();
        $this->assertSame($sprint->id, $issue->fresh()->sprint_id);

        $issue->board_column_id = $doingId;
        $issue->position_in_column = 10;
        $issue->save();
        $this->assertSame($doingId, $issue->fresh()->board_column_id);

        $this->assertTrue(DB::table('issue_activity')->where('issue_id', $issue->id)->where('action', 'MOVED_SPRINT')->exists());
        $this->assertTrue(DB::table('issue_activity')->where('issue_id', $issue->id)->where('action', 'MOVED_COLUMN')->exists());
    }
}

