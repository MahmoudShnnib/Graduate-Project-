<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Flip queued ("pending") WhatsApp logs to their real Qonvo delivery status.
// Needs the scheduler running (schedule:work / cron); the notification-logs page
// also has a manual "تحديث الحالات" button as a fallback.
Schedule::command('whatsapp:reconcile')->everyFiveMinutes()->withoutOverlapping();
