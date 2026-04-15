<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gener extends Model
{
    use HasFactory;
    protected $fillable = ['name'];
//------------------------------------------------------------------------    
    //تابع علاقة الكتاب بالنوع
    public function books(){
        return $this->BelongsToMany(Book::class,'book__geners');
    }
//--------------------------------------------------------------------------    
}
