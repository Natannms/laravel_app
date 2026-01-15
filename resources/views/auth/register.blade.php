<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Registrar</title>
    </head>
    <body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 40px; max-width: 520px;">
        <h1 style="margin: 0 0 16px;">Criar conta</h1>

        @if ($invitation)
            <div style="background: #dbeafe; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-weight: 600; margin-bottom: 4px;">Convite detectado</div>
                <div style="font-size: 14px;">
                    Workspace: {{ $invitation->workspace->name }}<br>
                    Role: {{ $invitation->role->value }}<br>
                    Expira em: {{ $invitation->expires_at->format('d/m/Y H:i') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div style="background: #fee2e2; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $inviteToken ? route('register.store', ['invite' => $inviteToken]) : route('register.store') }}">
            @csrf

            <label style="display:block; margin-bottom: 6px;">Nome</label>
            <input name="name" value="{{ old('name') }}" required style="width: 100%; padding: 10px; margin-bottom: 12px;">

            <label style="display:block; margin-bottom: 6px;">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required style="width: 100%; padding: 10px; margin-bottom: 12px;">

            @if (! $invitation)
                <label style="display:block; margin-bottom: 6px;">Nome do workspace</label>
                <input name="workspace_name" value="{{ old('workspace_name', 'Meu Workspace') }}" required style="width: 100%; padding: 10px; margin-bottom: 12px;">
            @endif

            <label style="display:block; margin-bottom: 6px;">Senha</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; margin-bottom: 12px;">

            <label style="display:block; margin-bottom: 6px;">Confirmar senha</label>
            <input type="password" name="password_confirmation" required style="width: 100%; padding: 10px; margin-bottom: 18px;">

            <button type="submit" style="padding: 10px 14px; border-radius: 8px; border: 0; background: #111827; color: white; width: 100%;">
                Criar e entrar no admin
            </button>
        </form>
    </body>
</html>
