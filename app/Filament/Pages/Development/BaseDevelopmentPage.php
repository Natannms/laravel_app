<?php

namespace App\Filament\Pages\Development;

use App\Models\Project;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class BaseDevelopmentPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string | array $routeMiddleware = [
        \App\Http\Middleware\EnsureCurrentProjectSelectedForDevelopment::class,
    ];

    public Project $project;

    public function boot(): void
    {
        $this->project = Project::query()
            ->with('workspace')
            ->whereKey((string) session('current_project_id'))
            ->firstOrFail();
    }
}
