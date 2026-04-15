<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
protected $fillable= ['user_id', 'book_id'];
//-------------------------------------------------------------
//علاقة المستخدم الذي أضاف الكتاب كمفضلة
public function user(){
    return $this->belongsTo(Users::class);
}
//-------------------------------------------------------------
//علاقة الكتاب الذي تمت إضافته كمفضلة
public function book(){
    return $this->belongsTo(Book::class);
}
//-------------------------------------------------------------
}
