<?php

namespace App\Http\Controllers;

use App\Services\WorkspaceInvitationService;
use Illuminate\Http\Request;

class WorkspaceInviteController extends Controller
{
    public function consume(string $token)
    {
        $service = app(WorkspaceInvitationService::class);
        $invitation = $service->findValidByToken($token);

        if (! $invitation) {
            return redirect()->route('register')->withErrors([
                'invite' => 'Convite inválido ou expirado.',
            ]);
        }

        return redirect()->route('register', ['invite' => $token]);
    }
}
