<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\ReadingReminder;

class SendReadReminders extends Command
{
    protected $signature = 'reminders:send-read';
    protected $description = 'Send daily reading reminders to users';

    public function handle()
    {
        // جلب جميع المستخدمين
        $users = User::all();

        foreach ($users as $user) {
            $user->notify(new ReadingReminder());
        }

        return Command::SUCCESS;
    }
}
