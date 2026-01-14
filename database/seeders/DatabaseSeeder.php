<?php

namespace Database\Seeders;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Models\Project;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Sprint;
use App\Models\Issue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'secret123']
        );

        $workspace = Workspace::query()->firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Workspace', 'slug' => 'default']
        );

        WorkspaceUser::query()->firstOrCreate([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
        ], [
            'role' => WorkspaceRole::Owner->value,
        ]);

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
        $done = BoardColumn::query()->firstOrCreate(
            ['board_id' => $board->id, 'name' => 'Done'],
            ['board_id' => $board->id, 'name' => 'Done', 'position' => 3, 'is_done' => true]
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
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
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
