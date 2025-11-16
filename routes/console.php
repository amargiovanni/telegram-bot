<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RSS Feed Monitoring - runs every minute
Schedule::command('telegram:monitor-rss')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Send Due Reminders - runs every minute
Schedule::command('reminders:send')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
