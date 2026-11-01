<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Part 3 Step 5: araw-araw na pag-check ng reorder points (6:00 AM)
Schedule::command('inventory:check-reorder')
    ->dailyAt('06:00')
    ->timezone('Asia/Manila')
    ->emailOutputTo('testadmin@example.com');
