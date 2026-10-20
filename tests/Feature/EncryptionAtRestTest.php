<?php

namespace Tests\Feature;

use Tests\TestCase;

class EncryptionAtRestTest extends TestCase
{
    public function test_app_key_is_configured(): void
    {
        $this->assertNotNull(config('app.key'));
        $this->assertStringStartsWith('base64:', config('app.key'));
    }
}
