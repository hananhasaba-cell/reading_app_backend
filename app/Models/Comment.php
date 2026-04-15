<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;


protected $fillable = ['user_id', 'book_id' , 'parent_id' , 'content'];
//-----------------------------------------------------------------------
//تابع علاقة التعليق بصاحبه
public function user(){
    return $this->belongsTo(User::class);
}
//-----------------------------------------------------------------------
//تابع علاقة التعليق بالكتاب
public function book(){
    return $this->belongsTo(Book::class);
}
//-----------------------------------------------------------------------
//تابع علاقة التعليق الأب
public function parent(){
    return $this->belongsTo(Comment::class, 'parent_id');
}
//-----------------------------------------------------------------------
//تابع علاقة التعليق بالردو عليه
public function replies(){
    return $this->hasMany(Comment::class, 'parent_id');
}
//------------------------------------------------------------------------

//------------------------------------------------------------------------

}
