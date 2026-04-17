<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Book;
use App\Models\UserBookList;
class AdminController extends Controller
{

    public function register(Request $request)
    {
        //إنشاء حساب للمدراء فقط
        // السماح للمدير فقط بإنشاء مدير جديد
        if (!$request->user() instanceof Admin) {
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

        if (!$admin || !Hash::check($request->password, $admin->password)) {
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
//عرض جميع المستخدمين مع عدد الكتب المقروءة لكل مستخدم
    public function usersProgress(Request $request)
    {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
            ], 403);
        }

        $users = User::withCount('finishedReading')->get()->map(function (User $user) {
            $finishedCount = $user->finished_reading_count;

            $nickname = app(UsersController::class)->getReaderTitle($finishedCount);
            return [
                'name' => $user->name,
                'nickname' => $nickname,
                'books_read' => $user->finished_reading_count,
            ];
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

        // تحميل التقييمات + التصنيفات
        $books = Book::with(['ratings', 'geners'])->get()->map(function ($book) {

            $book->average_rating = $book->ratings->avg('rating') ?? 0;

            return [
                'title' => $book->title,
                'author' => $book->author,
                'pages' => $book->PageNumber,
                'cover_img' => $book->cover_image
                    ? asset('storage/' . $book->cover_image)
                    : null,

                'pdf' => $book->pdf_path
                    ? asset('storage/' . $book->pdf_path)
                    : null,

                'average_rating' => $book->average_rating,

                // إضافة التصنيفات 
                'geners' => $book->geners->map(function ($gener) {
                    return [
                        'id' => $gener->id,
                        'name' => $gener->name,
                    ];
                }),

                'ratings' => $book->ratings->map(function ($rating) {
                    return [
                        'user_id' => $rating->user_id,
                        'rating' => $rating->rating,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $books,
        ], 200);
    }

    //---------------------------------------------------------------------------------------------------------
}