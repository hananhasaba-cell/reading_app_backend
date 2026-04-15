<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
	// عرض قائمة المفضلة للمستخدم الحالي
	public function index(Request $request)
	{
		$user = $request->user();
		$favorites = $user->favorites()->with('book')->get();
		return response()->json([
			'success' => true,
			'data' => $favorites,
		], 200);
	}
//--------------------------------------------------------------------------------------------------
	// إضافة كتاب إلى المفضلة
	public function add(Request $request)
	{
		$validated = $request->validate([
			'book_id' => ['required', 'integer', 'exists:books,id'],
		]);
		$user = $request->user();
		$exists = $user->favorites()->where('book_id', $validated['book_id'])->exists();
		if ($exists) {
			return response()->json([
				'success' => false,
				'message' => 'الكتاب موجود بالفعل في المفضلة',
			], 409);
		}
		$favorite = $user->favorites()->create(['book_id' => $validated['book_id']]);
		return response()->json([
			'success' => true,
			'message' => 'تمت إضافة الكتاب إلى المفضلة',
			'data' => $favorite->load('book'),
		], 201);
	}
//--------------------------------------------------------------------------------------------------
	// حذف كتاب من المفضلة
	public function delete(Request $request, $bookId)
	{
		$user = $request->user();
		$favorite = $user->favorites()->where('book_id', $bookId)->first();
		if (! $favorite) {
			return response()->json([
				'success' => false,
				'message' => 'الكتاب غير موجود في المفضلة',
			], 404);
		}
		$favorite->delete();
		return response()->json([
			'success' => true,
			'message' => 'تم حذف الكتاب من المفضلة',
		], 200);
	}
}

