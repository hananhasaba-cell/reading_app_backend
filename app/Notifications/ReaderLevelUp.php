<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReaderLevelUp extends Notification
{
    use Queueable;

    protected string $nickname;
    protected ?\App\Models\User $user;

    public function __construct(string $nickname, ?\App\Models\User $user = null)
    {
        $this->nickname = $nickname;
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        // إشعار لصاحب الحساب نفسه
        if ($this->user === null) {
            return [
                'message'  => "مبارك، تم ترقيتك إلى لقب: {$this->nickname}",
                'type'     => 'level_up',
                'nickname' => $this->nickname,
            ];
        }

        // إشعار يصل للمتابعين عن هذا المستخدم
        return [
            'message'     => "{$this->user->name} حصل على لقب جديد: {$this->nickname}",
            'type'        => 'level_up_follower',
            'user_id'     => $this->user->id,
            'user_name'   => $this->user->name,
            'nickname'    => $this->nickname,
            'profile_img' => $this->user->profile_img
                ? asset('storage/' . $this->user->profile_img)
                : null,
        ];
    }
}
