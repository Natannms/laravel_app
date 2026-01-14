<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSetupHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_project_creates_default_board_and_columns(): void
    {
        $workspace = Workspace::query()->create([
            'name' => 'Workspace',
            'slug' => 'workspace',
        ]);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Rodrigo Sartory',
        ]);

        $board = Board::query()->where('project_id', $project->id)->where('is_default', true)->firstOrFail();

        $columns = BoardColumn::query()
            ->where('board_id', $board->id)
            ->orderBy('position')
            ->get(['name', 'position', 'is_done']);

        $this->assertCount(4, $columns);

        $this->assertSame('To Do', $columns[0]->name);
        $this->assertSame(1, $columns[0]->position);
        $this->assertFalse((bool) $columns[0]->is_done);

        $this->assertSame('In Progress', $columns[1]->name);
        $this->assertSame(2, $columns[1]->position);
        $this->assertFalse((bool) $columns[1]->is_done);

        $this->assertSame('Review', $columns[2]->name);
        $this->assertSame(3, $columns[2]->position);
        $this->assertFalse((bool) $columns[2]->is_done);

        $this->assertSame('Done', $columns[3]->name);
        $this->assertSame(4, $columns[3]->position);
        $this->assertTrue((bool) $columns[3]->is_done);
    }
}

