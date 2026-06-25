### Task 7: Soft Delete for Ecografias
**Files:**
- Create: `database/migrations/2026_06_24_000002_add_soft_deletes_to_ecografias_tables.php`
- Modify: `app/Http/Controllers/Ecografias/EcografiaController.php`
- Modify: `app/Http/Controllers/Ecografias/EcografiaProductoController.php`
- Modify: `app/Models/Ecografias/Ecografia.php`
- Modify: `app/Models/Ecografias/EcografiaProducto.php`

**Migration:**
```php
Schema::table('ecografias', fn(Blueprint $t) => $t->softDeletes());
Schema::table('ecografias_productos', fn(Blueprint $t) => $t->softDeletes());
```

Add `use Illuminate\Database\Eloquent\SoftDeletes;` and `use SoftDeletes;` to both models.
In controllers, replace hard delete with `$model->delete()`.

### Task 8: Rate Limiting
**Files:**
- Modify: `app/Providers/RouteServiceProvider.php`
- Modify: `routes/api_ecografias.php`

In `RouteServiceProvider::boot()`, add before `$this->routes()`:
```php
RateLimiter::for('api-general', fn() => Limit::perMinute(60));
RateLimiter::for('api-sahi', fn() => Limit::perMinute(30));
RateLimiter::for('api-consent-create', fn() => Limit::perMinute(10));
RateLimiter::for('api-lectura', fn() => Limit::perMinute(20));
```
Imports needed: `use Illuminate\Cache\RateLimiting\Limit; use Illuminate\Support\Facades\RateLimiter;`

In `routes/api_ecografias.php`, add `'throttle:api-sahi'` to SAHI routes, `'throttle:api-consent-create'` to token creation, `'throttle:api-lectura'` to lectura endpoint.

### Task 9: Secure Error Handling
**Files:**
- Modify: `app/Exceptions/Handler.php`

Replace the `render()` method to return safe JSON for API requests:
```php
public function render($request, Throwable $e)
{
    if ($request->expectsJson()) {
        $status = match(true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof ValidationException => 422,
            $e instanceof ThrottleRequestsException => 429,
            default => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
        };
        $message = match(true) {
            $e instanceof ValidationException => $e->getMessage(),
            $e instanceof AuthenticationException => 'No autenticado.',
            $e instanceof AuthorizationException => 'No autorizado.',
            $e instanceof ModelNotFoundException => 'Recurso no encontrado.',
            $e instanceof ThrottleRequestsException => 'Demasiadas solicitudes.',
            $e instanceof NotFoundHttpException => 'Ruta no encontrada.',
            default => config('app.debug') && app()->isLocal() ? $e->getMessage() : 'Error interno del servidor.',
        };
        return response()->json(['success' => false, 'message' => $message], $status);
    }
    return parent::render($request, $e);
}
```

Add missing imports: `use Illuminate\Auth\AuthenticationException; use Illuminate\Auth\Access\AuthorizationException; use Illuminate\Database\Eloquent\ModelNotFoundException; use Illuminate\Http\Exceptions\ThrottleRequestsException; use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;`

### Task 12: Session and Sanctum Hardening
**Files:**
- Modify: `config/session.php` — set `'secure' => env('SESSION_SECURE_COOKIE', true)`, `'http_only' => true`, `'same_site' => 'strict'`, `'lifetime' => 120`
- Modify: `config/sanctum.php` — set `'expiration' => 60`

Work from: C:\laragon\www\lidiag. After all edits, run `php -l` on each modified PHP file. Commit as: `feat(security): soft delete, rate limiting, error handling, session hardening`
