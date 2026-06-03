<?php

namespace App\Providers;

use App\Extensions\SessionLogger;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        \URL::forceScheme('https');

        Session::extend('database-log', function ($app) {
            $connection = $app['db']->connection(config('session.connection'));
            $table = config('session.table', 'sessions');
            $minutes = config('session.lifetime', 120);
            $handler = new SessionLogger($connection, $table, $minutes, $app);
            return $handler;
        });
    }
}
