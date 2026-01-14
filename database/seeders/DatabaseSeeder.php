<?php

namespace Database\Seeders;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Sprint;
use App\Models\Issue;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $workspace = Workspace::query()->firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Workspace', 'slug' => 'default']
        );

        $this->call(DevOwnerUserSeeder::class);

        $project = Project::query()->firstOrCreate(
            ['key' => 'PRJ'],
            ['workspace_id' => $workspace->id, 'name' => 'Main Project', 'key' => 'PRJ']
        );

        $board = Board::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Default Board'],
            ['project_id' => $project->id, 'name' => 'Default Board', 'is_default' => true]
        );

        $todo = BoardColumn::query()->firstOrCreate(
            ['board_id' => $board->id, 'name' => 'To Do'],
            ['board_id' => $board->id, 'name' => 'To Do', 'position' => 1]
        );
        $doing = BoardColumn::query()->firstOrCreate(
            ['board_id' => $board->id, 'name' => 'In Progress'],
            ['board_id' => $board->id, 'name' => 'In Progress', 'position' => 2]
        );
        $review = BoardColumn::query()->firstOrCreate(
            ['board_id' => $board->id, 'name' => 'Review'],
            ['board_id' => $board->id, 'name' => 'Review', 'position' => 3]
        );
        $done = BoardColumn::query()->firstOrCreate(
            ['board_id' => $board->id, 'name' => 'Done'],
            ['board_id' => $board->id, 'name' => 'Done', 'position' => 4, 'is_done' => true]
        );

        $sprint = Sprint::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Sprint 1'],
            [
                'project_id' => $project->id,
                'name' => 'Sprint 1',
                'goal' => 'Primeira entrega',
                'status' => SprintStatus::Planned->value,
                'position' => 1,
            ]
        );

        $epic = Issue::query()->firstOrCreate(
            ['issue_key' => 'PRJ-1'],
            [
                'project_id' => $project->id,
                'board_column_id' => $todo->id,
                'sprint_id' => $sprint->id,
                'type' => IssueType::Epic->value,
                'title' => 'Plataforma inicial',
                'description' => 'Infra e base do projeto',
                'position_in_column' => 1,
            ]
        );

        $story = Issue::query()->firstOrCreate(
            ['issue_key' => 'PRJ-2'],
            [
                'project_id' => $project->id,
                'board_column_id' => $doing->id,
                'sprint_id' => $sprint->id,
                'epic_id' => $epic->id,
                'type' => IssueType::Story->value,
                'title' => 'Autenticação Sanctum',
                'description' => 'Cadastro, login, logout e /me',
                'position_in_column' => 1,
            ]
        );

        $task = Issue::query()->firstOrCreate(
            ['issue_key' => 'PRJ-3'],
            [
                'project_id' => $project->id,
                'board_column_id' => $done->id,
                'sprint_id' => $sprint->id,
                'parent_id' => $story->id,
                'type' => IssueType::Task->value,
                'title' => 'Modelagem de banco',
                'description' => 'Tabelas principais e relacionamentos',
                'position_in_column' => 1,
            ]
        );
    }
}
