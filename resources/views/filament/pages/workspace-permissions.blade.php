<x-filament-panels::page>
    @php
        $userRows = $this->workspaceUserRows();
        $groups = $this->groups();
        $roles = $this->roles();
    @endphp

    <div
        x-data="{ drawerOpen: @entangle('drawerOpen') }"
        class="space-y-4"
    >
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900/40">
            <div class="flex items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition {{ $tab === 'users' ? 'bg-gray-100 text-gray-950 dark:bg-gray-800/60 dark:text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800/40' }}"
                        wire:click="setTab('users')"
                    >
                        <span class="font-semibold">Usuários</span>
                        <span class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            {{ count($userRows) }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition {{ $tab === 'groups' ? 'bg-gray-100 text-gray-950 dark:bg-gray-800/60 dark:text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800/40' }}"
                        wire:click="setTab('groups')"
                    >
                        <span class="font-semibold">Grupos</span>
                        <span class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            {{ count($groups) }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition {{ $tab === 'roles' ? 'bg-gray-100 text-gray-950 dark:bg-gray-800/60 dark:text-white' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800/40' }}"
                        wire:click="setTab('roles')"
                    >
                        <span class="font-semibold">Roles</span>
                        <span class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            {{ count($roles) }}
                        </span>
                    </button>
                </div>

                <div class="text-xs text-gray-600 dark:text-gray-300">
                    Clique em “Permissões” para configurar
                </div>
            </div>

            <div class="mt-4 divide-y divide-gray-200 rounded-xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                @if ($tab === 'users')
                    @foreach ($userRows as $row)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">{{ $row['role'] }}</div>
                            </div>
                            <x-filament::button
                                size="sm"
                                color="gray"
                                wire:click="openDrawer('users', '{{ $row['id'] }}')"
                            >
                                Permissões
                            </x-filament::button>
                        </div>
                    @endforeach
                @elseif ($tab === 'groups')
                    @foreach ($groups as $id => $name)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $name }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Grupo</div>
                            </div>
                            <x-filament::button
                                size="sm"
                                color="gray"
                                wire:click="openDrawer('groups', '{{ $id }}')"
                            >
                                Permissões
                            </x-filament::button>
                        </div>
                    @endforeach
                @else
                    @foreach ($roles as $key => $label)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300">Role</div>
                            </div>
                            <x-filament::button
                                size="sm"
                                color="gray"
                                wire:click="openDrawer('roles', '{{ $key }}')"
                            >
                                Permissões
                            </x-filament::button>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

    <div
        x-cloak
        x-show="drawerOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex"
        role="dialog"
        aria-modal="true"
        aria-label="Permissões"
    >
        <button
            type="button"
            class="flex-1"
            style="background: rgba(0, 0, 0, 0.75); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);"
            x-on:click="drawerOpen = false"
            wire:click="closeDrawer"
            aria-label="Fechar"
        ></button>

        <section
            class="shrink-0 border-l border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900/95"
            style="width: min(500px, 100vw);"
            x-on:click.stop
        >
            <div class="flex h-full flex-col">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">Permissões</div>
                        <div class="truncate text-xs text-gray-600 dark:text-gray-300">
                            {{ $this->currentSubjectLabel() }}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800/60"
                        wire:click="closeDrawer"
                        x-on:click="drawerOpen = false"
                        aria-label="Fechar"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="space-y-4 p-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900/40">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Escopo</div>

                            <div class="mt-3 flex items-center gap-2">
                                <x-filament::button
                                    size="sm"
                                    color="{{ $scopeType === 'workspace' ? 'primary' : 'gray' }}"
                                    wire:click="setScope('workspace')"
                                >
                                    Workspace
                                </x-filament::button>

                                <x-filament::button
                                    size="sm"
                                    color="{{ $scopeType === 'project' ? 'primary' : 'gray' }}"
                                    wire:click="toggleProjectScope"
                                >
                                    Projeto
                                </x-filament::button>
                            </div>

                            @if ($scopeType === 'project')
                                <div class="mt-3">
                                    <select class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" wire:change="setScope('project', $event.target.value)">
                                        <option value="">Selecione um projeto</option>
                                        @foreach ($this->projects() as $id => $name)
                                            <option value="{{ $id }}" {{ $projectId === $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900/40">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Buscar permissão</div>
                            <div class="mt-2">
                                <input
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                                    placeholder="Ex.: attachments ou attachments.create"
                                    wire:model.live.debounce.200ms="permissionSearch"
                                />
                            </div>

                            <div class="mt-3">
                                @php
                                    $subjectReady = $tab === 'roles'
                                        ? (bool) ($role ?? null)
                                        : ($tab === 'groups' ? (bool) ($groupId ?? null) : (bool) ($workspaceUserId ?? null));
                                    $scopeReady = $scopeType === 'workspace' || ($scopeType === 'project' && (bool) ($projectId ?? null));
                                    $disabled = ! ($subjectReady && $scopeReady);
                                    $modules = $this->filteredPermissionsByModule();
                                    $searching = trim((string) $this->permissionSearch) !== '';
                                @endphp

                                @if ($modules === [])
                                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                                        Nenhuma permissão encontrada.
                                    </div>
                                @else
                                    <div x-data="{ openModule: null }" class="space-y-2">
                                        @foreach ($modules as $module => $permissions)
                                            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900/40">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2"
                                                    x-on:click="openModule = openModule === '{{ $module }}' ? null : '{{ $module }}'"
                                                >
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                                        {{ $module }}
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                                                            {{ count($permissions) }}
                                                        </span>
                                                        <span class="text-xs text-gray-600 dark:text-gray-300" x-text="openModule === '{{ $module }}' ? 'Fechar' : 'Abrir'"></span>
                                                    </div>
                                                </button>

                                                <div x-cloak x-show="openModule === '{{ $module }}' || @js($searching)" class="border-t border-gray-200 dark:border-gray-800">
                                                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                                                        @foreach ($permissions as $permission)
                                                            @php
                                                                $effect = $this->currentEffect($permission->id);
                                                                $allowOnly = $tab === 'roles';
                                                                $allowOn = $effect === 'ALLOW';
                                                                $denyOn = $effect === 'DENY';
                                                            @endphp

                                                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                                                <div class="min-w-0">
                                                                    <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $permission->name }}</div>
                                                                    <div class="truncate text-xs text-gray-600 dark:text-gray-300">{{ $permission->key }}</div>
                                                                </div>

                                                                <div class="flex items-center gap-3">
                                                                    <div class="flex items-center gap-2">
                                                                        <button
                                                                            type="button"
                                                                            role="switch"
                                                                            aria-label="Permitir: {{ $permission->key }}"
                                                                            aria-checked="{{ $allowOn ? 'true' : 'false' }}"
                                                                            title="Permitir"
                                                                            class="relative inline-flex items-center rounded-full transition {{ $disabled ? 'cursor-not-allowed opacity-40' : '' }}"
                                                                            style="width: 52px; height: 28px; background: {{ $allowOn ? '#29D57B' : '#303040' }};"
                                                                            wire:click="setEffect('{{ $permission->id }}','{{ $allowOn ? 'NONE' : 'ALLOW' }}')"
                                                                            @disabled($disabled)
                                                                        >
                                                                            <span
                                                                                class="absolute top-[2px] inline-block rounded-full bg-white transition"
                                                                                style="width: 24px; height: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.35); transform: translateX({{ $allowOn ? '26px' : '2px' }});"
                                                                            ></span>
                                                                        </button>
                                                                    </div>

                                                                    @if (! $allowOnly)
                                                                        <div class="flex items-center gap-2">
                                                                            <button
                                                                                type="button"
                                                                                role="switch"
                                                                                aria-label="Negar: {{ $permission->key }}"
                                                                                aria-checked="{{ $denyOn ? 'true' : 'false' }}"
                                                                                title="Negar"
                                                                                class="relative inline-flex items-center rounded-full transition {{ $disabled ? 'cursor-not-allowed opacity-40' : '' }}"
                                                                                style="width: 52px; height: 28px; background: {{ $denyOn ? '#ef4444' : '#303040' }};"
                                                                                wire:click="setEffect('{{ $permission->id }}','{{ $denyOn ? 'NONE' : 'DENY' }}')"
                                                                                @disabled($disabled)
                                                                            >
                                                                                <span
                                                                                    class="absolute top-[2px] inline-block rounded-full bg-white transition"
                                                                                    style="width: 24px; height: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.35); transform: translateX({{ $denyOn ? '26px' : '2px' }});"
                                                                                ></span>
                                                                            </button>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($disabled)
                                    <div class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                                        Selecione um alvo e um escopo válido para editar permissões.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    </div>
</x-filament-panels::page>
