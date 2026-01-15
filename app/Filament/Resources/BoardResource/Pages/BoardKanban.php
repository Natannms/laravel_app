<?php

namespace App\Filament\Resources\BoardResource\Pages;

use App\Filament\Resources\BoardResource;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\User;
use App\Models\Sprint;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BoardKanban extends Page
{
    protected static string $resource = BoardResource::class;

    protected static string $view = 'filament.pages.board-kanban';

    protected ?string $maxContentWidth = 'full';

    public Board $record;

    public string $search = '';
    public ?string $type = null;
    public ?string $epicId = null;
    public ?string $assigneeId = null;
    public string $blocked = 'all';
    public string $quickFilter = '';
    public string $groupBy = 'none';
    public string $sprintScope = 'BACKLOG';

    public bool $isColumnModalOpen = false;
    public ?string $editingColumnId = null;
    public string $columnName = '';
    public ?int $columnWipLimit = null;
    public ?string $columnColor = null;
    public bool $columnIsDone = false;

    public bool $isBoardModalOpen = false;
    public string $boardProjectId = '';
    public string $boardName = '';
    public bool $boardIsDefault = false;

    public bool $isIssueModalOpen = false;
    public ?string $editingIssueId = null;
    public string $issueTitle = '';
    public string $issueDescription = '';
    public string $issueBoardColumnId = '';
    public string $issueEstimateHours = '';
    public bool $issueCoffee = false;
    /**
     * @var array<string>
     */
    public array $issueAssigneeIds = [];

    public function updatedSearch(): void
    {
        //
    }

    public function mount(Board $record): void
    {
        $this->record = $record;

        abort_unless(
            Auth::check() && $record->project->workspace->workspaceUsers()->where('user_id', Auth::id())->exists(),
            403
        );

        session()->put('current_project_id', (string) $record->project_id);

        $activeSprintId = Sprint::query()
            ->where('project_id', $record->project_id)
            ->where('status', SprintStatus::Active->value)
            ->orderBy('position')
            ->value('id');

        $this->sprintScope = $activeSprintId ? (string) $activeSprintId : 'BACKLOG';

        $this->boardProjectId = (string) $record->project_id;
        $this->boardName = (string) $record->name;
        $this->boardIsDefault = (bool) $record->is_default;
    }

    public function getProjectOptionsProperty(): array
    {
        $workspaceId = (string) $this->record->project->workspace_id;

        return Project::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function openEditBoard(): void
    {
        $this->boardProjectId = (string) $this->record->project_id;
        $this->boardName = (string) $this->record->name;
        $this->boardIsDefault = (bool) $this->record->is_default;
        $this->isBoardModalOpen = true;
    }

    public function saveBoard(): void
    {
        $data = $this->validate([
            'boardProjectId' => ['required', 'string'],
            'boardName' => ['required', 'string', 'max:255'],
            'boardIsDefault' => ['boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $this->record->project_id = $data['boardProjectId'];
            $this->record->name = $data['boardName'];
            $this->record->is_default = (bool) $data['boardIsDefault'];
            $this->record->save();

            if ($this->record->is_default) {
                Board::query()
                    ->where('project_id', $this->record->project_id)
                    ->whereKeyNot($this->record->id)
                    ->update(['is_default' => false]);
            }
        });

        $this->isBoardModalOpen = false;

        Notification::make()
            ->title('Board salvo')
            ->success()
            ->send();
    }

    public function getColumnsProperty(): Collection
    {
        return $this->record->columns()->orderBy('position')->get();
    }

    public function getIssuesProperty(): Collection
    {
        $columnIds = $this->columns->pluck('id')->all();
        if ($columnIds === []) {
            return collect();
        }

        return Issue::query()
            ->with(['assignee', 'assignees', 'epic'])
            ->whereIn('board_column_id', $columnIds)
            ->when($this->sprintScope === 'BACKLOG', fn (Builder $q) => $q->whereNull('sprint_id'))
            ->when($this->sprintScope !== 'BACKLOG' && $this->sprintScope !== 'ALL', fn (Builder $q) => $q->where('sprint_id', $this->sprintScope))
            ->when(
                $this->search !== '',
                fn (Builder $q) => $q->where(function (Builder $qq) {
                    $qq->where('issue_key', 'like', '%' . $this->search . '%')
                        ->orWhere('title', 'like', '%' . $this->search . '%');
                }),
            )
            ->when($this->type, fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->epicId, fn (Builder $q) => $q->where('epic_id', $this->epicId))
            ->when(
                $this->assigneeId,
                fn (Builder $q) => $q->whereHas('assignees', fn (Builder $qq) => $qq->where('users.id', $this->assigneeId)),
            )
            ->when($this->blocked === 'blocked', fn (Builder $q) => $q->where('blocked', true))
            ->when($this->blocked === 'unblocked', fn (Builder $q) => $q->where('blocked', false))
            ->when(
                $this->quickFilter === 'mine',
                fn (Builder $q) => $q->whereHas('assignees', fn (Builder $qq) => $qq->where('users.id', Auth::id())),
            )
            ->when($this->quickFilter === 'unassigned', fn (Builder $q) => $q->whereDoesntHave('assignees'))
            ->orderBy('position_in_column')
            ->get();
    }

    public function openEditIssue(string $issueId): void
    {
        $allowedColumnIds = $this->columns->pluck('id')->all();

        $issue = Issue::query()
            ->with(['assignees'])
            ->whereKey($issueId)
            ->firstOrFail();

        abort_unless(in_array((string) $issue->board_column_id, $allowedColumnIds, true), 403);

        $this->editingIssueId = (string) $issue->id;
        $this->issueTitle = (string) $issue->title;
        $this->issueDescription = (string) ($issue->description ?? '');
        $this->issueBoardColumnId = (string) $issue->board_column_id;
        $this->issueEstimateHours = $issue->estimate_hours === null ? '' : rtrim(rtrim(number_format((float) $issue->estimate_hours, 2, '.', ''), '0'), '.');
        $this->issueCoffee = ((int) ($issue->coffee_breaks ?? 0)) > 0;
        $this->issueAssigneeIds = $issue->assignees->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $this->isIssueModalOpen = true;
    }

    public function saveIssue(): void
    {
        if (! $this->editingIssueId) {
            return;
        }

        $data = $this->validate([
            'issueTitle' => ['required', 'string', 'max:255'],
            'issueDescription' => ['nullable', 'string'],
            'issueBoardColumnId' => ['required', 'string'],
            'issueEstimateHours' => ['nullable', 'string'],
            'issueCoffee' => ['boolean'],
            'issueAssigneeIds' => ['array'],
            'issueAssigneeIds.*' => ['string'],
        ]);

        $allowedColumnIds = $this->columns->pluck('id')->all();
        if (! in_array($data['issueBoardColumnId'], $allowedColumnIds, true)) {
            abort(403);
        }

        $issue = Issue::query()->whereKey($this->editingIssueId)->firstOrFail();

        $fromColumnId = (string) $issue->board_column_id;

        DB::transaction(function () use ($issue, $data) {
            $issue->title = $data['issueTitle'];
            $issue->description = $data['issueDescription'] ?? null;

            $estimate = trim((string) ($data['issueEstimateHours'] ?? ''));
            $issue->estimate_hours = $estimate === '' ? null : (float) $estimate;
            $issue->coffee_breaks = ! empty($data['issueCoffee']) ? 1 : 0;

            $assigneeIds = array_values(array_unique(array_filter($data['issueAssigneeIds'] ?? [], fn ($v) => is_string($v) && $v !== '')));
            $issue->assignee_id = $assigneeIds[0] ?? null;
            $issue->save();

            $issue->assignees()->sync($assigneeIds);
        });

        if ($fromColumnId !== $data['issueBoardColumnId']) {
            $this->moveIssue((string) $issue->id, (string) $data['issueBoardColumnId'], null);

            $fresh = $issue->fresh();
            if ((string) $fresh->board_column_id !== (string) $data['issueBoardColumnId']) {
                return;
            }
        }

        $this->isIssueModalOpen = false;
        $this->editingIssueId = null;
    }

    public function getSprintOptionsProperty(): array
    {
        $options = [
            'BACKLOG' => 'Backlog (sem sprint)',
            'ALL' => 'Todas',
        ];

        $sprints = Sprint::query()
            ->where('project_id', $this->record->project_id)
            ->orderBy('position')
            ->get(['id', 'name', 'status']);

        foreach ($sprints as $sprint) {
            $options[(string) $sprint->id] = "{$sprint->name} ({$sprint->status->value})";
        }

        return $options;
    }

    public function getSelectedSprintProperty(): ?Sprint
    {
        if ($this->sprintScope === 'BACKLOG' || $this->sprintScope === 'ALL') {
            return null;
        }

        return Sprint::query()->whereKey($this->sprintScope)->first();
    }

    public function closeSprint(): void
    {
        $sprint = $this->selectedSprint;
        if (! $sprint) {
            return;
        }

        if ($sprint->status !== SprintStatus::Active) {
            return;
        }

        $sprint->status = SprintStatus::Closed;
        $sprint->save();

        Notification::make()
            ->title('Sprint concluída')
            ->success()
            ->send();
    }

    public function openCreateColumn(): void
    {
        $this->editingColumnId = null;
        $this->columnName = '';
        $this->columnWipLimit = null;
        $this->columnColor = null;
        $this->columnIsDone = false;
        $this->isColumnModalOpen = true;
    }

    public function openEditColumn(string $columnId): void
    {
        $column = BoardColumn::query()
            ->where('board_id', $this->record->id)
            ->whereKey($columnId)
            ->firstOrFail();

        $this->editingColumnId = (string) $column->id;
        $this->columnName = (string) $column->name;
        $this->columnWipLimit = $column->wip_limit !== null ? (int) $column->wip_limit : null;
        $this->columnColor = $column->color;
        $this->columnIsDone = (bool) $column->is_done;
        $this->isColumnModalOpen = true;
    }

    public function saveColumn(): void
    {
        $data = $this->validate([
            'columnName' => ['required', 'string', 'max:255'],
            'columnWipLimit' => ['nullable', 'integer', 'min:0'],
            'columnColor' => ['nullable', 'string', 'max:50'],
            'columnIsDone' => ['boolean'],
        ]);

        $attributes = [
            'name' => $data['columnName'],
            'wip_limit' => $data['columnWipLimit'],
            'color' => $data['columnColor'],
            'is_done' => $data['columnIsDone'],
        ];

        if ($this->editingColumnId) {
            BoardColumn::query()
                ->where('board_id', $this->record->id)
                ->whereKey($this->editingColumnId)
                ->update($attributes);
        } else {
            $maxPosition = (int) (BoardColumn::query()->where('board_id', $this->record->id)->max('position') ?? 0);
            BoardColumn::query()->create(array_merge($attributes, [
                'board_id' => $this->record->id,
                'position' => $maxPosition + 1,
            ]));
        }

        $this->isColumnModalOpen = false;

        Notification::make()
            ->title('Coluna salva')
            ->success()
            ->send();
    }

    public function deleteColumn(string $columnId): void
    {
        $column = BoardColumn::query()
            ->where('board_id', $this->record->id)
            ->whereKey($columnId)
            ->firstOrFail();

        $count = Issue::query()->where('board_column_id', $column->id)->count();
        if ($count > 0) {
            Notification::make()
                ->title('Não é possível remover a coluna')
                ->body("Existem {$count} cards nesta coluna.")
                ->danger()
                ->send();
            return;
        }

        $column->delete();

        Notification::make()
            ->title('Coluna removida')
            ->success()
            ->send();
    }

    public function moveColumn(string $columnId, int $direction): void
    {
        $columns = $this->columns->values();
        $index = $columns->search(fn (BoardColumn $c) => (string) $c->id === (string) $columnId);
        if ($index === false) {
            return;
        }

        $toIndex = $direction < 0 ? $index - 1 : $index + 1;
        if ($toIndex < 0 || $toIndex >= $columns->count()) {
            return;
        }

        DB::transaction(function () use ($columns, $index, $toIndex) {
            $a = $columns[$index];
            $b = $columns[$toIndex];

            $posA = (int) $a->position;
            $posB = (int) $b->position;

            BoardColumn::query()->whereKey($a->id)->update(['position' => $posB]);
            BoardColumn::query()->whereKey($b->id)->update(['position' => $posA]);
        });
    }

    public function getTypeOptionsProperty(): array
    {
        return collect(IssueType::cases())->mapWithKeys(fn (IssueType $t) => [$t->value => $t->value])->all();
    }

    public function getEpicOptionsProperty(): array
    {
        return Issue::query()
            ->where('project_id', $this->record->project_id)
            ->where('type', IssueType::Epic->value)
            ->orderBy('issue_key')
            ->get(['id', 'issue_key', 'title'])
            ->mapWithKeys(fn (Issue $issue) => [$issue->id => "{$issue->issue_key} {$issue->title}"])
            ->all();
    }

    public function getAssigneeOptionsProperty(): array
    {
        $workspaceId = $this->record->project->workspace_id;

        return User::query()
            ->whereHas('workspaceUsers', fn (Builder $q) => $q->where('workspace_id', $workspaceId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getGroupsProperty(): array
    {
        if ($this->groupBy === 'epic') {
            $groups = $this->issues
                ->pluck('epic_id')
                ->unique()
                ->values()
                ->all();

            $options = $this->epicOptions;
            $result = [];

            $result[] = ['key' => 'none', 'label' => 'Sem Epic', 'value' => null];
            foreach ($groups as $epicId) {
                if (! $epicId) {
                    continue;
                }
                $result[] = ['key' => (string) $epicId, 'label' => $options[$epicId] ?? (string) $epicId, 'value' => (string) $epicId];
            }

            return $result;
        }

        if ($this->groupBy === 'assignee') {
            $groups = $this->issues
                ->pluck('assignee_id')
                ->unique()
                ->values()
                ->all();

            $options = $this->assigneeOptions;
            $result = [];

            $result[] = ['key' => 'none', 'label' => 'Unassigned', 'value' => null];
            foreach ($groups as $assigneeId) {
                if (! $assigneeId) {
                    continue;
                }
                $result[] = ['key' => (string) $assigneeId, 'label' => $options[$assigneeId] ?? (string) $assigneeId, 'value' => (string) $assigneeId];
            }

            return $result;
        }

        return [
            ['key' => 'all', 'label' => 'Todos', 'value' => null],
        ];
    }

    public function moveIssue(string $issueId, string $toColumnId, ?int $toIndex = null): void
    {
        $allowedColumnIds = $this->columns->pluck('id')->all();
        if (! in_array($toColumnId, $allowedColumnIds, true)) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($issueId, $toColumnId, $toIndex, $allowedColumnIds) {
                $issue = Issue::query()->whereKey($issueId)->lockForUpdate()->firstOrFail();

                if (! in_array($issue->board_column_id, $allowedColumnIds, true)) {
                    abort(403);
                }

                $fromColumnId = (string) $issue->board_column_id;

                $targetIds = Issue::query()
                    ->where('board_column_id', $toColumnId)
                    ->orderBy('position_in_column')
                    ->pluck('id')
                    ->all();

                $targetIds = array_values(array_filter($targetIds, fn (string $id) => $id !== $issueId));

                $insertAt = $toIndex === null ? count($targetIds) : max(0, min(count($targetIds), (int) $toIndex));
                array_splice($targetIds, $insertAt, 0, [$issueId]);

                $positions = $this->buildPositions($targetIds);
                $issue->board_column_id = $toColumnId;
                $issue->position_in_column = $positions[$issueId] ?? 1024;
                $issue->save();

                unset($positions[$issueId]);
                $this->applyPositions($positions);

                if ($fromColumnId !== $toColumnId) {
                    $fromIds = Issue::query()
                        ->where('board_column_id', $fromColumnId)
                        ->orderBy('position_in_column')
                        ->pluck('id')
                        ->all();
                    $this->applyPositions($this->buildPositions($fromIds));
                }
            });
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Não foi possível mover o card.';

            Notification::make()
                ->title((string) $message)
                ->danger()
                ->send();
        }
    }

    private function buildPositions(array $orderedIds): array
    {
        $positions = [];
        $pos = 1024;
        foreach ($orderedIds as $id) {
            $positions[(string) $id] = $pos;
            $pos += 1024;
        }

        return $positions;
    }

    private function applyPositions(array $positions): void
    {
        foreach ($positions as $id => $pos) {
            Issue::query()->whereKey($id)->update([
                'position_in_column' => (int) $pos,
            ]);
        }
    }
}
