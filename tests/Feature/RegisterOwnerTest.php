<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_workspace_and_owner_membership(): void
    {
        $this->withoutMiddleware();

        $email = 'owner@example.com';
        $password = 'password12345';

        $response = $this->post('/register', [
            'name' => 'Owner',
            'email' => $email,
            'workspace_name' => 'Meu Workspace',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect('/admin');

        $user = User::query()->where('email', $email)->firstOrFail();
        $workspace = Workspace::query()->where('name', 'Meu Workspace')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
            'deleted_at' => null,
        ]);

        $membership = WorkspaceUser::query()->where('workspace_id', $workspace->id)->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(WorkspaceRole::Owner, $membership->role);
    }
}
