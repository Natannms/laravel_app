<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;
use App\Services\PermissionResolver;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Issue $issue): bool
    {
        return app(PermissionResolver::class)->has($user, 'issues.view', (string) $issue->project->workspace_id, (string) $issue->project_id);
    }

    public function create(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'issues.create');
    }

    public function update(User $user, Issue $issue): bool
    {
        return app(PermissionResolver::class)->has($user, 'issues.update', (string) $issue->project->workspace_id, (string) $issue->project_id);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return app(PermissionResolver::class)->has($user, 'issues.delete', (string) $issue->project->workspace_id, (string) $issue->project_id);
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
