<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceGroup;
use App\Models\WorkspaceUser;
use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_group_and_attach_members(): void
    {
        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);

        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $membership = WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);

        $group = WorkspaceGroup::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Dev Team',
            'description' => 'A',
        ]);

        $group->members()->sync([$membership->id]);

        $group->refresh();
        $this->assertSame(1, $group->members()->count());
    }
}

