<?php

namespace Database\Seeders;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\IssueAttachment;
use App\Models\IssueComment;
use App\Models\IssueDevLink;
use App\Models\Project;
use App\Models\Repository;
use App\Models\Sprint;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectSetupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DevDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (! filter_var(env('DEV_SEED_DEMO_ENABLED', true), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $email = (string) env('DEV_SEED_OWNER_EMAIL', '');
        if ($email === '') {
            return;
        }

        $workspaceSlug = (string) env('DEV_SEED_OWNER_WORKSPACE_SLUG', 'default');
        $workspaceName = (string) env('DEV_SEED_OWNER_WORKSPACE_NAME', 'Default Workspace');

        $workspace = Workspace::query()->withTrashed()->firstOrCreate(
            ['slug' => $workspaceSlug],
            ['name' => $workspaceName, 'slug' => $workspaceSlug],
        );

        $user = User::query()->withTrashed()->where('email', $email)->first();
        if (! $user) {
            return;
        }

        $faker = app(\Faker\Generator::class);

        $projectName = (string) env('DEV_SEED_DEMO_PROJECT_NAME', 'Ghork Demo');
        $projectKey = (string) env('DEV_SEED_DEMO_PROJECT_KEY', '');

        $project = Project::query()->withTrashed()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => $projectName],
            [
                'workspace_id' => $workspace->id,
                'name' => $projectName,
                'key' => $projectKey !== '' ? $projectKey : Project::generateUniqueKeyForWorkspace((string) $workspace->id, $projectName),
            ],
        );

        app(ProjectSetupService::class)->ensureDefaultBoardAndColumns($project);

        $board = Board::query()->withTrashed()
            ->where('project_id', $project->id)
            ->where('is_default', true)
            ->firstOrCreate(
                ['project_id' => $project->id, 'is_default' => true],
                ['project_id' => $project->id, 'name' => 'Default Board', 'is_default' => true],
            );

        $columns = BoardColumn::query()->withTrashed()
            ->where('board_id', $board->id)
            ->get()
            ->keyBy('name');

        $todoId = $columns->get('To Do')?->id;
        $doingId = $columns->get('In Progress')?->id;
        $reviewId = $columns->get('Review')?->id;
        $doneId = $columns->get('Done')?->id;

        if (! $todoId || ! $doingId || ! $reviewId || ! $doneId) {
            return;
        }

        $sprint1 = Sprint::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Sprint Demo 1'],
            [
                'project_id' => $project->id,
                'name' => 'Sprint Demo 1',
                'goal' => 'Sprint de exemplo para validar a UI',
                'status' => SprintStatus::Active->value,
                'position' => 1,
            ],
        );

        $sprint2 = Sprint::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Sprint Demo 2'],
            [
                'project_id' => $project->id,
                'name' => 'Sprint Demo 2',
                'goal' => 'Próxima janela de trabalho',
                'status' => SprintStatus::Planned->value,
                'position' => 2,
            ],
        );

        $repo = Repository::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'provider' => 'github', 'external_id' => 'demo-' . $project->id],
            [
                'project_id' => $project->id,
                'provider' => 'github',
                'external_id' => 'demo-' . $project->id,
                'full_name' => strtolower($workspaceSlug) . '/' . strtolower(str_replace(' ', '-', $project->name)),
                'clone_url' => null,
                'is_private' => true,
            ],
        );

        $epic = Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-1'],
            [
                'project_id' => $project->id,
                'board_column_id' => $todoId,
                'sprint_id' => null,
                'type' => IssueType::Epic->value,
                'title' => 'Epic: Plataforma inicial',
                'description' => 'Dados fake para testar a aplicação',
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 1,
            ],
        );

        $story = Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-2'],
            [
                'project_id' => $project->id,
                'board_column_id' => $doingId,
                'sprint_id' => $sprint1->id,
                'epic_id' => $epic->id,
                'type' => IssueType::Story->value,
                'title' => 'Story: Melhorar fluxo de login',
                'description' => $faker->paragraphs(2, true),
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 1,
            ],
        );

        IssueDevLink::query()->withTrashed()->firstOrCreate(
            ['issue_id' => $story->id, 'repository_id' => $repo->id, 'branch_name' => 'feat/demo-login'],
            [
                'issue_id' => $story->id,
                'repository_id' => $repo->id,
                'branch_name' => 'feat/demo-login',
                'pr_mr_url' => null,
                'pr_mr_id' => null,
                'commit_sha' => null,
                'link_type' => 'MANUAL',
                'status' => 'OPEN',
            ],
        );

        $attachmentPath = 'issues/' . $story->issue_key . '/readme.txt';
        Storage::disk('public')->put($attachmentPath, "Demo attachment for {$story->issue_key}\n");
        IssueAttachment::query()->withTrashed()->firstOrCreate(
            ['issue_id' => $story->id, 'file_path' => $attachmentPath],
            [
                'issue_id' => $story->id,
                'user_id' => $user->id,
                'file_path' => $attachmentPath,
                'file_name' => 'readme.txt',
                'mime_type' => 'text/plain',
                'file_size' => Storage::disk('public')->size($attachmentPath),
            ],
        );

        IssueComment::query()->withTrashed()->firstOrCreate(
            ['issue_id' => $story->id, 'user_id' => $user->id, 'body' => 'Comentário de demo para validar a aba Comments.'],
            [
                'issue_id' => $story->id,
                'user_id' => $user->id,
                'body' => 'Comentário de demo para validar a aba Comments.',
            ],
        );

        $blocker = Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-8'],
            [
                'project_id' => $project->id,
                'board_column_id' => $doingId,
                'sprint_id' => $sprint1->id,
                'epic_id' => $epic->id,
                'type' => IssueType::Task->value,
                'title' => 'Task: Definir decisões de produto',
                'description' => $faker->paragraphs(2, true),
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 2,
            ],
        );

        $task = Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-3'],
            [
                'project_id' => $project->id,
                'board_column_id' => $reviewId,
                'sprint_id' => $sprint1->id,
                'epic_id' => $epic->id,
                'type' => IssueType::Task->value,
                'title' => 'Task: Ajustar permissões no painel',
                'description' => $faker->paragraphs(2, true),
                'blocked' => true,
                'blocked_reason' => 'Aguardando revisão de requisitos',
                'blocked_by_issue_id' => $blocker->id,
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 1,
            ],
        );

        Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-4'],
            [
                'project_id' => $project->id,
                'board_column_id' => $doneId,
                'sprint_id' => $sprint1->id,
                'epic_id' => $epic->id,
                'type' => IssueType::Bug->value,
                'title' => 'Bug: Tela lenta ao abrir lista',
                'description' => $faker->paragraphs(2, true),
                'blocked' => false,
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 1,
            ],
        );

        $parent = Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-5'],
            [
                'project_id' => $project->id,
                'board_column_id' => $todoId,
                'sprint_id' => $sprint2->id,
                'epic_id' => $epic->id,
                'type' => IssueType::Story->value,
                'title' => 'Story: Backlog e planejamento',
                'description' => $faker->paragraphs(2, true),
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 2,
            ],
        );

        Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-6'],
            [
                'project_id' => $project->id,
                'board_column_id' => $todoId,
                'sprint_id' => $sprint2->id,
                'epic_id' => $epic->id,
                'parent_id' => $parent->id,
                'type' => IssueType::Subtask->value,
                'title' => 'Subtask: Criar filtros no backlog',
                'description' => $faker->sentence(12),
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 3,
            ],
        );

        Issue::query()->withTrashed()->firstOrCreate(
            ['project_id' => $project->id, 'issue_key' => $project->key . '-7'],
            [
                'project_id' => $project->id,
                'board_column_id' => $todoId,
                'sprint_id' => null,
                'epic_id' => $epic->id,
                'type' => IssueType::Task->value,
                'title' => 'Task: Itens de backlog sem sprint',
                'description' => $faker->sentence(12),
                'assignee_id' => $user->id,
                'reporter_id' => $user->id,
                'position_in_column' => 4,
            ],
        );

        $extraCount = (int) env('DEV_SEED_DEMO_EXTRA_ISSUES', 15);
        $maxIssues = max(0, min(200, $extraCount));

        $nextNumber = 9;
        for ($i = 0; $i < $maxIssues; $i++) {
            $number = $nextNumber + $i;
            $type = $faker->randomElement([
                IssueType::Story,
                IssueType::Task,
                IssueType::Bug,
            ]);

            $columnId = $faker->randomElement([$todoId, $doingId, $reviewId, $doneId]);
            $sprintId = $faker->boolean(65) ? $sprint1->id : null;

            Issue::query()->withTrashed()->firstOrCreate(
                ['project_id' => $project->id, 'issue_key' => $project->key . '-' . $number],
                [
                    'project_id' => $project->id,
                    'board_column_id' => $columnId,
                    'sprint_id' => $sprintId,
                    'epic_id' => $epic->id,
                    'type' => $type->value,
                    'title' => Str::limit($faker->sentence(6), 80, ''),
                    'description' => $faker->paragraphs(2, true),
                    'assignee_id' => $user->id,
                    'reporter_id' => $user->id,
                    'position_in_column' => 10 + $i,
                ],
            );
        }
    }
}
