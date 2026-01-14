<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectKeyGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_key_is_generated_from_name_and_unique_per_workspace(): void
    {
        $w1 = Workspace::query()->create(['name' => 'W1', 'slug' => 'w1']);
        $w2 = Workspace::query()->create(['name' => 'W2', 'slug' => 'w2']);

        $p1 = Project::query()->create([
            'workspace_id' => $w1->id,
            'name' => 'Rodrigo Sartory',
        ]);
        $this->assertSame('RSA-1', $p1->key);

        $p2 = Project::query()->create([
            'workspace_id' => $w1->id,
            'name' => 'Rodrigo Sartory',
        ]);
        $this->assertSame('RSA-2', $p2->key);

        $p3 = Project::query()->create([
            'workspace_id' => $w2->id,
            'name' => 'Rodrigo Sartory',
        ]);
        $this->assertSame('RSA-1', $p3->key);
    }
}

