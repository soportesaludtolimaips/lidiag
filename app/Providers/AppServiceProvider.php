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

    public function register()
    {
        //
    }

    public function boot()
    {
        \URL::forceScheme('https');

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
        Gate::policy(User::class, UserPolicy::class);

        $this->configureRateLimiting();
        $this->registerGlobalActivityLog();
    }

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
