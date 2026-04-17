<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_img',
        'nickname',
    ];
//----------------------------------------------------------------------------
// تابع علاقة تقييمات الكتاب مع المستخدم
public function ratings(){
    return $this->hasMany(Rating::class);
}
//----------------------------------------------------------------------------
// تابع علاقة المستخدم بكتابة تعليقه
public function commnets() {
    return $this->hasMany(Comment::class);
    
}
//----------------------------------------------------------------------------
// تابع علاقة المستخدم باقتراح الكتب
public function suggestions() {
    return $this->hasMany(Suggestion::class);
    
}
//----------------------------------------------------------------------------
// تابع علاقة المستخدم بالكتب في المفضلة
public function favorites() {
    return $this->hasMany(Favorite::class);
}
//----------------------------------------------------------------------------
// علاقة قائمة الكتب الخاصة بالمستخدم
public function bookList()
{
    return $this->hasMany(UserBookList::class, 'user_id');
}

public function wantToRead()
{
    return $this->hasMany(UserBookList::class, 'user_id')->where('status', UserBookList::STATUS_WANT_TO_READ);
}

public function readingNow()
{
    return $this->hasMany(UserBookList::class, 'user_id')->where('status', UserBookList::STATUS_READING);
}

public function finishedReading()
{
    return $this->hasMany(UserBookList::class, 'user_id')->where('status', UserBookList::STATUS_FINISHED);
}
//----------------------------------------------------------------------------
// علاقة المتابعين (من يتابعون هذا المستخدم)
public function followers() {
    return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id');
}
//----------------------------------------------------------------------------
// علاقة المتابعة (من يتابع هذا المستخدم)
public function following() {
    return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id');
}
//----------------------------------------------------------------------------
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
