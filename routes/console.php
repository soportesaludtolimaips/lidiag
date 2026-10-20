<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ecografias:sahi-reconcile --minutes=10 --limit=200')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('citas:notificar')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('citas:notificar-2h')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('sessions:prune --hours=24 --user-hours=168')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('notifications:prune --days=90')
    ->daily()
    ->withoutOverlapping();

Schedule::command('lidiag:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('lidiag:backup-dcm')
    ->dailyAt('02:30')
    ->withoutOverlapping();

Schedule::command('telegram:daily-digest')
    ->dailyAt('07:30')
    ->withoutOverlapping();
