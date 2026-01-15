<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlockedDoneGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_issue_cannot_move_to_done_column(): void
    {
        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $doneColumnId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('is_done', true)
            ->value('id');

        $todoColumnId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('name', 'To Do')
            ->value('id');

        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todoColumnId,
            'type' => IssueType::Task->value,
            'title' => 'Blocked task',
            'blocked' => true,
            'blocked_reason' => 'Waiting',
        ]);

        $this->expectException(ValidationException::class);

        $issue->board_column_id = $doneColumnId;
        $issue->save();
    }
}

