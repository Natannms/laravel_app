<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionAssignment;
use App\Models\WorkspaceUser;

class PermissionResolver
{
    private array $membershipCache = [];
    private array $groupIdsCache = [];
    private array $permissionIdCache = [];
    private array $decisionCache = [];

    public function has(\App\Models\User $user, string $permissionKey, string $workspaceId, ?string $projectId = null): bool
    {
        $cacheKey = implode('|', [
            (string) $user->id,
            $workspaceId,
            $projectId ?: '',
            $permissionKey,
        ]);

        if (array_key_exists($cacheKey, $this->decisionCache)) {
            return (bool) $this->decisionCache[$cacheKey];
        }

        $membership = $this->getMembership((string) $user->id, $workspaceId);
        if (! $membership) {
            return $this->decisionCache[$cacheKey] = false;
        }

        $permissionId = $this->getPermissionId($permissionKey);
        if (! $permissionId) {
            return $this->decisionCache[$cacheKey] = false;
        }

        $scopeTuples = $this->getScopes($workspaceId, $projectId);

        $userSubject = ['user', (string) $membership->id];
        $roleValue = $membership->role instanceof \App\Enums\WorkspaceRole ? $membership->role->value : (string) $membership->role;
        $roleSubject = ['role', $roleValue];
        $groupSubjects = array_map(
            fn (string $groupId) => ['group', $groupId],
            $this->getGroupIds((string) $membership->id),
        );

        if ($this->matchesAny($permissionId, [$userSubject], $scopeTuples, 'DENY')) {
            return $this->decisionCache[$cacheKey] = false;
        }

        if ($this->matchesAny($permissionId, [$userSubject], $scopeTuples, 'ALLOW')) {
            return $this->decisionCache[$cacheKey] = true;
        }

        if ($groupSubjects !== [] && $this->matchesAny($permissionId, $groupSubjects, $scopeTuples, 'DENY')) {
            return $this->decisionCache[$cacheKey] = false;
        }

        if ($groupSubjects !== [] && $this->matchesAny($permissionId, $groupSubjects, $scopeTuples, 'ALLOW')) {
            return $this->decisionCache[$cacheKey] = true;
        }

        if ($this->matchesAny($permissionId, [$roleSubject], $scopeTuples, 'ALLOW')) {
            return $this->decisionCache[$cacheKey] = true;
        }

        return $this->decisionCache[$cacheKey] = false;
    }

    public function hasInAnyWorkspace(\App\Models\User $user, string $permissionKey): bool
    {
        $workspaceIds = \App\Models\WorkspaceUser::query()
            ->where('user_id', $user->id)
            ->pluck('workspace_id')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values()
            ->all();

        foreach ($workspaceIds as $workspaceId) {
            if ($this->has($user, $permissionKey, $workspaceId, null)) {
                return true;
            }
        }

        return false;
    }

    private function getMembership(string $userId, string $workspaceId): ?WorkspaceUser
    {
        $key = $userId . '|' . $workspaceId;

        if (array_key_exists($key, $this->membershipCache)) {
            return $this->membershipCache[$key];
        }

        return $this->membershipCache[$key] = WorkspaceUser::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();
    }

    private function getGroupIds(string $workspaceUserId): array
    {
        if (array_key_exists($workspaceUserId, $this->groupIdsCache)) {
            return $this->groupIdsCache[$workspaceUserId];
        }

        $ids = \Illuminate\Support\Facades\DB::table('workspace_group_members')
            ->where('workspace_user_id', $workspaceUserId)
            ->pluck('workspace_group_id')
            ->map(fn ($v) => (string) $v)
            ->all();

        return $this->groupIdsCache[$workspaceUserId] = $ids;
    }

    private function getPermissionId(string $key): ?string
    {
        if (array_key_exists($key, $this->permissionIdCache)) {
            return $this->permissionIdCache[$key];
        }

        $permission = Permission::query()->where('key', $key)->first();

        return $this->permissionIdCache[$key] = $permission?->id ? (string) $permission->id : null;
    }

    private function getScopes(string $workspaceId, ?string $projectId): array
    {
        $scopes = [
            ['global', null],
            ['workspace', $workspaceId],
        ];

        if ($projectId) {
            array_unshift($scopes, ['project', $projectId]);
        }

        return $scopes;
    }

    private function matchesAny(string $permissionId, array $subjects, array $scopes, string $effect): bool
    {
        $query = PermissionAssignment::query()
            ->where('permission_id', $permissionId)
            ->where('effect', $effect)
            ->where(function ($q) use ($subjects) {
                foreach ($subjects as [$type, $id]) {
                    $q->orWhere(function ($qq) use ($type, $id) {
                        $qq->where('subject_type', $type)->where('subject_id', $id);
                    });
                }
            })
            ->where(function ($q) use ($scopes) {
                foreach ($scopes as [$type, $id]) {
                    $q->orWhere(function ($qq) use ($type, $id) {
                        $qq->where('scope_type', $type);
                        if ($id === null) {
                            $qq->whereNull('scope_id');
                        } else {
                            $qq->where('scope_id', $id);
                        }
                    });
                }
            })
            ->limit(1);

        return $query->exists();
    }
}
