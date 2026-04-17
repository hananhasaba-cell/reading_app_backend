<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserBookList;

class UsersController extends Controller
{

    // إنشاء حساب
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'password'   => ['required','string','min:8','confirmed'],
            'email' => ['required','string','unique:users,email'],
            'profile_img'=> ['nullable','string','max:1024'],
        ], [
            'password.confirmed' => 'كلمة المرور غير مطابقة',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'profile_img.image' => 'يجب أن يكون الملف صورة',
            'profile_img.mimes' => 'نوع الصورة غير مدعوم',
            'profile_img.max' => 'حجم الصورة كبير جداً',
        ]);

    // التعامل مع رفع الصورة
    $profilePath = $request->hasFile('profile_img')
        ? $request->file('profile_img')->store('profile_images', 'public')
        : null;


        $user = User
        ::create([
            'name'  => $validated['name'],
            'password'   => Hash::make($validated['password']),
            'email' => $validated['email'],
            'profile_img' => $profilePath,
        ]);
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' =>"تم إنشاء حساب بنجاح",
            'data'    => [
            'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'profile_img' => $profilePath,

],
            'token' => $token,
            ],
        ], 201);
        } 
//-------------------------------------------------------------------------------------------------------------------        
//تسجيل دخول    
       public function login(Request $request)
    {
        $credentials = $request->validate([
            'password' => ['required','string'],
            'email' => ['required','string'],
        ]);

        // البحث عن المستخدم عبر الإيميل
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
            'success' => false,
            'message' => "البيانات غير مطابقة",
            ], 401);
        }
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => "تم تسجيل الدخول بنجاح",
            'data'    => [
            'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' =>$user->email,
        'profile_img' => $user->profile_img ? asset('storage/'.$user->profile_img) : null,

],
            'token' => $token,
            ],
        ], 200);
    }
//-------------------------------------------------------------------------------------------------------------------    
    // معلومات المستخدم الحالي
   public function info(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' =>"خطأ في تحديد المستخدم",
        ], 401);
    }

    return response()->json([
                    // إحصائيات القوائم
            'stats' => [
                'want_to_read_count' => $user->bookList()
                    ->where('status', UserBookList::STATUS_WANT_TO_READ)
                    ->count(),

                'reading_now_count' => $user->bookList()
                    ->where('status', UserBookList::STATUS_READING)
                    ->count(),

                'finished_count' => $user->bookList()
                    ->where('status', UserBookList::STATUS_FINISHED)
                    ->count(),
                    $nickname = $user->nickname ?: $this->getReaderTitle($user->bookList()->where('status', UserBookList::STATUS_FINISHED)->count())
            ],
            
        'success' => true,
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'nickname' => $nickname,
            'email' => $user->email,
            'total_points' => $user->total_points,
            'profile_img' => $user->profile_img ? asset('storage/' . $user->profile_img) : null,


        ],
    ], 200);
}

//-------------------------------------------------------------------------------------------------------------------
//تعديل الملف الشخصي
public function update(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => "خطأ في تحديد المستخدم",
        ], 401);
    }


    $validated = $request->validate([
        'name' => ['sometimes', 'required', 'string', 'max:255'],
        'email' => ['sometimes', 'required', 'string', 'email', 'unique:users,email,' . $user->id],
        'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
        'old_password' => ['required_with:password'],
        'profile_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
    ], [
        'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
        'password.confirmed' => 'كلمة المرور غير مطابقة',
        'old_password.required_with' => 'يجب إدخال كلمة المرور القديمة لتغيير كلمة المرور',
        'profile_img.image' => 'يجب أن يكون الملف صورة',
        'profile_img.mimes' => 'نوع الصورة غير مدعوم',
        'profile_img.max' => 'حجم الصورة كبير جداً',
    ]);

    // منع تحديث كلمة المرور إذا لم يتم التأكد من الكلمة القديمة
    if ($request->filled('password')) {
        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور القديمة غير صحيحة',
            ], 400);
        }
        $validated['password'] = Hash::make($validated['password']);
    }
    // التعامل مع رفع الصورة
    $profilePath = $request->hasFile('profile_img')
        ? $request->file('profile_img')->store('profile_images', 'public')
        : null;
    if ($profilePath) {
        $validated['profile_img'] = $profilePath;
    }

    $user->update($validated);

    return response()->json([
        'success' => true,
        'message' => "تم تحديث المعلومات الشخصية بنجاح",
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_img' => $user->profile_img ? asset('storage/' . $user->profile_img) : null,
            ],
        ],
    ], 200);
}
//--------------------------------------------------------------------------------------------
//تسجيل خروج
    public function logout(Request $request)
    {
        // حذف التوكن فقط
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => "تم تسجيل الخروج بنجاح",
        ], 200);
    }

