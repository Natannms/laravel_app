<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WipLimitGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_wip_limit_blocks_moving_issue_into_full_column(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $todo = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('name', 'To Do')
            ->firstOrFail();

        $doing = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('name', 'In Progress')
            ->firstOrFail();

        $doing->wip_limit = 1;
        $doing->save();

        Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $doing->id,
            'type' => IssueType::Task->value,
            'title' => 'Already in doing',
        ]);

        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todo->id,
            'type' => IssueType::Task->value,
            'title' => 'Try move',
        ]);

        $this->expectException(ValidationException::class);

        $issue->board_column_id = $doing->id;
        $issue->save();
    }
}

