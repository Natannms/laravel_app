<?php

namespace App\Models;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\IssueActivity;
use App\Models\IssueAttachment;
use App\Models\IssueComment;
use App\Models\IssueDevLink;
use App\Services\IssueActivityService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Issue extends Model
{
    use SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'project_id',
        'board_column_id',
        'sprint_id',
        'parent_id',
        'epic_id',
        'assignee_id',
        'reporter_id',
        'issue_key',
        'type',
        'title',
        'description',
        'estimate_hours',
        'coffee_breaks',
        'blocked',
        'blocked_reason',
        'blocked_by_issue_id',
        'position_in_column',
    ];
    protected $casts = [
        'type' => IssueType::class,
        'blocked' => 'boolean',
        'estimate_hours' => 'decimal:2',
        'coffee_breaks' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->validateHierarchy();
            $model->validateSprintRules();
            $model->validateBoardRules();
        });

        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }

            if (! $model->getAttribute('issue_key')) {
                $projectId = (string) $model->getAttribute('project_id');
                $project = Project::query()->withTrashed()->whereKey($projectId)->first();
                if ($project) {
                    $model->setAttribute('issue_key', self::generateUniqueIssueKeyForProject($projectId, $project->key));
                }
            }
        });

        static::created(function (self $model) {
            app(IssueActivityService::class)->log(
                issueId: (string) $model->id,
                action: 'CREATED',
                after: [
                    'issue_key' => $model->issue_key,
                    'project_id' => $model->project_id,
                    'type' => $model->type?->value,
                    'title' => $model->title,
                ],
            );
        });

        static::updated(function (self $model) {
            if ($model->wasChanged('board_column_id') || $model->wasChanged('position_in_column')) {
                $before = [
                    'board_column_id' => $model->getOriginal('board_column_id'),
                    'position_in_column' => $model->getOriginal('position_in_column'),
                ];
                $after = [
                    'board_column_id' => $model->board_column_id,
                    'position_in_column' => $model->position_in_column,
                ];

                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'MOVED_COLUMN',
                    before: $before,
                    after: $after,
                );
            }

            if ($model->wasChanged('sprint_id')) {
                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'MOVED_SPRINT',
                    before: [
                        'sprint_id' => $model->getOriginal('sprint_id'),
                    ],
                    after: [
                        'sprint_id' => $model->sprint_id,
                    ],
                );
            }

            if ($model->wasChanged('blocked')) {
                $action = $model->blocked ? 'BLOCKED' : 'UNBLOCKED';

                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: $action,
                    before: [
                        'blocked' => (bool) $model->getOriginal('blocked'),
                        'blocked_reason' => $model->getOriginal('blocked_reason'),
                        'blocked_by_issue_id' => $model->getOriginal('blocked_by_issue_id'),
                    ],
                    after: [
                        'blocked' => (bool) $model->blocked,
                        'blocked_reason' => $model->blocked_reason,
                        'blocked_by_issue_id' => $model->blocked_by_issue_id,
                    ],
                );
            }

            if ($model->wasChanged('estimate_hours')) {
                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'ESTIMATE_CHANGED',
                    before: [
                        'estimate_hours' => $model->getOriginal('estimate_hours'),
                    ],
                    after: [
                        'estimate_hours' => $model->estimate_hours,
                    ],
                );
            }

            if ($model->wasChanged('assignee_id')) {
                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'ASSIGNEE_CHANGED',
                    before: [
                        'assignee_id' => $model->getOriginal('assignee_id'),
                    ],
                    after: [
                        'assignee_id' => $model->assignee_id,
                    ],
                );
            }

            if ($model->wasChanged('epic_id')) {
                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'EPIC_CHANGED',
                    before: [
                        'epic_id' => $model->getOriginal('epic_id'),
                    ],
                    after: [
                        'epic_id' => $model->epic_id,
                    ],
                );
            }

            if ($model->wasChanged('parent_id')) {
                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'PARENT_CHANGED',
                    before: [
                        'parent_id' => $model->getOriginal('parent_id'),
                    ],
                    after: [
                        'parent_id' => $model->parent_id,
                    ],
                );
            }

            $handled = [
                'board_column_id' => true,
                'position_in_column' => true,
                'sprint_id' => true,
                'blocked' => true,
                'estimate_hours' => true,
                'assignee_id' => true,
                'epic_id' => true,
                'parent_id' => true,
                'updated_at' => true,
            ];

            $dirtyKeys = array_keys($model->getChanges());
            $other = array_values(array_filter($dirtyKeys, fn (string $k) => ! isset($handled[$k])));

            if ($other !== []) {
                $before = [];
                $after = [];
                foreach ($other as $k) {
                    $before[$k] = $model->getOriginal($k);
                    $after[$k] = $model->getAttribute($k);
                }

                app(IssueActivityService::class)->log(
                    issueId: (string) $model->id,
                    action: 'UPDATED_FIELDS',
                    before: $before,
                    after: $after,
                );
            }

            if ($model->type === IssueType::Epic && $model->wasChanged('sprint_id')) {
                $before = ['sprint_id' => $model->getOriginal('sprint_id')];

                DB::transaction(function () use ($model, $before) {
                    $count = self::query()
                        ->where('epic_id', $model->id)
                        ->update([
                            'sprint_id' => $model->sprint_id,
                            'updated_at' => now(),
                        ]);

                    app(IssueActivityService::class)->log(
                        issueId: (string) $model->id,
                        action: 'EPIC_MOVED_WITH_CHILDREN',
                        before: $before,
                        after: [
                            'sprint_id' => $model->sprint_id,
                            'children_updated' => $count,
                        ],
                    );
                });
            }
        });

        static::saved(function (self $model) {
            $assigneeId = DB::table('issue_assignees')
                ->where('issue_id', $model->id)
                ->orderBy('created_at')
                ->value('user_id');

            $assigneeId = $assigneeId ? (string) $assigneeId : null;

            if ((string) ($model->assignee_id ?? '') === (string) ($assigneeId ?? '')) {
                return;
            }

            $model->forceFill(['assignee_id' => $assigneeId])->saveQuietly();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'board_column_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(self::class, 'epic_id')->withTrashed();
    }

    public function epicChildren(): HasMany
    {
        return $this->hasMany(self::class, 'epic_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id')->withTrashed();
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'issue_assignees', 'issue_id', 'user_id')->withTrashed();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }

    public function blockedByIssue(): BelongsTo
    {
        return $this->belongsTo(self::class, 'blocked_by_issue_id')->withTrashed();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IssueAttachment::class);
    }

    public function devLinks(): HasMany
    {
        return $this->hasMany(IssueDevLink::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(IssueActivity::class);
    }

    public static function generateUniqueIssueKeyForProject(string $projectId, string $projectKey): string
    {
        $prefix = (string) $projectKey;
        $start = strlen($prefix) + 2;

        $max = (int) (self::query()
            ->where('project_id', $projectId)
            ->where('issue_key', 'like', $prefix . '-%')
            ->selectRaw('MAX(CAST(SUBSTRING(issue_key, ?) AS UNSIGNED)) as max_num', [$start])
            ->value('max_num') ?? 0);

        return $prefix . '-' . ($max + 1);
    }

    private function validateHierarchy(): void
    {
        $type = $this->type instanceof IssueType ? $this->type : IssueType::tryFrom((string) $this->getAttribute('type'));

        if (! $type) {
            throw ValidationException::withMessages([
                'type' => 'Tipo inválido.',
            ]);
        }

        $projectId = (string) $this->getAttribute('project_id');
        $parentId = $this->getAttribute('parent_id');
        $epicId = $this->getAttribute('epic_id');

        if ($type === IssueType::Subtask) {
            if (! $parentId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'SUBTASK exige parent.',
                ]);
            }
        } else {
            if ($parentId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Somente SUBTASK pode ter parent.',
                ]);
            }
        }

        if ($type === IssueType::Epic) {
            if ($parentId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'EPIC não pode ter parent.',
                ]);
            }
            if ($epicId) {
                throw ValidationException::withMessages([
                    'epic_id' => 'EPIC não pode pertencer a outro epic.',
                ]);
            }
        }

        if ($epicId) {
            $epic = self::query()->withTrashed()->whereKey($epicId)->first();
            if (! $epic) {
                throw ValidationException::withMessages([
                    'epic_id' => 'Epic não encontrado.',
                ]);
            }
            if ((string) $epic->project_id !== $projectId) {
                throw ValidationException::withMessages([
                    'epic_id' => 'Epic deve ser do mesmo projeto.',
                ]);
            }
            if ($epic->type !== IssueType::Epic) {
                throw ValidationException::withMessages([
                    'epic_id' => 'A issue selecionada não é um EPIC.',
                ]);
            }
        }

        if ($parentId) {
            $parent = self::query()->withTrashed()->whereKey($parentId)->first();
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent não encontrado.',
                ]);
            }
            if ((string) $parent->project_id !== $projectId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent deve ser do mesmo projeto.',
                ]);
            }
            if ($parent->type === IssueType::Subtask) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent não pode ser SUBTASK.',
                ]);
            }

            if ($this->exists) {
                $visited = [];
                $current = $parent;
                for ($i = 0; $i < 50 && $current; $i++) {
                    $currentId = (string) $current->id;
                    if ($currentId === (string) $this->id) {
                        throw ValidationException::withMessages([
                            'parent_id' => 'Ciclo detectado na hierarquia de parent.',
                        ]);
                    }
                    if (isset($visited[$currentId])) {
                        break;
                    }
                    $visited[$currentId] = true;

                    $nextId = $current->parent_id;
                    if (! $nextId) {
                        break;
                    }
                    $current = self::query()->withTrashed()->whereKey($nextId)->first();
                }
            }
        }
    }

    private function validateSprintRules(): void
    {
        if (! $this->isDirty('sprint_id')) {
            return;
        }

        $sprintId = $this->getAttribute('sprint_id');
        if (! $sprintId) {
            return;
        }

        $sprint = Sprint::query()->withTrashed()->whereKey($sprintId)->first();
        if (! $sprint) {
            throw ValidationException::withMessages([
                'sprint_id' => 'Sprint não encontrada.',
            ]);
        }

        if ((string) $sprint->project_id !== (string) $this->getAttribute('project_id')) {
            throw ValidationException::withMessages([
                'sprint_id' => 'Sprint deve ser do mesmo projeto.',
            ]);
        }

        if ($sprint->status === SprintStatus::Closed) {
            throw ValidationException::withMessages([
                'sprint_id' => 'Não é permitido adicionar/mover issue para uma sprint CLOSED.',
            ]);
        }
    }

    private function validateBoardRules(): void
    {
        $columnId = $this->getAttribute('board_column_id');
        if (! $columnId) {
            return;
        }

        $column = BoardColumn::query()->withTrashed()->whereKey($columnId)->first();
        if (! $column) {
            throw ValidationException::withMessages([
                'board_column_id' => 'Coluna não encontrada.',
            ]);
        }

        $projectId = (string) $this->getAttribute('project_id');
        if ($projectId !== '') {
            $isFromProject = $column->board()->where('project_id', $projectId)->exists();
            if (! $isFromProject) {
                throw ValidationException::withMessages([
                    'board_column_id' => 'Coluna deve ser do mesmo projeto.',
                ]);
            }
        }

        if ($this->blocked && (bool) $column->is_done) {
            throw ValidationException::withMessages([
                'board_column_id' => 'Issue bloqueada não pode ir para DONE.',
            ]);
        }

        if ($this->isDirty('board_column_id') && $column->wip_limit !== null) {
            $currentCount = self::query()
                ->where('board_column_id', $column->id)
                ->whereKeyNot($this->id ?? '00000000-0000-0000-0000-000000000000')
                ->count();

            if ($currentCount >= (int) $column->wip_limit) {
                throw ValidationException::withMessages([
                    'board_column_id' => 'WIP limit atingido para essa coluna.',
                ]);
            }
        }
    }
}
