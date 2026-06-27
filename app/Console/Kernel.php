<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // جدولة التذكير اليومي
    $schedule->command('reminders:send-read')->dailyAt('08:00');
    $schedule->command('reminders:send-read')->dailyAt('15:00');
    $schedule->command('reminders:send-read')->dailyAt('21:00');

    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
