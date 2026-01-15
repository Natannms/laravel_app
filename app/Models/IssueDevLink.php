<?php

namespace App\Models;

use App\Services\IssueActivityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IssueDevLink extends Model
{
    use SoftDeletes;

    protected $table = 'issue_dev_links';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'issue_id',
        'repository_id',
        'branch_name',
        'pr_mr_url',
        'pr_mr_id',
        'commit_sha',
        'link_type',
        'status',
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
                action: 'DEV_LINKED',
                after: [
                    'dev_link_id' => (string) $model->id,
                    'repository_id' => (string) $model->repository_id,
                    'branch_name' => $model->branch_name,
                    'pr_mr_id' => $model->pr_mr_id,
                    'commit_sha' => $model->commit_sha,
                ],
            );
        });
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class)->withTrashed();
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class)->withTrashed();
    }
}
