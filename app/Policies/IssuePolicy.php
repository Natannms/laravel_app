<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Issue;
use App\Models\User;
use App\Support\WorkspacePermissions;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Issue $issue): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $issue->project->workspace_id, WorkspaceRole::cases());
    }

    public function create(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
            WorkspaceRole::Dev,
        ]);
    }

    public function update(User $user, Issue $issue): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $issue->project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
            WorkspaceRole::Manager,
            WorkspaceRole::Dev,
        ]);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $issue->project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function restore(User $user, Issue $issue): bool
    {
        return $this->delete($user, $issue);
    }

    public function forceDelete(User $user, Issue $issue): bool
    {
        return $this->delete($user, $issue);
    }
}

