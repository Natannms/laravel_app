<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Sprint;
use App\Models\User;
use App\Support\WorkspacePermissions;

class SprintPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $sprint->project->workspace_id, WorkspaceRole::cases());
    }

    public function create(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
        ]);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $sprint->project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
        ]);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $sprint->project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function restore(User $user, Sprint $sprint): bool
    {
        return $this->delete($user, $sprint);
    }

    public function forceDelete(User $user, Sprint $sprint): bool
    {
        return $this->delete($user, $sprint);
    }
}

