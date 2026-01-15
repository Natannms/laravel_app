<?php

namespace App\Policies;

use App\Models\BoardColumn;
use App\Models\User;
use App\Services\PermissionResolver;

class BoardColumnPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BoardColumn $boardColumn): bool
    {
        return app(PermissionResolver::class)->has($user, 'boards.view', (string) $boardColumn->board->project->workspace_id, (string) $boardColumn->board->project_id);
    }

    public function create(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'boards.manage_columns');
    }

    public function update(User $user, BoardColumn $boardColumn): bool
    {
        return app(PermissionResolver::class)->has($user, 'boards.manage_columns', (string) $boardColumn->board->project->workspace_id, (string) $boardColumn->board->project_id);
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
