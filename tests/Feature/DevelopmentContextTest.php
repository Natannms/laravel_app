<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Enums\WorkspaceRole;
use App\Filament\Pages\Development\Backlog;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DevelopmentContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_clears_current_project_session(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($user)
            ->withSession(['current_project_id' => 'x'])
            ->get('/admin')
            ->assertOk()
            ->assertSessionMissing('current_project_id');
    }

    public function test_development_pages_redirect_when_no_project_selected(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($user)
            ->get('/admin/development/backlog')
            ->assertRedirect('/admin');
    }

    public function test_backlog_page_filters_by_current_project(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        WorkspaceUser::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);

        $p1 = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'P1', 'key' => 'P1X']);
        $p2 = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'P2', 'key' => 'P2X']);

        $i1 = Issue::query()->create([
            'project_id' => $p1->id,
            'board_column_id' => $p1->boards()->where('is_default', true)->firstOrFail()->columns()->orderBy('position')->value('id'),
            'type' => IssueType::Task->value,
            'title' => 'Only P1',
            'sprint_id' => null,
        ]);

        $i2 = Issue::query()->create([
            'project_id' => $p2->id,
            'board_column_id' => $p2->boards()->where('is_default', true)->firstOrFail()->columns()->orderBy('position')->value('id'),
            'type' => IssueType::Task->value,
            'title' => 'Only P2',
            'sprint_id' => null,
        ]);

        $this->withSession(['current_project_id' => (string) $p1->id]);

        Livewire::actingAs($user)->test(Backlog::class)
            ->assertSee($i1->issue_key)
            ->assertDontSee($i2->issue_key);
    }
}
