<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserFollowed extends Notification
{
    use Queueable;

    protected $follower;

    public function __construct($follower)
    {
        $this->follower = $follower;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->follower->name} بدأ بمتابعتك",
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'follower_profile_img' => $this->follower->profile_img
                ? asset('storage/' . $this->follower->profile_img)
                : null,
        ];
    }
}
