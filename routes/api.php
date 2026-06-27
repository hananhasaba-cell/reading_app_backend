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
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReadProgressController;
use App\Http\Middleware\AdminOnly;


// مسارات عامة (بدون تسجيل دخول)

// إنشاء حساب مستخدم
Route::post('/register', [UsersController::class, 'register']);
// تسجيل دخول مستخدم
Route::post('/login', [UsersController::class, 'login'])->middleware('throttle:login');
// عرض جميع الكتب
Route::get('/books', [BookController::class, 'index']);
// البحث عن كتاب
Route::get('/books/search', [BookController::class, 'search']);
// تسجيل دخول المدير
Route::post('/admin/login', [AdminController::class, 'login']);
//-----------------------------------------------------------------------------------------------
// مسارات محمية للمستخدم (تحتاج auth:sanctum)

Route::middleware('auth:sanctum')->group(function () {

    // معلومات المستخدم
    // بيانات الملف الشخصي
    Route::get('/info', [UsersController::class, 'info']);
    // تحديث بيانات الملف الشخصي
    Route::put('/update', [UsersController::class, 'update']);
    // تسجيل خروج
    Route::post('/logout', [UsersController::class, 'logout']);
    // حذف الحساب
    Route::delete('/delete-account', [UsersController::class, 'deleteAccount']);

    // المتابعة
    // متابعة مستخدم
    Route::post('/follow/{userId}', [UsersController::class, 'follow']);
    // إلغاء متابعة مستخدم
    Route::delete('/unfollow/{userId}', [UsersController::class, 'unfollow']);
    // عرض المتابعين والذين أتابعهم
    Route::get('/followers', [UsersController::class, 'getFollowers']);
    Route::get('/following', [UsersController::class, 'getFollowing']);
    // عرض معلومات المستخدم الذي تتم متابعته ( عدد المتابعين، عدد الذين أتابعهم، عدد الكتب المقروءة)
    Route::get('/followed_users/{followedUserId}', [UsersController::class, 'getFollowedUser']);

    // الإشعارات
    // عرض إشعارات المستخدم
    Route::get('/notifications', [UsersController::class, 'userNotifications']);
    // وضع الجميع كمقروءة
    Route::post('/all_read/{id}', [UsersController::class, 'markAsRead']);
    //عرض جميع المستخدمين مع عدد الكتب المقروءة لكل مستخدم
    Route::get('/users_pogress_list', [UsersController::class, 'usersProgress']);

    // قوائم الكتب
    // عرض قوائم القراءة    
    Route::get('/book_list', [UserBookListController::class, 'index']);
    // إضافة كتاب للقوائم
    Route::post('/book_list', [UserBookListController::class, 'add']);
    // تحديث حالة كتاب في القوائم (مثل: "قراءة", "مكتمل", "أريد قراءته")
    Route::patch('/book_list/{bookId}', [UserBookListController::class, 'update']);
    // حذف كتاب من القوائم
    Route::delete('/book_list/{bookId}', [UserBookListController::class, 'delete']);

    // المفضلة
    // عرض المفضلة
    Route::get('/favorites', [FavoriteController::class, 'index']);
    // إضافة للمفضلة
    Route::post('/add_favorites', [FavoriteController::class, 'add']);
    // حذف من المفضلة
    Route::delete('/delete_favorites/{bookId}', [FavoriteController::class, 'delete']);

    // الاقتراحات (للمستخدم)
    // إضافة اقتراح
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    // عرض الاقتراحات للمستخدم نفسه
    Route::get('/suggestions/mine', [SuggestionController::class, 'mySuggestions']);
    //عرض اقتراحات الكتب المشابهة لكتاب معين
    Route::get('/books/{bookId}/suggestions', [SuggestionController::class, 'bookSuggestions']);

    // التعليقات
    // إضافة تعليق
    Route::post('/comments', [CommentController::class, 'add']);
    // الرد على تعليق
    Route::post('/comments/{commentId}/reply', [CommentController::class, 'reply']);
    // حذف تعليق
    Route::delete('/comments/{commentId}', [CommentController::class, 'delete']);
    // عرض التعليقات الخاصة بكتاب معين
    Route::get('/comments/{bookId}', [CommentController::class, 'index']);
    // تحديث تعليق
    Route::put('/comments/{commentId}', [CommentController::class, 'update']);

    // التقييمات
    // إضافة تقييم
    Route::post('/ratings/{bookId}', [RatingController::class, 'rate']);
    // حساب متوسط تقييم
    Route::get('/ratings/{bookId}/average', [RatingController::class, 'average']);
    //حذف تقييم
    Route::delete('/ratings/{bookId}', [RatingController::class, 'delete']);
    // عرض التقييمات
    Route::get('/ratings/{bookId}', [RatingController::class, 'show']);

    // عرض تفاصيل كتاب (يحتاج تسجيل دخول لفتح PDF)
    Route::get('/books/{id}', [BookController::class, 'show']);

//-------------------------------------------------------------------------------------------------------
    // مسارات المدير فقط (محمي بـ AdminOnly)

    Route::middleware(AdminOnly::class)->group(function () {
        // إضافة كتاب جديد (مدير فقط)
        Route::post('/books', [BookController::class, 'add']);
        // تعديل كتاب (مدير فقط)
        Route::put('/books/{id}', [BookController::class, 'update']);
        // حذف كتاب (مدير فقط)
        Route::delete('/admin/books/{id}', [BookController::class, 'delete']);
        // عرض جميع المستخدمين (لوحة المدير)
        Route::get('/admin/users-progress', [AdminController::class, 'usersProgress']);
        // عرض جميع الكتب مع تقييماتها
        Route::get('/admin/books-with-ratings', [AdminController::class, 'booksWithRatings']);
        // عرض جميع الاقتراحات (مدير فقط)
        Route::get('/suggestions', [SuggestionController::class, 'index']);
        // قبول اقتراح
        Route::post('/suggestions/{id}/accept', [SuggestionController::class, 'accept']);
        // رفض اقتراح
        Route::post('/suggestions/{id}/reject', [SuggestionController::class, 'reject']);

        // التحليلات
        // أرباح الكتب
        Route::get('/analytics/books_earnings', [AnalyticsController::class, 'booksEarnings']);
        // أرباح المؤلفين
        Route::get('/analytics/authors_earnings', [AnalyticsController::class, 'authorsEarnings']);
        // أرباح الفئات
        Route::get('/analytics/categories_earnings', [AnalyticsController::class, 'categoriesEarnings']);
        // أرباح الشهر
        Route::get('/analytics/monthly_earnings', [AnalyticsController::class, 'monthlyEarnings']);
        // أرباح السنة
        Route::get('/analytics/yearly_earnings', [AnalyticsController::class, 'yearlyEarnings']);
        // أكثر الكتب، المؤلفين، التصنيفات قراءة
        Route::get('/analytics/most_read', [AnalyticsController::class, 'mostRead']);
    });
//---------------------------------------------------------------------------------------------------------------
    // الدفع
    Route::post('/payment/checkout', [PaymentController::class, 'checkout']);
    Route::post('/payment/confirm', [PaymentController::class, 'confirm']);

    // تقدم القراءة
    Route::post('/read-progress/update', [ReadProgressController::class, 'update']);
});
//--------------------------------------------------------------------------------------------------------
// إنشاء حساب مدير (يحتاج تسجيل دخول مدير)
Route::middleware('auth:sanctum')->post('/admin/register', [AdminController::class, 'register']);
