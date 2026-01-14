<?php

namespace App\Providers;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\WorkspaceUser;
use App\Policies\BoardColumnPolicy;
use App\Policies\BoardPolicy;
use App\Policies\IssuePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SprintPolicy;
use App\Policies\WorkspaceUserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Sprint::class => SprintPolicy::class,
        Issue::class => IssuePolicy::class,
        Board::class => BoardPolicy::class,
        BoardColumn::class => BoardColumnPolicy::class,
        WorkspaceUser::class => WorkspaceUserPolicy::class,
    ];
}

