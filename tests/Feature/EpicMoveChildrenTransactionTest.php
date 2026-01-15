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

class EpicMoveChildrenTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_epic_to_sprint_moves_children_and_logs(): void
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

        $sprint = Sprint::query()->create([
            'project_id' => $project->id,
            'name' => 'Sprint',
            'status' => SprintStatus::Planned->value,
            'position' => 1,
        ]);

        $epic = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todoId,
            'type' => IssueType::Epic->value,
            'title' => 'Epic',
        ]);

        $child = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todoId,
            'type' => IssueType::Story->value,
            'title' => 'Child',
            'epic_id' => $epic->id,
        ]);

        $epic->sprint_id = $sprint->id;
        $epic->save();

        $this->assertSame($sprint->id, $child->fresh()->sprint_id);

        $this->assertTrue(DB::table('issue_activity')
            ->where('issue_id', $epic->id)
            ->where('action', 'EPIC_MOVED_WITH_CHILDREN')
            ->exists());
    }
}

