<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReadingReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */public function via($notifiable): array
{
    return ['database'];
}
public function toArray($notifiable)
{
    return [
        'message' => "وقفة صغيرة مع كتابك قد تغيّر مزاج يومك. تذكير لطيف بالعودة لصفحاتك المفضلة ",
        'type' => 'reading_reminder',
    ];
}

}
