<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    //إضافة تقييم جديد أو تحديث تقييم موجود
    public function rate(Request $request, $bookId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $rating = Rating::updateOrCreate(
            ['user_id' => auth()->id(), 'book_id' => $bookId],
            ['rating' => $request->get('rating')]
        );

        return response()->json([
            'message' => 'تمت إضافة/تحديث التقييم بنجاح',
            'rating' => $rating
        ], 200);
    }
//----------------------------------------------------------------------------------------
// حساب متوسط التقييم لكتاب معين
    public function average($bookId)
    {
        $averageRating = Rating::where('book_id', $bookId)->avg('rating');

        return response()->json([
            'average_rating' => round($averageRating, 2)
        ], 200);
    }
//-------------------------------------------------------------------------------------------
//    حذف تقييم
    public function delete($bookId)
    {
        $rating = Rating::where('user_id', auth()->id())->where('book_id', $bookId)->first();

        if (!$rating) {
            return response()->json(['message' => 'التقييم غير موجود'], 404);
        }

        $rating->delete();

        return response()->json(['message' => 'تم حذف التقييم بنجاح'], 200);
    }
//-------------------------------------------------------------------------------------------
//   عرض تقييم المستخدم لكتاب معين
    public function show($bookId)
    {
        $rating = Rating::where('user_id', auth()->id())->where('book_id', $bookId)->first();

        if (!$rating) {
            return response()->json(['message' => 'التقييم غير موجود'], 404);
        }

        return response()->json(['rating' => $rating->rating], 200);
    }    
//---------------------------------------------------------------------------------------------
//     
}
