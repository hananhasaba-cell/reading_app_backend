<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReaderLevelUp extends Notification
{
    use Queueable;

    public string $nickname;

    public function __construct(string $nickname)
    {
        $this->nickname = $nickname;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "مبارك، تم ترقيتك إلى لقب: {$this->nickname}",
            'type' => 'level_up',
        ];
    }
}