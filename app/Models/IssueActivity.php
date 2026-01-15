<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IssueActivity extends Model
{
    use SoftDeletes;

    protected $table = 'issue_activity';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'user_id',
        'action',
        'before',
        'after',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }

            if (! $model->getAttribute('created_at')) {
                $model->setAttribute('created_at', now());
            }
        });
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}

