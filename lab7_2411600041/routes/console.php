<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lab 7: every day at 6:00 AM (Manila time) look for products that need to be reordered.
// Laravel 12 has no Kernel.php anymore, so the schedule lives here.
Schedule::command('inventory:check-reorder')
    ->dailyAt('06:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reorder-check.log'))
    ->onFailure(function () {
        Log::error('Scheduled command inventory:check-reorder failed.');
    });
