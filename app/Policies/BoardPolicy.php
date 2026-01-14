<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Board;
use App\Models\User;
use App\Support\WorkspacePermissions;

class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Board $board): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $board->project->workspace_id, WorkspaceRole::cases());
    }

    public function create(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
        ]);
    }

    public function update(User $user, Board $board): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $board->project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
        ]);
    }

    public function delete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function restore(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function forceDelete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}

