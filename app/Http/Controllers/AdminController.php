<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Book;
class AdminController extends Controller
{

public function register(Request $request)
{
    //إنشاء حساب للمدراء فقط
    // السماح للمدير فقط بإنشاء مدير جديد
    if (! $request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }

    // التحقق من البيانات
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|unique:admins,email',
        'password' => 'required|string|min:6',
    ]);

    // إنشاء المدير الجديد
    $admin = Admin::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => bcrypt($validated['password']),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم إنشاء حساب المدير بنجاح',
        'admin' => $admin
    ], 201);
}

//--------------------------------------------------------------------------------------------------------
    // تسجيل دخول للمدير فقط
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات تسجيل الدخول غير صحيحة'
            ], 401);
        }

        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'admin' => $admin
        ], 200);
    }
//-----------------------------------------------------------------------------------------------
//عرض جميع المستخدمين مع عدد النقاط المكتسبة ونسبة التقدم في الأهداف الأسبوعية
public function usersProgress(Request $request)
{
        if (! $request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }
    $users = User::with('progress')->get()->map(function ($user) {

        $user->points = $user->progress->sum('points_earned');

        $user->progress_percentage = $user->progress->count()
            ? $user->progress->avg('progress_percentage')
            : 0;

        return $user;
    });

    return response()->json([
        'success' => true,
        'data' => $users,
    ], 200);
}
//---------------------------------------------------------------------------------------------------------
// عرض جميع الكتب مع تقييماتها
public function booksWithRatings(Request $request)
{
            if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }
    $books = Book::with('ratings')->get()->map(function ($book) {
        $book->average_rating = $book->ratings->avg('rating') ?? 0;
        return $book;
    });

    return response()->json([
        'success' => true,
        'data' => $books,
    ], 200);
}
//---------------------------------------------------------------------------------------------------------
}