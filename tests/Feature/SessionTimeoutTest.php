<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_session_regenerated_on_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $sessionId = session()->getId();

        $this->post('/logout');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotEquals($sessionId, session()->getId());
    }

    public function test_session_lifetime_is_configured(): void
    {
        $lifetime = config('session.lifetime');
        $this->assertNotNull($lifetime, 'session.lifetime config must be set');
        $this->assertIsNumeric($lifetime);
    }
}