//---------------------------------------------------------------------------------------------
//حذف الحساب 
public function deleteAccount(Request $request)
{
    $user = $request->user();

    $user->delete();

    return response()->json([
        'success' => true,
        'message' =>"تم حذف الحساب بنجاح"
    ], 200);
}
//---------------------------------------------------------------------------------------------
//متابعة المستخدمين لبعضهم
public function follow(Request $request, $userId)
{
    $user = $request->user();
    $followedUser = User::findOrFail($userId);

    if ($user->id == $userId) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك متابعة نفسك',
        ], 400);
    }

    if ($user->following()->where('followed_id', $userId)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'أنت تتابع هذا المستخدم بالفعل',
        ], 400);
    }

    $user->following()->attach($userId);

    return response()->json([
        'success' => true,
        'message' => 'تم متابعة المستخدم بنجاح',
    ], 200);
}
//---------------------------------------------------------------------------------------------
//إلغاء متابعة
public function unfollow(Request $request, $userId)
{
    $user = $request->user();

    if (!$user->following()->where('followed_id', $userId)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'أنت لا تتابع هذا المستخدم',
        ], 400);
    }

    $user->following()->detach($userId);

    return response()->json([
        'success' => true,
        'message' => 'تم إلغاء متابعة المستخدم بنجاح',
    ], 200);
}
//---------------------------------------------------------------------------------------------
//عرض معلومات المستخدم الذي تتم متابعته
public function getFollowedUser(Request $request, $followedUserId)
{
    $user = $request->user();

    //  هل المستخدم الحالي يتابع هذا الشخص؟
    $followedUser = $user->following()->where('followed_id', $followedUserId)->first();

    if (! $followedUser) {
        return response()->json([
            'success' => false,
            'message' => 'أنت لا تتابع هذا المستخدم.',
        ], 404);
    }

    // جلب بيانات المستخدم الذي تتم متابعته
    $targetUser = User::findOrFail($followedUserId);

    return response()->json([
        'success' => true,
        'data' => [
            'user_id' => $targetUser->id,
            'name' => $targetUser->name,
            'nickname' => $targetUser->nickname,
            'total_points' => $targetUser->total_points,
            'profile_img' => $targetUser->profile_img ? asset('storage/' . $targetUser->profile_img) : null,

            // إحصائيات القراءة
            'stats' => [
                'want_to_read_count' => $targetUser->bookList()
                    ->where('status', UserBookList::STATUS_WANT_TO_READ)
                    ->count(),

                'reading_now_count' => $targetUser->bookList()
                    ->where('status', UserBookList::STATUS_READING)
                    ->count(),

                'finished_count' => $targetUser->bookList()
                    ->where('status', UserBookList::STATUS_FINISHED)
                    ->count(),
            ],
        ],
    ], 200);
}
//---------------------------------------------------------------------------------------------
// عرض قائمة المتابعين
public function getFollowers(Request $request)
{
    $user = $request->user();

    $followers = $user->followers()->get();

    return response()->json([
        'success' => true,
        'data' => [
            'followers' => $followers->map(function ($follower) {
                return [
                    'id' => $follower->id,
                    'name' => $follower->name,
                    'nickname' => $follower->nickname,
                    'total_points' => $follower->total_points,
                    'email' => $follower->email,
                    'profile_img' => $follower->profile_img
                        ? asset('storage/' . $follower->profile_img)
                        : null,
                ];
            }),
        ],
    ], 200);
}
//----------------------------------------------------------------------------------------------
// تابع لتحديد اللقب
public function getReaderTitle($count)
{
    if ($count >= 200) return 'القارئ اللانهائي';
    if ($count >= 150) return 'أسطورة القراءة';
    if ($count >= 100) return 'شعلة القراءة';
    if ($count >= 60) return 'دودة الكتب';
    if ($count >= 40) return 'قارئ نهم';
    if ($count >= 20) return 'قارئ نشيط';
    if ($count >= 10) return 'قارئ منتظم';

    return 'قارئ مبتدئ';
}
//--------------------------------------------------------------------------------------
//عرض إشعارات المستخدم
public function userNotifications(Request $request)
{
    $user = $request->user();

    $notifications = $user->notifications()
        ->latest()
        ->get();

    return response()->json([
        'success' => true,
        'data' => $notifications,
    ]);
}
//تحديد كل الإشعارات كمقروءة
public function markAsRead(Request $request, $id)
{
    $notification = $request->user()
        ->notifications()
        ->where('id', $id)
        ->firstOrFail();

    $notification->markAsRead();

    return response()->json([
        'success' => true,
        'message' => 'تم تحديد الإشعار كمقروء'
    ]);
}
}