<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
protected $fillable = ['user_id', 'weekly_goal_id', 'book_id', 'pages_read', 'completed', 'points_earned'];
//---------------------------------------------------------------------------------
//علاقة التقدم بكل مستخدم
public function user(){
    return $this->belongsTo(User::class, 'user_id');
}
//-----------------------------------------------------------------------------------
//علاقة التقدم بكل هدف
public function weeklyGoals(){
    return $this->belongsTo(Weekly_Goals::class);
}
//------------------------------------------------------------------------------------
//علاقة التقدم بالكتاب
public function book(){
    return $this->belongsTo(Book::class);
}
//------------------------------------------------------------------------------------
//حساب نسبة التقدم
public function getProgressPercentageAttribute()
{
    if (!$this->weeklyGoal) return 0;

    return ($this->pages_read / $this->weeklyGoal->target_pages) * 100;
}
}
