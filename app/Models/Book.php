<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Prompts\Progress;

class Book extends Model
{
protected $fillable = ['title', 'author', 'cover_img', 'description', 'admin_id', 'PageNumber', 'pdf_path'];


//-----------------------------------------------------------------
//تابع علاقة الكتاب بالنوع
public function geners(){
        return $this->belongsToMany(Gener::class, 'book__geners');
    }
//-----------------------------------------------------------------    
//تابع علاقة الكتاب بالتقييم
public function ratings(){
        return $this->hasMany(Rating::class);
    }
//-----------------------------------------------------------------
//تابع علاقة الكتاب بالتعليقات عليه
public function comments(){
        return $this->hasMany(Comment::class);
    }
//-----------------------------------------------------------------
//تابع علاقة الكتاب بالاقتراحات
public function suggestions(){
        return $this->hasMany(Suggestion::class);
    }
 //-----------------------------------------------------------------  
 //تابع علاقة الكتاب بالمفضلة
public function favorites(){
        return $this->hasMany(Favorite::class);
    } 
 //-----------------------------------------------------------------  
//علاقة الكتاب بقوائم المستخدمين
public function userBookLists()
{
        return $this->hasMany(UserBookList::class, 'book_id');
}
 //-----------------------------------------------------------------  
//علاقة الكتب بالمدير   
public function admin(){
    return $this->belongsTo(Admin::class);
} 
 
}
