<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class Suggestion extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'title',
        'author',
        'description',
        'status',
        'admin_id',
        'related_book_id',
        'reviewed_at',
    ];
//-----------------------------------------------------------------------
//حالة الاقتراح
    public const STATUS_PENDING = 'معلق';
    public const STATUS_ACCEPTED = 'تمت الموافقة';
    public const STATUS_REJECTED = 'تم الرفض';
//------------------------------------------------------------------------
//علاقة الاقتراح بالمستخدم
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
//-------------------------------------------------------------------------
//علاقة الاقتراح بالكتاب
    public function book() {
        return $this->belongsTo(Book::class, 'book_id');
    }
//-------------------------------------------------------------------------    
//علاقة الاقتراح بالكتاب المقترح
    public function relatedBook() {
        return $this->belongsTo(Book::class, 'related_book_id');
    }
//--------------------------------------------------------------------------
//علاقة الاقتراح بالمدير الذي راجعه    
    public function admin() {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
