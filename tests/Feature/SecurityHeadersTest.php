<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_csp_header_is_present(): void
    {
        $response = $this->get('/');
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_hsts_on_https(): void
    {
        $response = $this->get('/', ['HTTPS' => true]);
        $this->assertNotNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_permissions_policy(): void
    {
        $response = $this->get('/');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }
}
