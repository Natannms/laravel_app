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

class IssueHierarchyValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtask_requires_parent(): void
    {
        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $columnId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->orderBy('position')
            ->value('id');

        $this->expectException(ValidationException::class);

        Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Subtask->value,
            'title' => 'Subtask',
        ]);
    }

    public function test_only_subtask_can_have_parent(): void
    {
        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $columnId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->orderBy('position')
            ->value('id');

        $parent = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Story->value,
            'title' => 'Parent',
        ]);

        $this->expectException(ValidationException::class);

        Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Task->value,
            'title' => 'Invalid',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_epic_id_must_reference_epic_in_same_project(): void
    {
        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $columnId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->orderBy('position')
            ->value('id');

        $notEpic = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Story->value,
            'title' => 'Not Epic',
        ]);

        $this->expectException(ValidationException::class);

        Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $columnId,
            'type' => IssueType::Task->value,
            'title' => 'Child',
            'epic_id' => $notEpic->id,
        ]);
    }
}

