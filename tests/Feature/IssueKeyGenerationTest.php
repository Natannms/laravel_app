<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueKeyGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_key_is_generated_incrementally_per_project(): void
    {
        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Main',
            'key' => 'PRJ',
        ]);

        $columnId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->orderBy('position')
            ->value('id');

        $issue1 = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Story->value,
            'title' => 'First',
        ]);
        $this->assertSame('PRJ-1', $issue1->issue_key);

        $issue2 = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Task->value,
            'title' => 'Second',
        ]);
        $this->assertSame('PRJ-2', $issue2->issue_key);

        $project2 = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Other',
            'key' => 'PRJ2',
        ]);
        $columnId2 = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project2->id))
            ->orderBy('position')
            ->value('id');

        $issue3 = Issue::query()->create([
            'project_id' => $project2->id,
            'board_column_id' => $columnId2,
            'type' => IssueType::Story->value,
            'title' => 'Third',
        ]);
        $this->assertSame('PRJ2-1', $issue3->issue_key);
    }
}

