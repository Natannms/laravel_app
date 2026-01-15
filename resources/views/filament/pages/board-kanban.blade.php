<x-filament::page>
    @php
        $issuesByColumn = $this->issues->groupBy('board_column_id');
        $columnCount = max(1, $this->columns->count());
    @endphp

    <div
        class="flex flex-col gap-4 -mx-4 sm:-mx-6 lg:-mx-8 px-2 sm:px-3 lg:px-4"
        x-data="{
            draggingIssueId: null,
            draggingFromColumnId: null,
            draggingGroupKey: null,
            startDrag(issueId, fromColumnId, event) {
                this.draggingIssueId = issueId;
                this.draggingFromColumnId = fromColumnId;
                if (event?.dataTransfer) {
                    event.dataTransfer.setData('text/plain', issueId);
                    event.dataTransfer.effectAllowed = 'move';
                }
            },
            async drop(toColumnId, toIndex, targetGroupKey, event) {
                event?.preventDefault?.();
                const id = this.draggingIssueId || event?.dataTransfer?.getData('text/plain');
                if (!id) return;
                await $wire.moveIssue(id, toColumnId, toIndex);
                this.draggingIssueId = null;
                this.draggingFromColumnId = null;
                this.draggingGroupKey = null;
            },
        }"
    >
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button
                color="gray"
                :href="\App\Filament\Resources\ProjectResource::getUrl('issues', ['record' => $this->record->project])"
                tag="a"
            >
                Visualizar em lista
            </x-filament::button>

            <div class="w-full sm:w-64">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="search"
                        placeholder="Pesquisar cards..."
                        wire:model.live="search"
                    />
                </x-filament::input.wrapper>
            </div>

            <div>
                <select wire:model.live="sprintScope" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    @foreach ($this->sprintOptions as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="quickFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Filtros rápidos</option>
                    <option value="mine">Minhas issues</option>
                    <option value="unassigned">Sem assignee</option>
                </select>
            </div>

            <div>
                <select wire:model.live="type" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Tipo</option>
                    @foreach ($this->typeOptions as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="epicId" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Epic</option>
                    @foreach ($this->epicOptions as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="assigneeId" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Assignee</option>
                    @foreach ($this->assigneeOptions as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="blocked" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="all">Blocked: todos</option>
                    <option value="blocked">Só blocked</option>
                    <option value="unblocked">Só não blocked</option>
                </select>
            </div>

            <div>
                <select wire:model.live="groupBy" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="none">Agrupar: nenhum</option>
                    <option value="epic">Agrupar: epic</option>
                    <option value="assignee">Agrupar: assignee</option>
                </select>
            </div>

            <x-filament::button wire:click="openCreateColumn" color="gray">
                Adicionar coluna
            </x-filament::button>

            <x-filament::button wire:click="openEditBoard" color="gray">
                Editar board
            </x-filament::button>
        </div>

        <div x-data="{ open: @entangle('isIssueModalOpen') }">
            <div
                x-show="open"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-xl rounded-xl bg-white p-5 shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="min-w-0 text-base font-semibold text-gray-900 dark:text-gray-100">
                            Editar card
                        </div>
                        <button type="button" class="text-sm text-gray-600 dark:text-gray-300" @click="open = false">Fechar</button>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Status</label>
                                <select wire:model.defer="issueBoardColumnId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                    @foreach ($this->columns as $col)
                                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                                    @endforeach
                                </select>
                                @error('issueBoardColumnId') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Estimativa (h)</label>
                                    <select wire:model.defer="issueEstimateHours" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        <option value="">—</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="4">4</option>
                                        <option value="6">6</option>
                                        <option value="8">8</option>
                                    </select>
                                    @error('issueEstimateHours') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                                </div>

                                <div class="flex items-end">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input wire:model.defer="issueCoffee" type="checkbox" class="rounded border-gray-300 dark:border-gray-700" />
                                        ☕
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Assignees</label>
                            <select wire:model.defer="issueAssigneeIds" multiple class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                @foreach ($this->assigneeOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('issueAssigneeIds') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Título</label>
                            <input wire:model.defer="issueTitle" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" />
                            @error('issueTitle') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Descrição</label>
                            <textarea wire:model.defer="issueDescription" rows="4" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"></textarea>
                            @error('issueDescription') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <x-filament::button color="gray" @click="open = false">Cancelar</x-filament::button>
                        <x-filament::button wire:click="saveIssue">Salvar</x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        <div x-data="{ open: @entangle('isBoardModalOpen') }">
            <div
                x-show="open"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Editar board
                        </div>
                        <button type="button" class="text-sm text-gray-600 dark:text-gray-300" @click="open = false">Fechar</button>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div>
                            <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Project</label>
                            <select wire:model.defer="boardProjectId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                @foreach ($this->projectOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('boardProjectId') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Name</label>
                            <input wire:model.defer="boardName" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" />
                            @error('boardName') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input wire:model.defer="boardIsDefault" type="checkbox" class="rounded border-gray-300 dark:border-gray-700" />
                                Default
                            </label>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <x-filament::button color="gray" @click="open = false">Cancelar</x-filament::button>
                        <x-filament::button wire:click="saveBoard">Salvar</x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        <div x-data="{ open: @entangle('isColumnModalOpen') }">
            <div
                x-show="open"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {{ $editingColumnId ? 'Editar coluna' : 'Nova coluna' }}
                        </div>
                        <button type="button" class="text-sm text-gray-600 dark:text-gray-300" @click="open = false">Fechar</button>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div>
                            <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">Nome</label>
                            <input wire:model.defer="columnName" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" />
                            @error('columnName') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-sm text-gray-700 dark:text-gray-300">WIP limit</label>
                                <input wire:model.defer="columnWipLimit" type="number" min="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900" />
                                @error('columnWipLimit') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                            </div>

                            <div class="flex items-end gap-2">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input wire:model.defer="columnIsDone" type="checkbox" class="rounded border-gray-300 dark:border-gray-700" />
                                    Coluna DONE
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <x-filament::button color="gray" @click="open = false">Cancelar</x-filament::button>
                        <x-filament::button wire:click="saveColumn">Salvar</x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            @foreach ($this->groups as $group)
                <div class="flex flex-col gap-3" wire:key="group-{{ $group['key'] }}">
                    @if ($this->groupBy !== 'none')
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $group['label'] }}
                        </div>
                    @endif

                    <div
                        class="grid gap-3 overflow-x-hidden pb-3"
                        style="grid-template-columns: repeat({{ $columnCount }}, minmax(150px, 1fr));"
                    >
                        @foreach ($this->columns as $column)
                            @php
                                $cards = $issuesByColumn->get($column->id, collect());
                                if ($this->groupBy === 'epic') {
                                    $cards = $cards->filter(fn ($c) => ($group['value'] === null ? $c->epic_id === null : (string) $c->epic_id === (string) $group['value']));
                                } elseif ($this->groupBy === 'assignee') {
                                    $cards = $cards->filter(fn ($c) => ($group['value'] === null ? $c->assignee_id === null : (string) $c->assignee_id === (string) $group['value']));
                                }
                            @endphp

                            <div class="min-w-45 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/10" wire:key="col-{{ $group['key'] }}-{{ $column->id }}">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                            {{ $column->name }}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ $cards->count() }} cards
                                            @if ($column->wip_limit !== null)
                                                • WIP {{ $cards->count() }}/{{ (int) $column->wip_limit }}
                                            @endif
                                        </div>
                                    </div>

                                    @if ($column->is_done)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                            DONE
                                        </span>
                                    @else
                                        <div class="flex items-center gap-1">
                                            <button type="button" wire:click="moveColumn('{{ $column->id }}', -1)" class="rounded px-1 text-xs text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">←</button>
                                            <button type="button" wire:click="moveColumn('{{ $column->id }}', 1)" class="rounded px-1 text-xs text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">→</button>
                                            <button type="button" wire:click="openEditColumn('{{ $column->id }}')" class="rounded px-1 text-xs text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">Editar</button>
                                            <button type="button" wire:click="deleteColumn('{{ $column->id }}')" class="rounded px-1 text-xs text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/30">Remover</button>
                                        </div>
                                    @endif
                                </div>

                                <div
                                    class="flex min-h-15 flex-col gap-2"
                                    @dragover.prevent
                                    @drop="drop('{{ $column->id }}', null, '{{ $group['key'] }}', $event)"
                                >
                                    @if ($cards->count() === 0)
                                        <div class="rounded-lg border border-dashed border-gray-300 py-6 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            Solte aqui
                                        </div>
                                    @endif
                                    @foreach ($cards->values() as $i => $issue)
                                        <div
                                            class="rounded-lg"
                                            @dragover.prevent
                                            @drop="drop('{{ $column->id }}', {{ $this->groupBy === 'none' ? $i : 'null' }}, '{{ $group['key'] }}', $event)"
                                            wire:key="card-{{ $issue->id }}"
                                        >
                                            <div
                                                role="button"
                                                tabindex="0"
                                                draggable="true"
                                                @dragstart="draggingGroupKey='{{ $group['key'] }}'; startDrag('{{ $issue->id }}', '{{ $column->id }}', $event)"
                                                @dragend="draggingGroupKey=null; draggingIssueId=null; draggingFromColumnId=null"
                                            @click.prevent="if (draggingIssueId) return; $wire.openEditIssue('{{ $issue->id }}')"
                                            @keydown.enter.prevent="if (draggingIssueId) return; $wire.openEditIssue('{{ $issue->id }}')"
                                                class="block cursor-grab rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-950/10 hover:ring-gray-950/20 active:cursor-grabbing dark:bg-white/5 dark:ring-white/10 dark:hover:ring-white/20"
                                            >
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                        {{ $issue->issue_key }}
                                                    </div>

                                                    <div class="flex items-center gap-1">
                                                        @if ($issue->blocked)
                                                            <span
                                                                class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200"
                                                                title="{{ $issue->blocked_reason }}"
                                                            >
                                                                BLOCKED
                                                            </span>
                                                        @endif

                                                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                                            {{ $issue->type?->value }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                                                    {{ $issue->title }}
                                                </div>

                                                <div class="mt-2 flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                        <span>{{ $issue->epic?->issue_key }}</span>
                                                        @if ($issue->estimate_hours !== null)
                                                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                                                {{ rtrim(rtrim(number_format((float) $issue->estimate_hours, 2, '.', ''), '0'), '.') }}h
                                                            </span>
                                                        @endif
                                                        @if (((int) ($issue->coffee_breaks ?? 0)) > 0)
                                                            <span class="text-[12px] leading-none">☕</span>
                                                        @endif
                                                    </div>

                                                    @php
                                                        $assignees = $issue->assignees?->pluck('name')->filter()->values() ?? collect();
                                                        if ($assignees->isEmpty() && $issue->assignee?->name) {
                                                            $assignees = collect([$issue->assignee->name]);
                                                        }
                                                        $shown = $assignees->take(2);
                                                        $extra = max(0, $assignees->count() - $shown->count());
                                                    @endphp

                                                    @if ($shown->isNotEmpty())
                                                        <div class="flex items-center -space-x-1">
                                                            @foreach ($shown as $name)
                                                                @php
                                                                    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('');
                                                                @endphp
                                                                <span
                                                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-semibold text-indigo-800 ring-2 ring-white dark:bg-indigo-900/40 dark:text-indigo-200 dark:ring-gray-900"
                                                                    title="{{ $name }}"
                                                                >
                                                                    {{ strtoupper($initials) }}
                                                                </span>
                                                            @endforeach

                                                            @if ($extra > 0)
                                                                <span class="ml-2 text-[10px] font-semibold text-gray-600 dark:text-gray-300">
                                                                    +{{ $extra }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                                            Unassigned
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament::page>
