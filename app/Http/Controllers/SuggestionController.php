<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;

use App\Models\Book;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use App\Notifications\SuggestionStatus;

class SuggestionController extends Controller
{
	// إضافة اقتراح من قبل المستخدم
	public function store(Request $request)
	{
		$validated = $request->validate([
			'title' => 'required|string|max:255',
			'author' => 'required|string|max:255',
			'description' => 'nullable|string',
			'related_book_id' => 'nullable|exists:books,id',
		]);
		$user = $request->user();
		if (!$user) {
			return response()->json([
				'success' => false,
				'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
			], 401);
		}

		$userIdColumn = 'user_id';
		$relatedBookIdColumn = 'related_book_id';
		$statusColumn = 'status';

		$suggestion = Suggestion::create([
			$userIdColumn => $user->id,
			'title' => $validated['title'],
			'author' => $validated['author'],
			'description' => $validated['description'] ?? null,
			$statusColumn => Suggestion::STATUS_PENDING,
			$relatedBookIdColumn => $validated['related_book_id'] ?? null,
		]);
		return response()->json([
			'success' => true,
			'message' => 'تم إرسال الاقتراح بنجاح',
			'data' => $suggestion,
		], 201);
	}
	//-----------------------------------------------------------------------------------------------
//عرض اقتراحات المستخدم الحالي
	public function mySuggestions(Request $request)
	{
		$user = $request->user();
		if (!$user) {
			return response()->json([
				'success' => false,
				'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
			], 401);
		}

		$userIdColumn = 'user_id';
		$suggestions = Suggestion::where($userIdColumn, $user->id)->with('relatedBook')->get();
		return response()->json([
			'success' => true,
			'data' => $suggestions,
		], 200);
	}
	//--------------------------------------------------------------------------------------------------
//عرض قائمة جميع الاقتراحات عند المدير
	public function index(Request $request)
	{
		if (!$request->user() instanceof Admin) {
			return response()->json([
				'success' => false,
				'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
			], 403);
		}
		$createdAtColumn = 'created_at';
		$suggestions = Suggestion::with(['user', 'relatedBook'])->orderBy($createdAtColumn, 'desc')->get();
		return response()->json([
			'success' => true,
			'data' => $suggestions,
		], 200);
	}
	//-----------------------------------------------------------------------------------------------------
//موافقة المدير على اقتراح الكتاب
	public function accept(Request $request,int $id)
	{
		if (!$request->user() instanceof Admin) {
			return response()->json([
				'success' => false,
				'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
			], 403);
		}
		$admin = $request->user();
		$suggestion = Suggestion::findOrFail($id);
		if ($suggestion->status !== Suggestion::STATUS_PENDING) {
			return response()->json([
				'success' => false,
				'message' => 'تمت مراجعة هذا الاقتراح بالفعل',
			], 400);
		}

		$suggestion->update([
			'status' => Suggestion::STATUS_ACCEPTED,
			'admin_id' => $admin->id,
			'reviewed_at' => now(),
		]);
		//  إرسال إشعار
		$suggestion->user->notify(
			new SuggestionStatus(
				'تم قبول اقتراحك، سنحاول توفير الكتاب قريباً',
				'accepted'
			)
		);
		return response()->json([
			'success' => true,
			'message' => 'تم قبول الاقتراح ',
			'data' => $suggestion->load('book'),
		], 200);
	}
	//---------------------------------------------------------------------------------------------------------
	//رفض المدير للاقتراح
	public function reject(Request $request,int $id)
	{
		if (!$request->user() instanceof Admin) {
			return response()->json([
				'success' => false,
				'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
			], 403);
		}

		$admin = $request->user();
		$suggestion = Suggestion::findOrFail($id);

		if ($suggestion->status !== Suggestion::STATUS_PENDING) {
			return response()->json([
				'success' => false,
				'message' => 'تمت مراجعة هذا الاقتراح بالفعل',
			], 400);
		}

		// رفض الاقتراح
		$suggestion->update([
			'status' => Suggestion::STATUS_REJECTED,
			'admin_id' => $admin->id,
			'reviewed_at' => now(),
		]);

		// إرسال إشعار
		$suggestion->user->notify(
			new SuggestionStatus(
				'نعتذر تم رفض هذا الاقتراح بسبب مشكلة معينة أو وجود هذا الكتاب بالفعل',
				'rejected'
			)
		);

		return response()->json([
			'success' => true,
			'message' => 'تم رفض الاقتراح',
			'data' => $suggestion,
		], 200);
	}

	//-----------------------------------------------------------------------------------------------------------
//عرض الكتب المشابهة للكتاب المقترح
	public function bookSuggestions(int $bookId)
	{
		$relatedBookIdColumn = 'related_book_id';
		$bookIdColumn = 'book_id';
		$suggestions = Suggestion::where($relatedBookIdColumn, $bookId)
			->orWhere($bookIdColumn, $bookId)
			->with('user')
			->get();
		return response()->json([
			'success' => true,
			'data' => $suggestions,
		], 200);
	}
	//--------------------------------------------------------------------------------------------------------------
}
