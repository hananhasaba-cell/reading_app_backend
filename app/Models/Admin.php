<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
use HasFactory, Notifiable,HasApiTokens;
    protected $fillable = ['name', 'email', 'password'];

protected $hidden = ['password'];
//-------------------------------------------------------------------------
//علاقة المدير بالاقتراحات التي يراجعها  
public function suggestions(){
    return $this->hasMany(Suggestion::class);
} 
//-------------------------------------------------------------------------
//علاقة المدير بالكتب التي يديرها  
public function books(){
    return $this->hasMany(Book::class);
}  
//-------------------------------------------------------------------------
//علاقة المدير بالمستخدمين المشرف عليهم  
public function users(){
    return $this->hasMany(User::class);
} 
//-------------------------------------------------------------------------
//علاقة التقدمات بالمستخدمين المشرف عليهم
public function progress() {
    return $this-> hasMany(UserProgress::class, 'user_id');
}
//--------------------------------------------------------------------------
}
