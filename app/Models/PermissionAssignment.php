<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAssignment extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'permission_id',
        'subject_type',
        'subject_id',
        'scope_type',
        'scope_id',
        'effect',
        'created_by_user_id',
    ];

    protected $casts = [
        'scope_id' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}

