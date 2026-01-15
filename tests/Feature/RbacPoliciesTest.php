<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_permissions_follow_workspace_roles(): void
    {
        $this->seed(PermissionsSeeder::class);

        $workspace = Workspace::query()->create([
            'name' => 'Workspace',
            'slug' => 'workspace',
        ]);

        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $viewer = User::factory()->create();

        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $dev->id,
            'role' => WorkspaceRole::Dev->value,
        ]);
        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $viewer->id,
            'role' => WorkspaceRole::Viewer->value,
        ]);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Project',
            'key' => 'PRJ',
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('create', Project::class));
        $this->assertFalse(Gate::forUser($dev)->allows('create', Project::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Project::class));

        $this->assertTrue(Gate::forUser($owner)->allows('view', $project));
        $this->assertTrue(Gate::forUser($dev)->allows('view', $project));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $project));

        $this->assertTrue(Gate::forUser($owner)->allows('update', $project));
        $this->assertFalse(Gate::forUser($dev)->allows('update', $project));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $project));
    }
}
