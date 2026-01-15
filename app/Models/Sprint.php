<?php

namespace App\Models;

use App\Enums\SprintStatus;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sprint extends Model
{
    use SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['project_id', 'name', 'goal', 'start_date', 'end_date', 'status', 'position'];
    protected $casts = [
        'status' => SprintStatus::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->exists && $model->getOriginal('status') === SprintStatus::Closed->value && $model->status !== SprintStatus::Closed) {
                throw ValidationException::withMessages([
                    'status' => 'Sprint CLOSED não pode ser reaberta.',
                ]);
            }

            if ($model->status === SprintStatus::Active) {
                $hasOtherActive = self::query()
                    ->where('project_id', $model->project_id)
                    ->where('status', SprintStatus::Active->value)
                    ->when($model->exists, fn ($q) => $q->whereKeyNot($model->id))
                    ->exists();

                if ($hasOtherActive) {
                    throw ValidationException::withMessages([
                        'status' => 'Já existe uma sprint ACTIVE neste projeto.',
                    ]);
                }
            }
        });

        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
