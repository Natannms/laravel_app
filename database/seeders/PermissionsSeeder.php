<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\Permission;
use App\Models\PermissionAssignment;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = $this->permissionCatalog();

        foreach ($permissions as $row) {
            Permission::query()->updateOrCreate(
                ['key' => $row['key']],
                [
                    'module' => $row['module'],
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                ],
            );
        }

        $this->seedRoleDefaults();
    }

    private function seedRoleDefaults(): void
    {
        $allPermissionIds = Permission::query()->pluck('id', 'key')->all();

        $allowAll = array_keys($allPermissionIds);

        $viewer = [
            'projects.view_any',
            'projects.view',
            'boards.view',
            'sprints.view_any',
            'issues.view_any',
            'issues.view',
            'reports.view',
        ];

        $dev = array_values(array_unique(array_merge($viewer, [
            'issues.create',
            'issues.update',
            'issues.move',
            'issues.assign',
            'issues.estimate',
            'issues.block',
            'issues.link_git',
            'comments.create',
            'comments.update',
            'comments.delete',
            'attachments.create',
            'attachments.delete',
            'backlog.plan',
        ])));

        $manager = array_values(array_unique(array_merge($dev, [
            'boards.update',
            'boards.manage_columns',
            'sprints.create',
            'sprints.update',
            'sprints.delete',
            'sprints.start',
            'sprints.close',
            'epics.move_with_children',
        ])));

        $roleMap = [
            WorkspaceRole::Owner->value => $allowAll,
            WorkspaceRole::Admin->value => $allowAll,
            WorkspaceRole::Manager->value => $manager,
            WorkspaceRole::Dev->value => $dev,
            WorkspaceRole::Viewer->value => $viewer,
        ];

        foreach ($roleMap as $role => $keys) {
            foreach ($keys as $key) {
                $permissionId = $allPermissionIds[$key] ?? null;
                if (! $permissionId) {
                    continue;
                }

                PermissionAssignment::query()->updateOrCreate(
                    [
                        'permission_id' => $permissionId,
                        'subject_type' => 'role',
                        'subject_id' => $role,
                        'scope_type' => 'global',
                        'scope_id' => null,
                    ],
                    [
                        'effect' => 'ALLOW',
                        'created_by_user_id' => null,
                    ],
                );
            }
        }
    }

    private function permissionCatalog(): array
    {
        return [
            ['key' => 'workspaces.update', 'module' => 'workspaces', 'name' => 'Atualizar workspace'],
            ['key' => 'workspaces.delete', 'module' => 'workspaces', 'name' => 'Excluir workspace'],

            ['key' => 'workspace_members.view_any', 'module' => 'workspace_members', 'name' => 'Listar membros do workspace'],
            ['key' => 'workspace_members.invite', 'module' => 'workspace_members', 'name' => 'Convidar membro para workspace'],
            ['key' => 'workspace_members.update_role', 'module' => 'workspace_members', 'name' => 'Atualizar role do membro'],
            ['key' => 'workspace_members.remove', 'module' => 'workspace_members', 'name' => 'Remover membro do workspace'],

            ['key' => 'projects.view_any', 'module' => 'projects', 'name' => 'Listar projetos'],
            ['key' => 'projects.view', 'module' => 'projects', 'name' => 'Ver projeto'],
            ['key' => 'projects.create', 'module' => 'projects', 'name' => 'Criar projeto'],
            ['key' => 'projects.update', 'module' => 'projects', 'name' => 'Atualizar projeto'],
            ['key' => 'projects.delete', 'module' => 'projects', 'name' => 'Excluir projeto'],

            ['key' => 'boards.view', 'module' => 'boards', 'name' => 'Ver board'],
            ['key' => 'boards.update', 'module' => 'boards', 'name' => 'Atualizar board'],
            ['key' => 'boards.manage_columns', 'module' => 'boards', 'name' => 'Gerenciar colunas do board'],

            ['key' => 'sprints.view_any', 'module' => 'sprints', 'name' => 'Listar sprints'],
            ['key' => 'sprints.create', 'module' => 'sprints', 'name' => 'Criar sprint'],
            ['key' => 'sprints.update', 'module' => 'sprints', 'name' => 'Atualizar sprint'],
            ['key' => 'sprints.delete', 'module' => 'sprints', 'name' => 'Excluir sprint'],
            ['key' => 'sprints.start', 'module' => 'sprints', 'name' => 'Iniciar sprint'],
            ['key' => 'sprints.close', 'module' => 'sprints', 'name' => 'Fechar sprint'],

            ['key' => 'backlog.plan', 'module' => 'backlog', 'name' => 'Planejar backlog (mover para sprint)'],
            ['key' => 'epics.move_with_children', 'module' => 'epics', 'name' => 'Mover epic com filhos'],

            ['key' => 'issues.view_any', 'module' => 'issues', 'name' => 'Listar issues'],
            ['key' => 'issues.view', 'module' => 'issues', 'name' => 'Ver issue'],
            ['key' => 'issues.create', 'module' => 'issues', 'name' => 'Criar issue'],
            ['key' => 'issues.update', 'module' => 'issues', 'name' => 'Atualizar issue'],
            ['key' => 'issues.delete', 'module' => 'issues', 'name' => 'Excluir issue'],
            ['key' => 'issues.move', 'module' => 'issues', 'name' => 'Mover issue (drag and drop)'],
            ['key' => 'issues.assign', 'module' => 'issues', 'name' => 'Atribuir issue (assignee)'],
            ['key' => 'issues.estimate', 'module' => 'issues', 'name' => 'Editar estimativa'],
            ['key' => 'issues.block', 'module' => 'issues', 'name' => 'Bloquear/Desbloquear issue'],
            ['key' => 'issues.link_git', 'module' => 'issues', 'name' => 'Vincular Git (branch/PR)'],

            ['key' => 'comments.create', 'module' => 'comments', 'name' => 'Criar comentário'],
            ['key' => 'comments.update', 'module' => 'comments', 'name' => 'Atualizar comentário'],
            ['key' => 'comments.delete', 'module' => 'comments', 'name' => 'Excluir comentário'],

            ['key' => 'attachments.create', 'module' => 'attachments', 'name' => 'Adicionar anexo'],
            ['key' => 'attachments.delete', 'module' => 'attachments', 'name' => 'Excluir anexo'],

            ['key' => 'integrations.manage', 'module' => 'integrations', 'name' => 'Gerenciar integrações'],
            ['key' => 'repositories.manage', 'module' => 'repositories', 'name' => 'Gerenciar repositórios'],

            ['key' => 'reports.view', 'module' => 'reports', 'name' => 'Ver relatórios'],
            ['key' => 'permissions_admin.manage', 'module' => 'permissions_admin', 'name' => 'Gerenciar permissões'],
        ];
    }
}

