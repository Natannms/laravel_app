<?php

namespace App\Models;

use App\Services\IssueActivityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IssueComment extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'issue_id',
        'user_id',
        'body',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });

        static::created(function (self $model) {
            app(IssueActivityService::class)->log(
                issueId: (string) $model->issue_id,
                action: 'COMMENT_CREATED',
                after: [
                    'comment_id' => (string) $model->id,
                ],
                userId: (string) $model->user_id,
            );
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
