<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UserBookListController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\RatingController;
// المسنخدم
//إنشاء حساب جديد
Route::post('/register', [UsersController::class, 'register']);
// تسجيل دخول
Route::post('/login', [UsersController::class, 'login'])->middleware('throttle:login');
//حماية المسارات
Route::middleware('auth:sanctum')->group(function () {
    //عرض معلومات المستخدم
    Route::get('/info', [UsersController::class, 'info']);
    // تحديث المعلومات الشخصية
    Route::put('/update', [UsersController::class, 'update']);
    // تسجيل خروج
    Route::post('/logout', [UsersController::class, 'logout']);
    // حذف الحساب
    Route::delete('/delete-account', [UsersController::class, 'deleteAccount']);
    // متابعة مستخدم
    Route::post('/follow/{userId}', [UsersController::class, 'follow']);
    // إلغاء متابعة مستخدم
    Route::delete('/unfollow/{userId}', [UsersController::class, 'unfollow']);
    // عرض بيانات الذين أتابعهم
    Route::get('/followed_users/{followedUserId}', [UsersController::class, 'getFollowedUser']);
    // عرض المتابعين
    Route::get('/followers', [UsersController::class, 'getFollowers']);
    //عرض إشعارات المستخدم
    Route::get('/notifications', [UsersController::class, 'userNotifications']);
    //تحديد كل الإشعارات كمقروءة
    Route::post('/all_read', [UsersController::class, 'markAsRead']);
    //-----------------------------------------------------------------------------------------------------
// قوائم الكتب الشخصية
//عرض الكتب
    Route::get('/book_list', [UserBookListController::class, 'index']);
    //إضافة كتاب للقوائم
    Route::post('/book_list', [UserBookListController::class, 'add']);
    //تحديث حالة كتاب في القوائم
    Route::patch('/book_list/{bookId}', [UserBookListController::class, 'update']);
    //حذف كتاب من القوائم
    Route::delete('/book_list/{bookId}', [UserBookListController::class, 'delete']);
    //-------------------------------------------------------------------------------------------------------
// قائمة المفضلة
//عرض قائمة المفضلة
    Route::get('/favorites', [FavoriteController::class, 'index']);
    //إضافة كتاب إلى المفضلة
    Route::post('/add_favorites', [FavoriteController::class, 'add']);
    //حذف كتاب من المفضلة
    Route::delete('/delete_favorites/{bookId}', [FavoriteController::class, 'delete']);
    //--------------------------------------------------------------------------------------------------------
// اقتراحات الكتب
//إضافة اقتراح جديد
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    //عرض قائمة اقتراحات المستخدم
    Route::get('/suggestions/mine', [SuggestionController::class, 'mySuggestions']);
    //عرض قائمة الاقتراحات للمدير
    Route::get('/suggestions', [SuggestionController::class, 'index']);
    //موافقة المدير على الاقتراح
    Route::post('/suggestions/{id}/accept', [SuggestionController::class, 'accept']);
    //رفض المدير للاقتراح
    Route::post('/suggestions/{id}/reject', [SuggestionController::class, 'reject']);
    //عرض قائمة الكتب المقترحة لكتاب معين
    Route::get('/books/{bookId}/suggestions', [SuggestionController::class, 'bookSuggestions']);
    //----------------------------------------------------------------------------------------------
//الكتب
//البحث عن كتاب
    Route::get('/books/search', [BookController::class, 'search']);
    //عرض جميع الكتب
    Route::get('/books', [BookController::class, 'index']);
    //عرض تفاصيل كتاب معين
    Route::get('/books/{id}', [BookController::class, 'show']);
    //----------------------------------------------------------------------------------------------------------
//المسارات الخاصة بالتعليقات
//إضافة تعليق جديد
    Route::post('/comments', [CommentController::class, 'add']);
    //الرد على تعليق
    Route::post('/comments/{commentId}/reply', [CommentController::class, 'reply']);
    //حذف تعليق أو رد على تعليق
    Route::delete('/comments/{commentId}', [CommentController::class, 'delete']);
    //عرض التعليقات والردود لكتاب معين
    Route::get('/comments/{bookId}', [CommentController::class, 'index']);
    //تعديل تعليق
    Route::put('/comments/{commentId}', [CommentController::class, 'update']);
    //----------------------------------------------------------------------------------------------------------
//المسارات الخاصة بالتقييمات
//إضافة تقييم جديد أو تحديث تقييم موجود
    Route::post('/ratings/{bookId}', [RatingController::class, 'rate']);
    //حساب متوسط التقييم لكتاب معين
    Route::get('/ratings/{bookId}/average', [RatingController::class, 'average']);
    //حذف تقييم
    Route::delete('/ratings/{bookId}', [RatingController::class, 'delete']);
    //عرض تقييم المستخدم لكتاب معين
    Route::get('/ratings/{bookId}', [RatingController::class, 'show']);
    //----------------------------------------------------------------------------------------------------------

    //----------------------------------------------------------------------------------------------------------
//المسارات الخاصة بالمدير
//إضافة كتاب جديد (للمدير فقط)
    Route::post('/books', [BookController::class, 'add']);
    //تعديل كتاب (للمدير فقط)
    Route::put('/books/{id}', [BookController::class, 'update']);
    //حذف كتاب (للمدير فقط)
    Route::delete('/admin/books/{id}', [BookController::class, 'delete']);
    //عرض جميع المستخدمين 
    Route::get('/admin/users-progress', [AdminController::class, 'usersProgress']);
    // عرض جميع الكتب مع تقييماتها
    Route::get('/admin/books-with-ratings', [AdminController::class, 'booksWithRatings']);
});
//----------------------------------------------------------------------------------------------------------
//تسجيل دخول المدير
Route::post('/admin/login', [AdminController::class, 'login']);
//إنشاء حساب للمدير
Route::middleware('auth:sanctum')->post('/admin/register', [AdminController::class, 'register']);

