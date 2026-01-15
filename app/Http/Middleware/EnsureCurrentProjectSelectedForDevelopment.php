<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentProjectSelectedForDevelopment
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = session('current_project_id');
        if (! $projectId) {
            return redirect()->to(route('filament.admin.pages.dashboard'));
        }

        $userId = Auth::id();
        if (! $userId) {
            return redirect()->to(route('filament.admin.pages.dashboard'));
        }

        $allowed = Project::query()
            ->whereKey($projectId)
            ->whereHas('workspace.workspaceUsers', fn (Builder $q) => $q->where('user_id', $userId))
            ->exists();

        if (! $allowed) {
            session()->forget('current_project_id');

            return redirect()->to(route('filament.admin.pages.dashboard'));
        }

        return $next($request);
    }
}

