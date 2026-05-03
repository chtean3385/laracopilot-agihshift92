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
