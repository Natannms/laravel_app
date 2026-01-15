<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Services\PermissionResolver;

class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Board $board): bool
    {
        return app(PermissionResolver::class)->has($user, 'boards.view', (string) $board->project->workspace_id, (string) $board->project_id);
    }

    public function create(User $user): bool
    {
        return app(PermissionResolver::class)->hasInAnyWorkspace($user, 'boards.update');
    }

    public function update(User $user, Board $board): bool
    {
        return app(PermissionResolver::class)->has($user, 'boards.update', (string) $board->project->workspace_id, (string) $board->project_id);
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
