<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key',
        'module',
        'name',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PermissionAssignment::class);
    }
}

