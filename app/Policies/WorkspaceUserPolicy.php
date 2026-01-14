<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceUser;
use App\Support\WorkspacePermissions;

class WorkspaceUserPolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, WorkspaceRole::cases());
    }

    public function view(User $user, WorkspaceUser $workspaceUser): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $workspaceUser->workspace_id, WorkspaceRole::cases());
    }

    public function create(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function update(User $user, WorkspaceUser $workspaceUser): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $workspaceUser->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function delete(User $user, WorkspaceUser $workspaceUser): bool
    {
        return $this->update($user, $workspaceUser);
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

