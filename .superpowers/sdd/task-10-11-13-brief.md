### Task 10: Auth Hardening
**Files:**
- Create: `app/Rules/StrongPassword.php`
- Create: `app/Http/Middleware/ForcePasswordChange.php`
- Create: `database/migrations/2026_06_24_000003_add_password_changed_at_to_users.php`
- Create: `database/migrations/2026_06_24_000004_create_two_factor_authentications_table.php`
- Modify: `app/Http/Kernel.php`
- Modify: `app/Models/User.php`

**StrongPassword rule:**
```php
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 8) { $fail('Min 8 caracteres.'); return; }
        if (!preg_match('/[A-Z]/', $value)) { $fail('Requiere mayúscula.'); return; }
        if (!preg_match('/[0-9]/', $value)) { $fail('Requiere número.'); return; }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) { $fail('Requiere especial.'); }
    }
}
```

**ForcePasswordChange middleware:**
```php
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->password_changed_at === null) {
            return response()->json(['success' => false, 'message' => 'Debe cambiar su contraseña.'], 403);
        }
        return $next($request);
    }
}
```

**Migration 1:** Add `$table->timestamp('password_changed_at')->nullable()->after('password');` to users table
**Migration 2:** Create `two_factor_authentications` with id, user_id (FK cascade), secret (nullable string), recovery_codes (nullable text), confirmed_at (nullable timestamp), timestamps, unique on user_id.

**Kernel.php:** Register `'force-password-change' => \App\Http\Middleware\ForcePasswordChange::class` in `$routeMiddleware`.
**User.php:** Add `'password_changed_at'` to `$fillable`.

### Task 11: Security Fixes (RCE + Uploads + SyncDicom)

**Files:**
- Modify: `app/Http/Controllers/Ecografias/PlantillaEcografiaController.php`
- Modify: `app/Http/Controllers/Ecografias/EcografiaConsentimientosController.php`
- Modify: `app/Console/Commands/SyncDicomEcografias.php`

**1. Fix RCE in PlantillaEcografiaController::convertDocxToPdf():**
Add use statements: `use Symfony\Component\Process\Process; use Symfony\Component\Process\Exception\ProcessFailedException;`
Replace shell execution with Process:
```php
$filename = pathinfo($nomPlantilla, PATHINFO_FILENAME);
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    throw new \InvalidArgumentException('Nombre de archivo inválido');
}
$unoconv = PHP_OS_FAMILY === 'Windows' ? 'C:\unoconv\unoconv.bat' : '/usr/bin/unoconv';
$process = new Process([$unoconv, '-f', 'pdf', '-o', $outputPath, $inputPath]);
$process->setTimeout(60);
$process->run();
if (!$process->isSuccessful()) {
    Log::error('unoconv error: ' . $process->getErrorOutput());
    throw new ProcessFailedException($process);
}
```

**2. Fix upload validation in firmarPacientePorToken():**
Add at the beginning of the method (before existing logic):
```php
$dataUrl = $request->input('firma');
if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/png;base64,')) {
    return response()->json(['success' => false, 'message' => 'Formato inválido. Solo PNG.'], 422);
}
$base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
$decoded = base64_decode($base64, true);
if ($decoded === false || strlen($decoded) > 2 * 1024 * 1024) {
    return response()->json(['success' => false, 'message' => 'Firma inválida o demasiado grande.'], 422);
}
$img = @imagecreatefromstring($decoded);
if (!$img || imagesx($img) > 2048 || imagesy($img) > 2048) {
    imagedestroy($img);
    return response()->json(['success' => false, 'message' => 'Dimensiones excedidas (max 2048x2048).'], 422);
}
imagedestroy($img);
```

**3. Fix password in SyncDicomEcografias:**
Find `'password' => bcrypt('123456')` and replace with `'password' => bcrypt(\Illuminate\Support\Str::random(16)), 'password_changed_at' => null`

### Task 13: Security Tests
**Files:**
- Create: `tests/Feature/SecurityHeadersTest.php`
- Create: `tests/Feature/PermissionEnforcementTest.php`

**SecurityHeadersTest:**
```php
class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_present(): void {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
    public function test_csp_header_is_present(): void {
        $response = $this->get('/');
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }
    public function test_hsts_on_https(): void {
        $response = $this->get('/', ['HTTPS' => true]);
        $this->assertNotNull($response->headers->get('Strict-Transport-Security'));
    }
    public function test_permissions_policy(): void {
        $response = $this->get('/');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }
}
```

**PermissionEnforcementTest:**
```php
class PermissionEnforcementTest extends TestCase
{
    public function test_guest_gets_401(): void {
        $response = $this->getJson('/api/ecografias');
        $response->assertStatus(401);
    }
    public function test_user_without_permission_gets_403(): void {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/ecografias');
        $response->assertStatus(403);
    }
    public function test_file_access_guest_gets_401(): void {
        $response = $this->getJson('/files/firmas_consntimientos/test.png');
        $response->assertStatus(401);
    }
}
```

Work from: C:\laragon\www\lidiag. Run `php -l` on all files, `php artisan migrate` for new migrations, then `php artisan test tests/Feature/SecurityHeadersTest.php tests/Feature/PermissionEnforcementTest.php`. Commit as: `feat(security): auth hardening, RCE fix, upload validation, security tests`
