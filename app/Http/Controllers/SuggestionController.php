<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;

use App\Models\Book;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

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
		$suggestion = Suggestion::create([
			'user_id' => $user->id,
			'title' => $validated['title'],
			'author' => $validated['author'],
			'description' => $validated['description'] ?? null,
			'status' => Suggestion::STATUS_PENDING,
			'related_book_id' => $validated['related_book_id'] ?? null,
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
		$suggestions = Suggestion::where('user_id', $user->id)->with('relatedBook')->get();
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
		$suggestions = Suggestion::with(['user', 'relatedBook'])->orderBy('created_at', 'desc')->get();
		return response()->json([
			'success' => true,
			'data' => $suggestions,
		], 200);
	}
//-----------------------------------------------------------------------------------------------------
//موافقة المدير على اقتراح الكتاب
	public function accept(Request $request, $id)
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
		// إرسال إيميل للمستخدم بأنه تم قبول اقتراحه
		$this->sendSuggestionStatusMail($suggestion, true);
		return response()->json([
			'success' => true,
			'message' => 'تم قبول الاقتراح ',
			'data' => $suggestion->load('book'),
		], 200);
	}
//---------------------------------------------------------------------------------------------------------
	//رفض المدير للاقتراح
	public function reject(Request $request, $id)
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
			'status' => Suggestion::STATUS_REJECTED,
			'admin_id' => $admin->id,
			'reviewed_at' => now(),
		]);
		// إرسال إيميل للمستخدم بأنه تم رفض اقتراحه
		$this->sendSuggestionStatusMail($suggestion, false);
		return response()->json([
			'success' => true,
			'message' => 'تم رفض الاقتراح',
			'data' => $suggestion,
		], 200);
	}
//-----------------------------------------------------------------------------------------------------------
//عرض الكتب المشابهة للكتاب المقترح
	public function bookSuggestions($bookId)
	{
		$suggestions = Suggestion::where('related_book_id', $bookId)->orWhere('book_id', $bookId)->with('user')->get();
		return response()->json([
			'success' => true,
			'data' => $suggestions,
		], 200);
	}
//--------------------------------------------------------------------------------------------------------------
	//إرسال إيميل للمستخدم
	protected function sendSuggestionStatusMail(Suggestion $suggestion, $accepted)
	{
		$user = $suggestion->user;
		$status = $accepted ? 'تم قبول الاقتراح، سنحاول توفير الكتاب بأقرب وقت' : 'نعتذر تم رفض هذا الاقتراح بسبب مشكلة معينة أو وجود هذا الكتاب بالفعل';
		Mail::to($user->email)->send(
			new \App\Mail\SuggestionStatusMail($user, $suggestion, $status, $accepted)
		);
	}
}
