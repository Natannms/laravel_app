<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardColumn extends Model
{
    use SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['board_id', 'name', 'position', 'wip_limit', 'color', 'is_done'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class)->withTrashed();
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'board_column_id');
    }
}
