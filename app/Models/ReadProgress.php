<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadProgress extends Model
{
    use HasFactory;

    protected $table = 'read_progresses';

    protected $fillable = [
        'user_id',
        'book_id',
        'pages_read',
        'prompted_for_subscription'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
