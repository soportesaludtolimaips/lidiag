### Task 3: Data Masking Helper

**Context:** Patient personal data (document number, phone, address, email, date of birth) is displayed in full in API responses. ISO 27001 requires data minimization — show masked data in list views.

**Files:**
- Create: `app/Helpers/security.php`
- Modify: `composer.json` (autoload.files)
- Modify: `app/Http/Controllers/Interface/SAHIEcografiasController.php`
- Modify: `app/Http/Controllers/Ecografias/EcografiaController.php`
- Modify: `app/Http/Controllers/Ecografias/ecografiaLecturaController.php`
- Create: `tests/Unit/DataMaskingTest.php`

**Steps:**

1. **Create helper function** in `app/Helpers/security.php`:
```php
<?php

if (!function_exists('maskData')) {
    function maskData(string $value, string $type): string
    {
        return match ($type) {
            'document' => strlen($value) > 4
                ? str_repeat('*', strlen($value) - 4) . substr($value, -4)
                : $value,
            'phone' => strlen($value) > 4
                ? str_repeat('*', strlen($value) - 4) . substr($value, -4)
                : $value,
            'address' => preg_replace('/\d+/', '***', $value) ?: $value,
            'email' => preg_replace('/(.?)(.?)(.?)(@.*)/', '***$2$3$4', $value) ?: $value,
            'dob' => strlen($value) >= 4
                ? substr($value, 0, 4) . '-**-**'
                : $value,
            default => $value,
        };
    }
}
```

2. **Register autoload** in `composer.json`:
Add to `autoload.files` array: `"app/Helpers/security.php"`

3. **Write tests** in `tests/Unit/DataMaskingTest.php`:
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class DataMaskingTest extends TestCase
{
    public function test_masks_document_number(): void
    {
        $this->assertEquals('*******123', maskData('1234567123', 'document'));
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
```

4. **Apply to SAHIEcografiasController::listarProductosAtencion()**:
After the collection is built (after `consultaUnionSahi()` and enrichment), before returning the response, mask patient fields:
```php
$atenciones = $atenciones->map(function ($item) {
    $item->num_docu = maskData($item->num_docu ?? '', 'document');
    $item->direccion = maskData($item->direccion ?? '', 'address');
    $item->tel = maskData($item->tel ?? '', 'phone');
    $item->cel = maskData($item->cel ?? '', 'phone');
    $item->email = maskData($item->email ?? '', 'email');
    $item->fec_naci = maskData($item->fec_naci ?? '', 'dob');
    return $item;
});
```

5. **Apply to EcografiaController::index()**:
Before returning the response, mask patient data:
```php
$ecografias->transform(function ($eco) {
    if ($eco->relationLoaded('paciente') && $eco->paciente) {
        $eco->paciente->num_docu = maskData($eco->paciente->num_docu ?? '', 'document');
        $eco->paciente->direccion = maskData($eco->paciente->direccion ?? '', 'address');
        $eco->paciente->tel = maskData($eco->paciente->tel ?? '', 'phone');
        $eco->paciente->cel = maskData($eco->paciente->cel ?? '', 'phone');
    }
    return $eco;
});
```

6. **Apply to ecografiaLecturaController::listarPendientesPorLectura()**:
Mask patient fields before returning:
```php
$ecografias->transform(function ($eco) {
    if (isset($eco->num_docu)) {
        $eco->num_docu = maskData($eco->num_docu, 'document');
    }
    if (isset($eco->direccion)) {
        $eco->direccion = maskData($eco->direccion, 'address');
    }
    return $eco;
});
```

7. **Run `composer dump-autoload`** after modifying composer.json

8. **Run tests** and commit.
