<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'blocked',
        'blocked_reason',
        'blocked_by_issue_id',
        'position_in_column',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'board_column_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }
}
