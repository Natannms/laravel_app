<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'workspace_name' => ['required', 'string', 'max:255'],
        ]);

        $user = null;

        DB::transaction(function () use (&$user, $data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

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

