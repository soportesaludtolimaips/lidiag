<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const SENSITIVE_FIELDS = [
        'password', 'remember_token', 'firma', 'token_hash',
    ];

    /**
     * Registra los bindings del contenedor de servicios.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function register()
    {
        //
    }

    /**
     * Ejecuta la configuración de arranque de la aplicación.
     *
     * Define el permiso global del Super Admin, la política de User,
     * el rate limiting y el log global de actividad.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function boot()
    {
        //         \URL::forceScheme('https');

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
        Gate::policy(User::class, UserPolicy::class);

        $this->configureRateLimiting();
        $this->registerGlobalActivityLog();
    }

    /**
     * Configura los límites de velocidad (rate limiting) de la aplicación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-general', fn () => Limit::perMinute(60));
        RateLimiter::for('api-sahi', fn () => Limit::perMinute(30));
        RateLimiter::for('api-consent-create', fn () => Limit::perMinute(10));
        RateLimiter::for('api-lectura', fn () => Limit::perMinute(20));

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            $key = mb_strtolower($email).'|'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });
    }

    /**
     * Registra en activity_log las altas, bajas y cambios de los modelos.
     *
     * Excluye los campos sensibles y no registra la propia activity_log.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function registerGlobalActivityLog(): void
    {
        $events = ['created', 'updated', 'deleted'];

        foreach ($events as $event) {
            Model::{$event}(function (Model $model) use ($event) {
                if (! Auth::check()) {
                    return;
                }
                if ($model instanceof ActivityLog) {
                    return;
                }

                $old = match ($event) {
                    'updated' => $model->getOriginal(),
                    'deleted' => $model->getAttributes(),
                    default => null,
                };

                $new = match ($event) {
                    'created' => $model->getAttributes(),
                    'updated' => $model->getChanges(),
                    default => null,
                };

                $filter = fn ($v) => is_array($v)
                    ? array_diff_key($v, array_flip(self::SENSITIVE_FIELDS))
                    : $v;

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'loggable_type' => get_class($model),
                    'loggable_id' => $model->getKey(),
                    'event' => $event,
                    'old_values' => $old ? $filter($old) : null,
                    'new_values' => $new ? $filter($new) : null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            });
        }
    }
}
