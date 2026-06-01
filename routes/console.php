<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('whatsapp:checkin-reminders')->dailyAt('09:00');
Schedule::command('whatsapp:feedback-reminders')->dailyAt('10:00');
Schedule::command('hotels:backup')->hourly();
Schedule::command('invoices:purge-deleted')->dailyAt('02:00');
Schedule::command('emails:sync')->everyFiveMinutes()->withoutOverlapping();

// Keep Neon DB compute warm — prevents cold-start latency on the first request after an idle period.
// Neon free tier suspends after 5 min of inactivity; pinging every 4 min keeps it alive.
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::select('SELECT 1');
})->everyFourMinutes()->name('db:keepalive')->withoutOverlapping();
