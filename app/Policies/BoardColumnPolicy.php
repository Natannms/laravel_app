<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\BoardColumn;
use App\Models\User;
use App\Support\WorkspacePermissions;

class BoardColumnPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BoardColumn $boardColumn): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $boardColumn->board->project->workspace_id, WorkspaceRole::cases());
    }

    public function create(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
        ]);
    }

    public function update(User $user, BoardColumn $boardColumn): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $boardColumn->board->project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
        ]);
    }

    public function delete(User $user, BoardColumn $boardColumn): bool
    {
        return $this->update($user, $boardColumn);
    }

    public function restore(User $user, BoardColumn $boardColumn): bool
    {
        return $this->update($user, $boardColumn);
    }

    public function forceDelete(User $user, BoardColumn $boardColumn): bool
    {
        return $this->update($user, $boardColumn);
    }
}

