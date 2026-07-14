<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

        $this->registerGlobalActivityLog();
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
