<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Project;

class ProjectSetupService
{
    public function ensureDefaultBoardAndColumns(Project $project): void
    {
        $board = Board::query()->firstOrCreate(
            ['project_id' => $project->id, 'is_default' => true],
            ['project_id' => $project->id, 'name' => 'Default Board', 'is_default' => true],
        );

        $this->ensureDefaultColumns($board);
    }

    private function ensureDefaultColumns(Board $board): void
    {
        $definitions = [
            ['name' => 'To Do', 'position' => 1, 'is_done' => false],
            ['name' => 'In Progress', 'position' => 2, 'is_done' => false],
            ['name' => 'Review', 'position' => 3, 'is_done' => false],
            ['name' => 'Done', 'position' => 4, 'is_done' => true],
        ];

        foreach ($definitions as $def) {
            $column = BoardColumn::query()->firstOrCreate(
                ['board_id' => $board->id, 'name' => $def['name']],
                [
                    'board_id' => $board->id,
                    'name' => $def['name'],
                    'position' => $def['position'],
                    'is_done' => $def['is_done'],
                ],
            );

            $column->forceFill([
                'position' => $def['position'],
                'is_done' => $def['is_done'],
            ])->save();
        }
    }
}

