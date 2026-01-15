<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Services\PermissionResolver;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return app(PermissionResolver::class)->has($user, 'projects.view', (string) $project->workspace_id, (string) $project->id);
    }

    public function create(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return app(PermissionResolver::class)->has($user, 'projects.update', (string) $project->workspace_id, (string) $project->id);
    }

    public function delete(User $user, Project $project): bool
    {
        return app(PermissionResolver::class)->has($user, 'projects.delete', (string) $project->workspace_id, (string) $project->id);
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
