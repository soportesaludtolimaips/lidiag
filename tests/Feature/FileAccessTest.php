<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAccessTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_guest_cannot_access_protected_files(): void
    {
        $response = $this->getJson('/files/firmas_consntimientos/test.png');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/files/firmas_consntimientos/test.png');

        $response->assertForbidden();
    }

    public function test_user_with_permission_can_access(): void
    {
        Storage::fake('firmas_consntimientos');
        Storage::disk('firmas_consntimientos')
            ->put('test.png', 'fake-png-content');

        $user = User::factory()->create();
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'ecografias.consentimientos.show',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->getJson('/files/firmas_consntimientos/test.png');

        $response->assertOk();
        $response->assertHeader('Content-Type');
    }
}
