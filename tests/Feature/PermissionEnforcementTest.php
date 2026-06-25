<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_gets_401(): void
    {
        $response = $this->getJson('/api/ecografias');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/ecografias');
        $response->assertStatus(403);
    }

    public function test_file_access_guest_gets_401(): void
    {
        $response = $this->getJson('/files/firmas_consntimientos/test.png');
        $response->assertStatus(401);
    }
}
