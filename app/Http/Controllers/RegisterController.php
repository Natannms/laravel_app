<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function create(Request $request)
    {
        $inviteToken = $request->query('invite');
        $invitation = null;

        if (is_string($inviteToken) && $inviteToken !== '') {
            $invitation = app(WorkspaceInvitationService::class)->findValidByToken($inviteToken);
        }

        return view('auth.register', [
            'invitation' => $invitation,
            'inviteToken' => is_string($inviteToken) ? $inviteToken : null,
        ]);
    }

    public function store(Request $request)
    {
        $inviteToken = $request->query('invite');
        $invitation = null;

        if (is_string($inviteToken) && $inviteToken !== '') {
            $invitation = app(WorkspaceInvitationService::class)->findValidByToken($inviteToken);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if (! $invitation) {
            $rules['workspace_name'] = ['required', 'string', 'max:255'];
        }

        $data = $request->validate($rules);

        if ($invitation) {
            $invitedEmail = mb_strtolower(trim((string) $invitation->email));
            if ($invitedEmail !== '' && mb_strtolower(trim($data['email'])) !== $invitedEmail) {
                return back()->withErrors([
                    'email' => 'Esse convite é para outro email.',
                ])->withInput();
            }
        }

        $user = null;

        DB::transaction(function () use (&$user, $data, $invitation) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            if ($invitation) {
                WorkspaceUser::query()->create([
                    'workspace_id' => $invitation->workspace_id,
                    'user_id' => $user->id,
                    'role' => $invitation->role->value,
                ]);

                $invitation->used_at = now();
                $invitation->accepted_user_id = $user->id;
                $invitation->save();
            } else {
                $workspaceSlug = $this->uniqueWorkspaceSlug($data['workspace_name']);

                $workspace = Workspace::query()->create([
                    'name' => $data['workspace_name'],
                    'slug' => $workspaceSlug,
                ]);

                WorkspaceUser::query()->create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $user->id,
                    'role' => WorkspaceRole::Owner->value,
                ]);
            }
        });

        Auth::login($user);

        return redirect('/admin');
    }

    private function uniqueWorkspaceSlug(string $workspaceName): string
    {
        $base = Str::slug($workspaceName);
        if ($base === '') {
            $base = 'workspace';
        }

        $slug = $base;
        $i = 2;
        while (Workspace::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
