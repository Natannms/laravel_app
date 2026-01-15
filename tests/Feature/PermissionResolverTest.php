<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Permission;
use App\Models\PermissionAssignment;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceGroup;
use App\Models\WorkspaceUser;
use App\Services\PermissionResolver;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_precedence_user_deny_overrides_everything(): void
    {
        $this->seed(PermissionsSeeder::class);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $user = User::factory()->create();

        $membership = WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Viewer->value,
        ]);

        $permissionId = (string) Permission::query()->where('key', 'projects.create')->value('id');
        $this->assertNotEmpty($permissionId);

        $group = WorkspaceGroup::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'G',
        ]);
        $group->members()->sync([$membership->id]);

        PermissionAssignment::query()->create([
            'permission_id' => $permissionId,
            'subject_type' => 'group',
            'subject_id' => (string) $group->id,
            'scope_type' => 'workspace',
            'scope_id' => (string) $workspace->id,
            'effect' => 'ALLOW',
        ]);

        PermissionAssignment::query()->create([
            'permission_id' => $permissionId,
            'subject_type' => 'user',
            'subject_id' => (string) $membership->id,
            'scope_type' => 'workspace',
            'scope_id' => (string) $workspace->id,
            'effect' => 'DENY',
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertFalse($resolver->has($user, 'projects.create', (string) $workspace->id, null));
    }

    public function test_precedence_user_allow_overrides_group_deny(): void
    {
        $this->seed(PermissionsSeeder::class);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $user = User::factory()->create();

        $membership = WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Viewer->value,
        ]);

        $permissionId = (string) Permission::query()->where('key', 'projects.create')->value('id');
        $this->assertNotEmpty($permissionId);

        $group = WorkspaceGroup::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'G',
        ]);
        $group->members()->sync([$membership->id]);

        PermissionAssignment::query()->create([
            'permission_id' => $permissionId,
            'subject_type' => 'group',
            'subject_id' => (string) $group->id,
            'scope_type' => 'workspace',
            'scope_id' => (string) $workspace->id,
            'effect' => 'DENY',
        ]);

        PermissionAssignment::query()->create([
            'permission_id' => $permissionId,
            'subject_type' => 'user',
            'subject_id' => (string) $membership->id,
            'scope_type' => 'workspace',
            'scope_id' => (string) $workspace->id,
            'effect' => 'ALLOW',
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertTrue($resolver->has($user, 'projects.create', (string) $workspace->id, null));
    }

    public function test_scope_project_is_independent_from_workspace_scope(): void
    {
        $this->seed(PermissionsSeeder::class);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'P', 'key' => 'P']);

        $user = User::factory()->create();
        $membership = WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Viewer->value,
        ]);

        $permissionId = (string) Permission::query()->where('key', 'issues.move')->value('id');
        $this->assertNotEmpty($permissionId);

        PermissionAssignment::query()->create([
            'permission_id' => $permissionId,
            'subject_type' => 'user',
            'subject_id' => (string) $membership->id,
            'scope_type' => 'project',
            'scope_id' => (string) $project->id,
            'effect' => 'ALLOW',
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertTrue($resolver->has($user, 'issues.move', (string) $workspace->id, (string) $project->id));
        $this->assertFalse($resolver->has($user, 'issues.move', (string) $workspace->id, null));
    }
}

