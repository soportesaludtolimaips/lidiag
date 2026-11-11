<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/force-password', function () {
            return response()->json(['message' => 'ok']);
        })->middleware(['auth', 'force-password-change']);
    }

    public function test_user_without_password_change_gets_blocked(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/_test/force-password');

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    public function test_user_with_recent_password_change_gets_through(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/_test/force-password');

        $response->assertStatus(200);
    }
}
