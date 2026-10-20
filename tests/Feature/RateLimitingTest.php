<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_throttle_blocks_after_60_requests(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user)->getJson('/api/user');
            $response->assertStatus(200);
        }

        $response = $this->actingAs($user)->getJson('/api/user');
        $response->assertStatus(429);
    }
}
