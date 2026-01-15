<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Filament\Resources\WorkspaceResource;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspacePermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_page_loads_for_soft_deleted_workspace(): void
    {
        $this->seed(PermissionsSeeder::class);

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

        $workspace->delete();
        $this->assertNotNull(Workspace::query()->withTrashed()->find($workspace->id));
        $this->actingAs($user);
        $this->assertNotNull(WorkspaceResource::resolveRecordRouteBinding($workspace->id));

        $this->get("/admin/workspaces/{$workspace->id}/permissions")
            ->assertOk();
    }
}
