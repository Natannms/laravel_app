<?php

namespace App\Models;

use App\Services\IssueActivityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IssueAttachment extends Model
{
    use SoftDeletes;

    protected $table = 'issue_attachments';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'user_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'created_at',
    ];

    protected $casts = [
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

        static::created(function (self $model) {
            app(IssueActivityService::class)->log(
                issueId: (string) $model->issue_id,
                action: 'ATTACHMENT_ADDED',
                after: [
                    'attachment_id' => (string) $model->id,
                    'file_name' => $model->file_name,
                    'file_path' => $model->file_path,
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

    public function getUrlAttribute(): ?string
    {
        $path = (string) $this->file_path;
        if ($path === '') {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
