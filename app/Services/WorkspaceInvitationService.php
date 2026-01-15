<?php

namespace App\Services;

use App\Enums\WorkspaceRole;
use App\Models\WorkspaceInvitation;
use Carbon\CarbonImmutable;

class WorkspaceInvitationService
{
    public function createInvitation(string $workspaceId, string $email = '', WorkspaceRole $role = WorkspaceRole::Viewer, ?string $createdByUserId = null): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $plain);

        $invitation = WorkspaceInvitation::query()->create([
            'workspace_id' => $workspaceId,
            'email' => mb_strtolower(trim($email)),
            'role' => $role->value,
            'token_hash' => $hash,
            'expires_at' => CarbonImmutable::now()->addHours(24),
            'created_by_user_id' => $createdByUserId,
        ]);

        return [$invitation, $plain];
    }

    public function findValidByToken(string $plainToken): ?WorkspaceInvitation
    {
        $hash = hash('sha256', $plainToken);

        $invitation = WorkspaceInvitation::query()
            ->where('token_hash', $hash)
            ->first();

        if (! $invitation) {
            return null;
        }

        if ($invitation->used_at !== null) {
            return null;
        }

        if ($invitation->expires_at->isPast()) {
            return null;
        }

        return $invitation;
    }
}
