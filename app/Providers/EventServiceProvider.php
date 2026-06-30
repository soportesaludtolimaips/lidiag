<?php

namespace App\Providers;

use App\Helpers\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(Login::class, function (Login $event) {
            AuditLogger::evento(
                'inicio_sesion',
                $event->user,
                'Inicio de sesión — Usuario: '.($event->user->email ?? $event->user->name ?? 'N/A')
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLogger::evento(
                    'cierre_sesion',
                    $event->user,
                    'Cierre de sesión — Usuario: '.($event->user->email ?? $event->user->name ?? 'N/A')
                );
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            AuditLogger::evento(
                'inicio_sesion_fallido',
                null,
                'Intento de inicio de sesión fallido — Email: '.($event->credentials['email'] ?? 'N/A').', IP: '.request()->ip()
            );
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
