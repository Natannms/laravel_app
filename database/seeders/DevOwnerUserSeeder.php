<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Database\Seeder;

class DevOwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $email = (string) env('DEV_SEED_OWNER_EMAIL', '');
        $password = (string) env('DEV_SEED_OWNER_PASSWORD', '');

        if ($email === '' || $password === '') {
            return;
        }

        $name = (string) env('DEV_SEED_OWNER_NAME', 'Owner');
        $workspaceSlug = (string) env('DEV_SEED_OWNER_WORKSPACE_SLUG', 'default');
        $workspaceName = (string) env('DEV_SEED_OWNER_WORKSPACE_NAME', 'Default Workspace');
        $role = WorkspaceRole::tryFrom((string) env('DEV_SEED_OWNER_ROLE', WorkspaceRole::Owner->value)) ?? WorkspaceRole::Owner;

        $workspace = Workspace::query()->withTrashed()->firstOrNew(['slug' => $workspaceSlug]);
        $workspace->forceFill([
            'name' => $workspaceName,
            'slug' => $workspaceSlug,
            'deleted_at' => null,
        ])->save();

        $user = User::query()->withTrashed()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'deleted_at' => null,
        ])->save();

        WorkspaceUser::query()->withTrashed()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $user->id],
            ['role' => $role->value, 'deleted_at' => null],
        );
    }
}

