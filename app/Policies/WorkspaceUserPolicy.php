<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceUser;
use App\Services\PermissionResolver;

class WorkspaceUserPolicy
{
    public function viewAny(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'workspace_members.view_any');
    }

    public function view(User $user, WorkspaceUser $workspaceUser): bool
    {
        return app(PermissionResolver::class)->has($user, 'workspace_members.view_any', (string) $workspaceUser->workspace_id, null);
    }

    public function create(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'workspace_members.invite');
    }

    public function update(User $user, WorkspaceUser $workspaceUser): bool
    {
        return app(PermissionResolver::class)->has($user, 'workspace_members.update_role', (string) $workspaceUser->workspace_id, null);
    }

    public function delete(User $user, WorkspaceUser $workspaceUser): bool
    {
        return app(PermissionResolver::class)->has($user, 'workspace_members.remove', (string) $workspaceUser->workspace_id, null);
    }

    public function restore(User $user, WorkspaceUser $workspaceUser): bool
    {
        return $this->update($user, $workspaceUser);
    }

    public function forceDelete(User $user, WorkspaceUser $workspaceUser): bool
    {
        return $this->update($user, $workspaceUser);
    }
}
