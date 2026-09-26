<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every few minutes rather than once a day: each child now has their own
// reminder_time (see SendPracticeReminders), not one fixed time for everyone.
Schedule::command('reminders:send')->everyFiveMinutes();
Schedule::command('contact:prune')->daily();
Schedule::command('accounts:prune-unverified')->daily();
