<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('cobros:procesar --enviar')->dailyAt('09:00')->timezone('America/Bogota')->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/cobros-scheduler.log'));

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
