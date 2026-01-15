<?php

namespace App\Policies;

use App\Models\Sprint;
use App\Models\User;
use App\Services\PermissionResolver;

class SprintPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return app(PermissionResolver::class)->has($user, 'sprints.view_any', (string) $sprint->project->workspace_id, (string) $sprint->project_id);
    }

    public function create(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'sprints.create');
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return app(PermissionResolver::class)->has($user, 'sprints.update', (string) $sprint->project->workspace_id, (string) $sprint->project_id);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return app(PermissionResolver::class)->has($user, 'sprints.delete', (string) $sprint->project->workspace_id, (string) $sprint->project_id);
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
