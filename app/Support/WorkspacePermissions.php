<?php

namespace App\Support;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceUser;

class WorkspacePermissions
{
    public static function roleForWorkspace(User $user, string $workspaceId): ?WorkspaceRole
    {
        $role = WorkspaceUser::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->value('role');

        if (! $role) {
            return null;
        }

        if ($role instanceof WorkspaceRole) {
            return $role;
        }

        return WorkspaceRole::tryFrom((string) $role);
    }

    public static function hasAnyRole(User $user, string $workspaceId, array $roles): bool
    {
        $role = self::roleForWorkspace($user, $workspaceId);
        if (! $role) {
            return false;
        }

        $allowed = array_map(
            fn ($value) => $value instanceof WorkspaceRole ? $value->value : (string) $value,
            $roles,
        );

        return in_array($role->value, $allowed, true);
    }

    public static function hasRoleInAnyWorkspace(User $user, array $roles): bool
    {
        $allowed = array_map(
            fn ($value) => $value instanceof WorkspaceRole ? $value->value : (string) $value,
            $roles,
        );

        return WorkspaceUser::query()
            ->where('user_id', $user->id)
            ->whereIn('role', $allowed)
            ->exists();
    }
}
