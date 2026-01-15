<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceUser extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['workspace_id', 'user_id', 'role'];
    protected $casts = [
        'role' => WorkspaceRole::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(WorkspaceGroup::class, 'workspace_group_members', 'workspace_user_id', 'workspace_group_id');
    }
}
