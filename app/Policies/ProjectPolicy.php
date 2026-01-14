<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Support\WorkspacePermissions;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $project->workspace_id, WorkspaceRole::cases());
    }

    public function create(User $user): bool
    {
        return WorkspacePermissions::hasRoleInAnyWorkspace($user, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function update(User $user, Project $project): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function delete(User $user, Project $project): bool
    {
        return WorkspacePermissions::hasAnyRole($user, $project->workspace_id, [
            WorkspaceRole::Owner,
            WorkspaceRole::Admin,
        ]);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }
}

