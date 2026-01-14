<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_redirects_guest_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/workspaces')->assertRedirect('/admin/login');
        $this->get('/admin/projects')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_and_resources_load_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::query()->create([
            'name' => 'Workspace',
            'slug' => 'workspace',
        ]);

        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);

        $this->actingAs($user);

        $this->get('/admin')->assertOk();
        $this->get('/admin/workspaces')->assertOk();
        $this->get('/admin/projects')->assertOk();
    }

    public function test_project_create_page_is_forbidden_for_viewer_and_allowed_for_owner(): void
    {
        $workspace = Workspace::query()->create([
            'name' => 'Workspace',
            'slug' => 'workspace',
        ]);

        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $viewer->id,
            'role' => WorkspaceRole::Viewer->value,
        ]);

        $this->actingAs($viewer);
        $this->get('/admin/projects/create')->assertForbidden();

        $this->actingAs($owner);
        $this->get('/admin/projects/create')->assertOk();
    }
}

