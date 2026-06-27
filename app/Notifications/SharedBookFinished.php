<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SharedBookFinished extends Notification
{
    use Queueable;

    protected $user;
    protected $book;

    public function __construct($user, $book)
    {
        $this->user = $user;
        $this->book = $book;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->user->name} أنهى قراءة كتاب قرأته أنت سابقاً!",
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'user_profile_img' => $this->user->profile_img
                ? asset('storage/' . $this->user->profile_img)
                : null,
        ];
    }
}
