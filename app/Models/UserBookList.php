<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBookList extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
    ];

    public const STATUS_WANT_TO_READ = 'أرغب بقراءتها';
    public const STATUS_READING = 'أقرأها الآن';
    public const STATUS_FINISHED = 'أنهيتها';
//حالات الكتب بالقوائم
    public static function statuses(): array
    {
        return [
            self::STATUS_WANT_TO_READ,
            self::STATUS_READING,
            self::STATUS_FINISHED,
        ];
    }
//---------------------------------------------------------------------------
//علاقة المستخدم بالقائمة
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
//---------------------------------------------------------------------------
//علاقة الكتب بالقائمة
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
//---------------------------------------------------------------------------
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
