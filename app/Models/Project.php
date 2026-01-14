<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Services\ProjectSetupService;

class Project extends Model
{
    use SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['workspace_id', 'name', 'key'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }

            $workspaceId = $model->getAttribute('workspace_id');
            $name = (string) $model->getAttribute('name');
            if ($workspaceId && $name && ! $model->getAttribute('key')) {
                $model->setAttribute('key', self::generateUniqueKeyForWorkspace((string) $workspaceId, $name));
            }
        });

        static::created(function (self $model) {
            app(ProjectSetupService::class)->ensureDefaultBoardAndColumns($model);
        });
    }

    public static function previewKeyFromName(string $name): string
    {
        return self::keyBaseFromName($name) . '-1';
    }

    public static function generateUniqueKeyForWorkspace(string $workspaceId, string $name): string
    {
        $base = self::keyBaseFromName($name);

        $existing = self::query()
            ->withTrashed()
            ->where('workspace_id', $workspaceId)
            ->where('key', 'like', $base . '-%')
            ->pluck('key');

        $max = 0;
        foreach ($existing as $key) {
            if (! is_string($key)) {
                continue;
            }
            if (! Str::startsWith($key, $base . '-')) {
                continue;
            }
            $suffix = substr($key, strlen($base) + 1);
            if ($suffix !== '' && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $base . '-' . ($max + 1);
    }

    private static function keyBaseFromName(string $name): string
    {
        $ascii = Str::ascii(trim($name));
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?? $ascii;

        $parts = array_values(array_filter(explode(' ', $ascii), fn ($p) => $p !== ''));
        $letters = preg_replace('/[^A-Za-z]/', '', $ascii) ?? '';

        if (count($parts) >= 2) {
            $first = preg_replace('/[^A-Za-z]/', '', $parts[0]) ?? '';
            $last = preg_replace('/[^A-Za-z]/', '', $parts[count($parts) - 1]) ?? '';

            $base = strtoupper(substr($first, 0, 1) . substr($last, 0, 2));
        } else {
            $base = strtoupper(substr($letters, 0, 3));
        }

        $base = preg_replace('/[^A-Z]/', '', $base) ?? '';
        $base = str_pad(substr($base, 0, 3), 3, 'X');

        return $base;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
