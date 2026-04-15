<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
protected $fillable = ['user_id' , 'book_id', 'rating'];

//-----------------------------------------------------------------------------
    //علاقة التقييم بالكتاب
    public function book(){
        return $this->belongsTo(Book::class);
    }
//-----------------------------------------------------------------------------
    //علاقة التقييم بالمستخدم
    public function user(){
        return $this->belongsTo(User::class);
    }
}
