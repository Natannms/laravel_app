<?php

namespace App\Support;

class PermissionMessages
{
    public static function for(string $permissionKey, ?string $fallback = null): string
    {
        $messages = config('permission_messages.keys', []);
        if (is_array($messages) && array_key_exists($permissionKey, $messages)) {
            return (string) $messages[$permissionKey];
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return (string) config('permission_messages.default', 'Você não tem permissão para realizar esta ação.');
    }
}

