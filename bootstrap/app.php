<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustHosts;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\VerifyTeleRadiologiaHmac;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            $apiFiles = [
                'api.php',
                'api_configuracion.php',
                'api_estudios.php',
                'api_ecografias.php',
                'api_seguridad.php',
                'api_interfaces.php',
                'api_agendamiento_dashboard.php',
                'api_helpdesk.php',
                'api_OHIF_visor.php',
                'api_burn.php',
                'api_contratacion.php',
                'api_liquidacion.php',
                'api_telegram.php',
                'api_pacientes.php',
                'api_dosimetria.php',
            ];
            foreach ($apiFiles as $file) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/'.$file));
            }
        },
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware stack
        $middleware->append([
            TrustHosts::class,
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        // Web middleware group
        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SecurityHeaders::class,
            SubstituteBindings::class,
            HandleCors::class,
            ForcePasswordChange::class,
        ]);

        // API middleware group
        $middleware->group('api', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            'throttle:api',
            SubstituteBindings::class,
            HandleCors::class,
            SecurityHeaders::class,
            ForcePasswordChange::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'signed' => ValidateSignature::class,
            'tele.hmac' => VerifyTeleRadiologiaHmac::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'force-password-change' => ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if ($request->expectsJson()) {
                $status = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException, $e instanceof SpatieUnauthorizedException => 403,
                    $e instanceof ModelNotFoundException => 404,
                    $e instanceof NotFoundHttpException => 404,
                    $e instanceof ValidationException => 422,
                    $e instanceof ThrottleRequestsException => 429,
                    default => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                };

                $message = match (true) {
                    $e instanceof ValidationException => $e->getMessage(),
                    $e instanceof AuthenticationException => 'No autenticado.',
                    $e instanceof AuthorizationException, $e instanceof SpatieUnauthorizedException => 'No autorizado.',
                    $e instanceof ModelNotFoundException => 'Recurso no encontrado.',
                    $e instanceof ThrottleRequestsException => 'Demasiadas solicitudes.',
                    $e instanceof NotFoundHttpException => 'Ruta no encontrada.',
                    default => config('app.debug') && app()->isLocal() ? $e->getMessage() : 'Error interno del servidor.',
                };

                $message = mb_convert_encoding($message, 'UTF-8', 'UTF-8');

                $data = $e instanceof ValidationException ? $e->errors() : null;

                return new JsonResponse([
                    'success' => false,
                    'data' => $data,
                    'message' => $message,
                    'status' => $status,
                ], $status);
            }

            // Let default handler process non-JSON requests
        });
    })->create();
