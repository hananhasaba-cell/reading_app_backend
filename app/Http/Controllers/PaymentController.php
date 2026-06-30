<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentController extends Controller
{
    // بدء عملية التحقق من الدفع 
public function checkout(Request $request)
{
    $request->validate([
        'book_id' => 'required|integer|exists:books,id',
        'amount' => 'required|numeric|min:0',
        'currency' => 'nullable|string|max:10',
        'is_test' => 'nullable|boolean',
    ]);

    $user = $request->user();
    $bookId = $request->book_id;
    $isTest = (bool) $request->get('is_test', false);
// منع شراء نفس الكتاب مرتين
$alreadyPaid = Payment::where('user_id', $user->id)
    ->where('book_id', $bookId)
    ->where('status', 'succeeded')
    ->exists();

if ($alreadyPaid) {
    return response()->json([
        'success' => false,
        'message' => 'لقد قمت بشراء هذا الكتاب مسبقاً.',
    ], 409);
}
    // إنشاء عملية دفع
    $payment = Payment::create([
        'user_id' => $user->id,
        'book_id' => $bookId,
        'amount' => $request->amount,
        'currency' => $request->get('currency', 'USD'),
        'gateway' => $isTest ? 'fiction' : null,
        'gateway_id' => $isTest ? 'fiction_' . uniqid() : null,
        'status' => $isTest ? 'succeeded' : 'pending',
        'is_test' => $isTest,
    ]);

    return response()->json([
        'success' => true,
        'payment' => $payment,
        'message' => $isTest
            ? 'تمت معالجة الدفع الوهمي بنجاح.'
            : 'تم إنشاء عملية الدفع. أكمل الدفع عبر بوابة الدفع.',
    ]);
}

//------------------------------------------------------------------------------------------------------------
    //تابع تأكيد الدفع 
   public function confirm(Request $request)
{
    $request->validate([
        'payment_id' => 'nullable|integer|exists:payments,id',
        'gateway_id' => 'nullable|string',
        'status' => 'nullable|in:succeeded,failed,refunded',
    ]);

    $status = $request->get('status', 'succeeded');
    $payment = null;

    if ($request->payment_id) {
        $payment = Payment::find($request->payment_id);
    } elseif ($request->gateway_id) {
        $payment = Payment::where('gateway_id', $request->gateway_id)->first();
    }

    if (!$payment) {
        return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
    }

    $payment->status = $status;
    $payment->save();

    return response()->json([
        'success' => true,
        'payment' => $payment,
        'message' => $status === 'succeeded'
            ? 'تم تأكيد الدفع بنجاح.'
            : 'فشل الدفع.',
    ]);
}
}