<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    // Default post-auth redirection target (si algún flujo lo usa)
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Rutas de configuración con middleware personalizado (algunas públicas, algunas protegidas)
            // Importante: debe ir bajo middleware('api') para que Sanctum SPA
            // (EnsureFrontendRequestsAreStateful) aplique y `auth:sanctum` reconozca sesión/cookies.
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_configuracion.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_estudios.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_ecografias.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_seguridad.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_interfaces.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_agendamiento_dashboard.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_helpdesk.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_OHIF_visor.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Limita intentos de login por email+IP para mitigar brute force
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            $key = mb_strtolower($email) . '|' . $request->ip();

            return Limit::perMinute(10)->by($key);
        });
    }
}
