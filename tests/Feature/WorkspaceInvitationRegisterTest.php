<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceUser;
use App\Services\WorkspaceInvitationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkspaceInvitationRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_invite_link_redirects_to_register_with_token(): void
    {
        $this->withoutMiddleware();

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);

        [$invitation, $token] = app(WorkspaceInvitationService::class)->createInvitation(
            workspaceId: (string) $workspace->id,
            email: 'invitee@example.com',
            role: WorkspaceRole::Dev,
            createdByUserId: null,
        );

        $this->get('/invite/' . $token)
            ->assertRedirect('/register?invite=' . $token);
    }

    public function test_register_with_valid_invite_creates_membership_and_marks_used(): void
    {
        $this->withoutMiddleware();

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);

        [$invitation, $token] = app(WorkspaceInvitationService::class)->createInvitation(
            workspaceId: (string) $workspace->id,
            email: 'invitee@example.com',
            role: WorkspaceRole::Manager,
            createdByUserId: null,
        );

        $password = 'password123';

        $this->post('/register?invite=' . $token, [
            'name' => 'Invitee',
            'email' => 'invitee@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect('/admin');

        $user = User::query()->where('email', 'invitee@example.com')->firstOrFail();

        WorkspaceUser::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('role', WorkspaceRole::Manager->value)
            ->firstOrFail();

        $invitation->refresh();
        $this->assertNotNull($invitation->used_at);
        $this->assertSame($user->id, $invitation->accepted_user_id);
    }

    public function test_register_with_expired_invite_falls_back_to_normal_flow(): void
    {
        $this->withoutMiddleware();

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);

        $token = 'tok';
        $hash = hash('sha256', $token);

        $invitation = WorkspaceInvitation::query()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'role' => WorkspaceRole::Dev->value,
            'token_hash' => $hash,
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);

        $password = 'password123';

        $this->post('/register?invite=' . $token, [
            'name' => 'Invitee',
            'email' => 'invitee@example.com',
            'workspace_name' => 'Meu Workspace',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect('/admin');

        $this->assertDatabaseHas('workspaces', ['name' => 'Meu Workspace']);
    }
}
