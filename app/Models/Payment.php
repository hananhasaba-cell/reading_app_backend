<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

protected $fillable = [
    'user_id',
    'subscription_id', 
    'book_id',
    'amount',
    'currency',
    'gateway',
    'gateway_id',
    'status',
    'is_test'
];
//-----------------------------------------------------------------
// علاقة الدفع بالكتاب
public function book()
{
    return $this->belongsTo(Book::class);
}
//-----------------------------------------------------------------
    // علاقة مع المتخدمين
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // علاقة مع الاشتراكات
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
