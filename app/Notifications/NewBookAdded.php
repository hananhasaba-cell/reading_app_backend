<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewBookAdded extends Notification
{
    use Queueable;

    protected $book;

    public function __construct($book)
    {
        $this->book = $book;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "تمت إضافة كتاب جديد: {$this->book->title}",
            'type' => 'new_book',
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'book_author' => $this->book->author,
            'cover_img' => $this->book->cover_img
                ? asset('books/images/' . $this->book->cover_img)
                : null,
        ];
    }
}
