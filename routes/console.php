<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Pending requests whose time is over become "Expired" (no-show). Run `php artisan schedule:work` locally,
// or add the usual `* * * * * php artisan schedule:run` cron on the server. (The panels also do this check
// themselves at most once a minute, so it works even without the scheduler.)
Artisan::command('appointments:expire', function () {
    $this->info(\App\Models\Appointment::expireStale() . ' appointment(s) marked as expired.');
})->purpose('Mark pending appointments whose time has passed as expired');

Schedule::command('appointments:expire')->everyFiveMinutes();


// 24-hour and 2-hour reminders (e-mail + SMS / WhatsApp when switched on). This only QUEUES the reminders;
// they are sent by the queue worker: `php artisan queue:work` (QUEUE_CONNECTION=database). The scheduler needs
// `php artisan schedule:work` locally or the `* * * * * php artisan schedule:run` cron on the server.
Artisan::command('appointments:remind', function () {
    $this->info(\App\Models\Appointment::dispatchDueReminders() . ' reminder(s) queued.');
})->purpose('Queue the 24-hour and 2-hour appointment reminders');

Schedule::command('appointments:remind')->everyMinute()->withoutOverlapping();
