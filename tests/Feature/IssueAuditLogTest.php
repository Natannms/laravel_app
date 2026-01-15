<?php

namespace Tests\Feature;

use App\Enums\IssueType;
use App\Enums\SprintStatus;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\IssueAttachment;
use App\Models\IssueComment;
use App\Models\IssueDevLink;
use App\Models\Project;
use App\Models\Repository;
use App\Models\Sprint;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IssueAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_activity_logs_expected_actions(): void
    {
        $user = User::query()->create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $workspace = Workspace::query()->create(['name' => 'W', 'slug' => 'w']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Main', 'key' => 'PRJ']);

        $todoId = BoardColumn::query()
            ->whereHas('board', fn ($q) => $q->where('project_id', $project->id))
            ->where('name', 'To Do')
            ->value('id');

        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todoId,
            'type' => IssueType::Story->value,
            'title' => 'Story',
        ]);

        $sprint = Sprint::query()->create([
            'project_id' => $project->id,
            'name' => 'Sprint',
            'status' => SprintStatus::Planned->value,
            'position' => 1,
        ]);

        $epic = Issue::query()->create([
            'project_id' => $project->id,
            'board_column_id' => $todoId,
            'type' => IssueType::Epic->value,
            'title' => 'Epic',
        ]);

        $assignee = User::query()->create([
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => 'secret123',
        ]);

        $issue->estimate_hours = 3.5;
        $issue->assignee_id = $assignee->id;
        $issue->epic_id = $epic->id;
        $issue->sprint_id = $sprint->id;
        $issue->blocked = true;
        $issue->blocked_reason = 'Waiting';
        $issue->save();

        IssueComment::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'body' => 'Comment',
        ]);

        IssueAttachment::query()->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'file_path' => 'issues/PRJ-1/readme.txt',
            'file_name' => 'readme.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
        ]);

        $repo = Repository::query()->create([
            'project_id' => $project->id,
            'provider' => 'github',
            'external_id' => '1',
            'full_name' => 'org/repo',
            'is_private' => true,
        ]);

        IssueDevLink::query()->create([
            'issue_id' => $issue->id,
            'repository_id' => $repo->id,
            'branch_name' => 'feat/demo',
            'link_type' => 'MANUAL',
            'status' => 'OPEN',
        ]);

        $issue->blocked = false;
        $issue->blocked_reason = null;
        $issue->save();

        $actions = DB::table('issue_activity')->where('issue_id', $issue->id)->pluck('action')->all();

        $this->assertContains('CREATED', $actions);
        $this->assertContains('ESTIMATE_CHANGED', $actions);
        $this->assertContains('ASSIGNEE_CHANGED', $actions);
        $this->assertContains('EPIC_CHANGED', $actions);
        $this->assertContains('MOVED_SPRINT', $actions);
        $this->assertContains('BLOCKED', $actions);
        $this->assertContains('UNBLOCKED', $actions);
        $this->assertContains('COMMENT_CREATED', $actions);
        $this->assertContains('ATTACHMENT_ADDED', $actions);
        $this->assertContains('DEV_LINKED', $actions);
    }
}

