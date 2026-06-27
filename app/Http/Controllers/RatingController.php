<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Models\Payment;
class RatingController extends Controller
{
    //إضافة تقييم جديد أو تحديث تقييم موجود
    public function rate(Request $request, int $bookId)
{
    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
    ]);

    $user = $request->user();
    $book = Book::find($bookId);

    if (!$book) {
        return response()->json(['message' => 'الكتاب غير موجود'], 404);
    }

    //  هل المستخدم دفع؟
    $hasPaid = Payment::where('user_id', $user->id)
        ->where('book_id', $bookId)
        ->where('status', 'succeeded')
        ->exists();

    //  منطق المنع حسب نوع الكتاب
    if ($book->access_type === 'paid' && !$hasPaid) {
        return response()->json([
            'message' => 'لا يمكنك تقييم كتاب مدفوع قبل شرائه.',
            'allowed' => false
        ], 403);
    }

    if ($book->access_type === 'trial') {
        // إذا لم يدفع لا يمكنه تقييم كتاب لم يقرأه كاملًا
        if (!$hasPaid) {
            return response()->json([
                'message' => 'لا يمكنك تقييم كتاب تجريبي قبل شرائه.',
                'allowed' => false
            ], 403);
        }
    }

    if ($book->access_type === 'conditional') {
        $finishedBooks = $user->finishedReading()->count();

        if ($finishedBooks < $book->required_books_read) {
            return response()->json([
                'message' => 'لا يمكنك تقييم هذا الكتاب قبل فتحه وقراءته.',
                'allowed' => false
            ], 403);
        }
    }

    //  مسموح بالتقييم
    $rating = Rating::updateOrCreate(
        ['user_id' => $user->id, 'book_id' => $bookId],
        ['rating' => $request->rating]
    );

    return response()->json([
        'message' => 'تمت إضافة/تحديث التقييم بنجاح',
        'rating' => $rating,
        'allowed' => true
    ], 200);
}
//----------------------------------------------------------------------------------------
// حساب متوسط التقييم لكتاب معين
    public function average(int $bookId)
    {
        $booknumber = 'book_id';
        $averageRating = Rating::where($booknumber, $bookId)->avg('rating');

        return response()->json([
            'average_rating' => round($averageRating, 2)
        ], 200);
    }
//-------------------------------------------------------------------------------------------
//    حذف تقييم
   public function delete(int $bookId)
{
    $user = auth()->user();
    $book = Book::find($bookId);

    if (!$book) {
        return response()->json(['message' => 'الكتاب غير موجود'], 404);
    }

    //  هل المستخدم دفع؟
    $hasPaid = Payment::where('user_id', $user->id)
        ->where('book_id', $bookId)
        ->where('status', 'succeeded')
        ->exists();

    if ($book->access_type === 'paid' && !$hasPaid) {
        return response()->json([
            'message' => 'لا يمكنك حذف تقييم كتاب لم تقم بشرائه.',
            'allowed' => false
        ], 403);
    }

    $rating = Rating::where('user_id', $user->id)
                    ->where('book_id', $bookId)
                    ->first();

    if (!$rating) {
        return response()->json(['message' => 'التقييم غير موجود'], 404);
    }

    $rating->delete();

    return response()->json(['message' => 'تم حذف التقييم بنجاح'], 200);
}
//-------------------------------------------------------------------------------------------
//   عرض تقييم المستخدم لكتاب معين
public function show(int $bookId)
{
    $rating = Rating::where('user_id', auth()->id())
                    ->where('book_id', $bookId)
                    ->first();

    if (!$rating) {
        return response()->json(['message' => 'التقييم غير موجود'], 404);
    }

    return response()->json(['rating' => $rating->rating], 200);
}
  
//---------------------------------------------------------------------------------------------
//     
}
