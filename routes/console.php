<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:create {email?} {--name=} {--workspace=default} {--role=OWNER}', function () {
    $email = $this->argument('email') ?? $this->ask('Email');
    $name = $this->option('name') ?: $this->ask('Nome');
    $workspaceSlug = (string) $this->option('workspace');
    $roleValue = (string) $this->option('role');
    $role = WorkspaceRole::tryFrom($roleValue);

    if (! $role) {
        $this->error("Role inválida: {$roleValue}");
        return 1;
    }

    $password = $this->secret('Senha');
    if (! is_string($password) || $password === '') {
        $this->error('Senha obrigatória.');
        return 1;
    }

    $user = User::query()->firstOrCreate(
        ['email' => $email],
        ['name' => $name, 'password' => $password],
    );

    if (! $user->wasRecentlyCreated) {
        $user->forceFill([
            'name' => $name,
            'password' => $password,
        ])->save();
    }

    $workspace = Workspace::query()->where('slug', $workspaceSlug)->first();
    if (! $workspace) {
        $this->warn("Workspace não encontrado para slug={$workspaceSlug}. Usuário criado/atualizado sem vínculo.");
        $this->info("OK: {$user->email}");
        return 0;
    }

    WorkspaceUser::query()->withTrashed()->updateOrCreate(
        ['workspace_id' => $workspace->id, 'user_id' => $user->id],
        ['role' => $role->value, 'deleted_at' => null],
    );

    $this->info("OK: {$user->email} no workspace {$workspace->slug} como {$role->value}");
    return 0;
})->purpose('Cria/atualiza usuário e vincula ao workspace');
