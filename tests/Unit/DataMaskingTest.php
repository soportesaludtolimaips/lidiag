<?php

namespace Tests\Unit;

use Tests\TestCase;

class DataMaskingTest extends TestCase
{
    public function test_masks_document_number(): void
    {
        $this->assertEquals('******7123', maskData('1234567123', 'document'));
    }

    public function test_masks_phone(): void
    {
        $this->assertEquals('******7890', maskData('1234567890', 'phone'));
    }

    public function test_masks_address(): void
    {
        $this->assertStringContainsString('***', maskData('Calle 123 # 45-67', 'address'));
    }

    public function test_masks_email(): void
    {
        $result = maskData('juan@example.com', 'email');
        $this->assertStringContainsString('***', $result);
        $this->assertStringContainsString('@example.com', $result);
    }

    public function test_masks_dob(): void
    {
        $this->assertEquals('1990-**-**', maskData('1990-05-15', 'dob'));
    }

    public function test_returns_original_for_unknown_type(): void
    {
        $this->assertEquals('hello', maskData('hello', 'unknown'));
    }

    public function test_short_document_not_masked(): void
    {
        $this->assertEquals('12', maskData('12', 'document'));
    }
}
