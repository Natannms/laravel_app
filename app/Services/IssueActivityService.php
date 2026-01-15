<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueActivityService
{
    public function log(string $issueId, string $action, ?array $before = null, ?array $after = null, ?string $userId = null): void
    {
        $userId = $userId ?: Auth::id();
        if (! $userId) {
            return;
        }

        DB::table('issue_activity')->insert([
            'id' => (string) Str::uuid(),
            'issue_id' => $issueId,
            'user_id' => $userId,
            'action' => $action,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'created_at' => now(),
            'deleted_at' => null,
        ]);
    }
}

