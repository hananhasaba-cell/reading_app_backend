<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weekly_Goals extends Model
{
protected $fillable= ['admin_id', 'target_pages','title', 'start_date', 'end_date'];
//--------------------------------------------------------------------------------
//علاقة الهدف الأسبوعي بالمدير
public function admin(){
    return $this->belongsTo(Admin::class);
}
//---------------------------------------------------------------------------------
//علاقة الهدف الأسبوعي بتقدم المستخدم
public function userProgress(){
    return $this->hasMany(UserProgress::class);
}
//----------------------------------------------------------------------------------
}
