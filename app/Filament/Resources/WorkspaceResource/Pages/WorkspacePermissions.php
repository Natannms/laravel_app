<?php

namespace App\Filament\Resources\WorkspaceResource\Pages;

use App\Enums\WorkspaceRole;
use App\Filament\Resources\WorkspaceResource;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\PermissionAssignment;
use App\Models\Project;
use App\Models\Workspace;
use App\Models\WorkspaceGroup;
use App\Models\WorkspaceUser;
use App\Services\PermissionResolver;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class WorkspacePermissions extends Page
{
    protected static string $resource = WorkspaceResource::class;

    protected static string $view = 'filament.pages.workspace-permissions';

    public Workspace $workspace;

    public string $tab = 'users';

    public string $scopeType = 'workspace';

    public ?string $projectId = null;

    public ?string $role = null;

    public ?string $groupId = null;

    public ?string $workspaceUserId = null;

    public bool $drawerOpen = false;

    public string $permissionSearch = '';

    public function mount(string $record): void
    {
        $this->workspace = Workspace::query()->withTrashed()->whereKey($record)->firstOrFail();

        $user = Auth::user();
        abort_unless($user, 403);

        abort_unless(
            app(PermissionResolver::class)->has($user, 'permissions_admin.manage', (string) $this->workspace->id, null),
            403,
        );

        $this->role = WorkspaceRole::Viewer->value;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['roles', 'groups', 'users'], true) ? $tab : 'roles';
        $this->role = $this->tab === 'roles' ? (WorkspaceRole::Viewer->value) : null;
        $this->groupId = null;
        $this->workspaceUserId = null;
        $this->drawerOpen = false;
    }

    public function setScope(string $scopeType, ?string $projectId = null): void
    {
        $this->scopeType = in_array($scopeType, ['workspace', 'project'], true) ? $scopeType : 'workspace';
        $this->projectId = $this->scopeType === 'project' ? $projectId : null;
    }

    public function toggleProjectScope(): void
    {
        if ($this->scopeType === 'project') {
            $this->setScope('workspace', null);
            return;
        }

        $this->setScope('project', $this->projectId);
    }

    public function setSubject(?string $id): void
    {
        if ($this->tab === 'roles') {
            $this->role = $id;
        } elseif ($this->tab === 'groups') {
            $this->groupId = $id;
        } else {
            $this->workspaceUserId = $id;
        }
    }

    public function openDrawer(string $tab, string $id): void
    {
        $this->tab = in_array($tab, ['roles', 'groups', 'users'], true) ? $tab : 'users';

        $this->role = null;
        $this->groupId = null;
        $this->workspaceUserId = null;

        $this->setSubject($id);

        $this->permissionSearch = '';
        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
    }

    public function setEffect(string $permissionId, string $effect): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        abort_unless(
            app(PermissionResolver::class)->has($user, 'permissions_admin.manage', (string) $this->workspace->id, null),
            403,
        );

        $permission = Permission::query()->whereKey($permissionId)->firstOrFail();

        [$subjectType, $subjectId] = $this->getSubject();
        abort_unless($subjectType && $subjectId, 422);

        [$scopeType, $scopeId] = $this->getScope();
        abort_unless($scopeType, 422);

        $allowedEffects = $this->tab === 'roles' ? ['NONE', 'ALLOW'] : ['NONE', 'ALLOW', 'DENY'];
        abort_unless(in_array($effect, $allowedEffects, true), 422);

        $before = PermissionAssignment::query()
            ->where('permission_id', $permission->id)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('scope_type', $scopeType)
            ->when($scopeId === null, fn ($q) => $q->whereNull('scope_id'), fn ($q) => $q->where('scope_id', $scopeId))
            ->first();

        if ($effect === 'NONE') {
            PermissionAssignment::query()
                ->where('permission_id', $permission->id)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->where('scope_type', $scopeType)
                ->when($scopeId === null, fn ($q) => $q->whereNull('scope_id'), fn ($q) => $q->where('scope_id', $scopeId))
                ->delete();

            $this->audit($user->id, 'permission_assignment.deleted', $permission->key, $before?->toArray(), null);

            Notification::make()->title('Permissão removida')->success()->send();
            return;
        }

        $assignment = PermissionAssignment::query()->updateOrCreate(
            [
                'permission_id' => $permission->id,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'effect' => $effect,
                'created_by_user_id' => $user->id,
            ],
        );

        $this->audit($user->id, 'permission_assignment.upserted', $permission->key, $before?->toArray(), $assignment->toArray());

        Notification::make()->title('Permissão salva')->success()->send();
    }

    public function permissionsByModule(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('key')
            ->get()
            ->groupBy('module')
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }

    public function filteredPermissionsByModule(): array
    {
        $modules = $this->permissionsByModule();

        $needleRaw = trim((string) $this->permissionSearch);
        if ($needleRaw === '') {
            return $modules;
        }

        $needle = mb_strtolower($needleRaw);

        $scored = [];

        foreach ($modules as $module => $permissions) {
            $moduleLower = mb_strtolower((string) $module);
            $moduleStarts = str_starts_with($moduleLower, $needle);
            $moduleContains = str_contains($moduleLower, $needle);

            $prefixMatches = 0;
            $containsMatches = 0;

            $filteredPermissions = [];

            foreach ($permissions as $permission) {
                $keyLower = mb_strtolower((string) ($permission->key ?? ''));
                $nameLower = mb_strtolower((string) ($permission->name ?? ''));

                $keyContains = str_contains($keyLower, $needle);
                $nameContains = str_contains($nameLower, $needle);
                $contentContains = $keyContains || $nameContains;

                if (! $moduleContains && ! $contentContains) {
                    continue;
                }

                if ($contentContains) {
                    if (str_starts_with($keyLower, $needle) || str_starts_with($nameLower, $needle)) {
                        $prefixMatches++;
                    } else {
                        $containsMatches++;
                    }
                }

                $filteredPermissions[] = $permission;
            }

            if ($filteredPermissions === []) {
                continue;
            }

            $score = 0;
            if ($moduleStarts) {
                $score += 100000;
            } elseif ($moduleContains) {
                $score += 50000;
            }
            $score += ($prefixMatches * 1000) + ($containsMatches * 100);

            $scored[] = [
                'module' => (string) $module,
                'permissions' => $filteredPermissions,
                'score' => $score,
            ];
        }

        usort($scored, function (array $a, array $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return strcmp($a['module'], $b['module']);
        });

        $result = [];
        foreach ($scored as $row) {
            $result[$row['module']] = $row['permissions'];
        }

        return $result;
    }

    public function roles(): array
    {
        return collect(WorkspaceRole::cases())->mapWithKeys(fn (WorkspaceRole $r) => [$r->value => $r->value])->all();
    }

    public function groups(): array
    {
        return WorkspaceGroup::query()
            ->where('workspace_id', $this->workspace->id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    public function workspaceUsers(): array
    {
        return WorkspaceUser::query()
            ->where('workspace_id', $this->workspace->id)
            ->with('user')
            ->get()
            ->sortBy(fn (WorkspaceUser $wu) => (string) ($wu->user?->name ?? ''))
            ->mapWithKeys(fn (WorkspaceUser $wu) => [(string) $wu->id => (string) ($wu->user?->name ?? $wu->user?->email ?? 'Usuário')])
            ->all();
    }

    public function workspaceUserRows(): array
    {
        return WorkspaceUser::query()
            ->where('workspace_id', $this->workspace->id)
            ->with('user')
            ->get()
            ->sortBy(fn (WorkspaceUser $wu) => (string) ($wu->user?->name ?? ''))
            ->map(fn (WorkspaceUser $wu) => [
                'id' => (string) $wu->id,
                'name' => (string) ($wu->user?->name ?? $wu->user?->email ?? 'Usuário'),
                'role' => (string) ($wu->role instanceof \App\Enums\WorkspaceRole ? $wu->role->value : $wu->role),
            ])
            ->values()
            ->all();
    }

    public function currentSubjectLabel(): string
    {
        if ($this->tab === 'roles') {
            return (string) ($this->role ?? '');
        }

        if ($this->tab === 'groups') {
            $id = (string) ($this->groupId ?? '');
            if ($id === '') {
                return '';
            }

            return (string) (WorkspaceGroup::query()->whereKey($id)->value('name') ?? '');
        }

        $id = (string) ($this->workspaceUserId ?? '');
        if ($id === '') {
            return '';
        }

        $name = WorkspaceUser::query()
            ->whereKey($id)
            ->with('user')
            ->first()?->user?->name;

        if ($name) {
            return (string) $name;
        }

        return (string) (WorkspaceUser::query()->whereKey($id)->with('user')->first()?->user?->email ?? '');
    }

    public function projects(): array
    {
        return Project::query()
            ->where('workspace_id', $this->workspace->id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    public function currentEffect(string $permissionId): string
    {
        [$subjectType, $subjectId] = $this->getSubject();
        if (! $subjectType || ! $subjectId) {
            return 'NONE';
        }

        [$scopeType, $scopeId] = $this->getScope();
        if (! $scopeType) {
            return 'NONE';
        }

        $assignment = PermissionAssignment::query()
            ->where('permission_id', $permissionId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('scope_type', $scopeType)
            ->when($scopeId === null, fn ($q) => $q->whereNull('scope_id'), fn ($q) => $q->where('scope_id', $scopeId))
            ->first();

        return $assignment?->effect ?? 'NONE';
    }

    private function getSubject(): array
    {
        if ($this->tab === 'roles') {
            return ['role', (string) ($this->role ?? '')];
        }

        if ($this->tab === 'groups') {
            return ['group', (string) ($this->groupId ?? '')];
        }

        return ['user', (string) ($this->workspaceUserId ?? '')];
    }

    private function getScope(): array
    {
        if ($this->scopeType === 'project') {
            if (! $this->projectId) {
                return [null, null];
            }

            return ['project', $this->projectId];
        }

        return ['workspace', (string) $this->workspace->id];
    }

    private function audit(string $actorUserId, string $action, string $permissionKey, ?array $before, ?array $after): void
    {
        AuditLog::query()->create([
            'workspace_id' => $this->workspace->id,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'target_type' => 'permission',
            'target_id' => $permissionKey,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
