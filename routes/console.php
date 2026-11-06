<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('smartdocs:backup')
    ->dailyAt(config('backup.schedule', '02:00'))
    ->withoutOverlapping();

Schedule::command('smartdocs:send-health-alerts')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
