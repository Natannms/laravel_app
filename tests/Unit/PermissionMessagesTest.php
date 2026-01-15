<?php

namespace Tests\Unit;

use App\Support\PermissionMessages;
use Tests\TestCase;

class PermissionMessagesTest extends TestCase
{
    public function testReturnsMappedMessageWhenExists(): void
    {
        $this->assertSame(
            'Você não tem permissão para criar projetos.',
            PermissionMessages::for('projects.create'),
        );
    }

    public function testReturnsFallbackForUnknownKey(): void
    {
        $this->assertSame(
            'Fallback',
            PermissionMessages::for('unknown.permission', 'Fallback'),
        );
    }
}
