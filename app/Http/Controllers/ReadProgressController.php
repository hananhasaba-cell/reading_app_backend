<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReadProgress;
use App\Models\Book;
use App\Models\Payment;

class ReadProgressController extends Controller
{
    // تابع تحديث حالة التقدم في القراءة للمستخدم
    public function update(Request $request)
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'pages' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $bookId = $request->book_id;
        $pages = (int) $request->pages;

        $book = Book::find($bookId);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'الكتاب غير موجود.',
                'allowed' => false
            ], 404);
        }

        // هل المستخدم دفع ثمن الكتاب؟
        $hasPaid = Payment::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'succeeded')
            ->exists();

        // منع القراءة في الكتب المدفوعة إذا لم يدفع
        if ($book->access_type === 'paid' && !$hasPaid) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الكتاب مدفوع. يرجى الدفع لفتحه.',
                'allowed' => false
            ], 403);
        }

        // منع القراءة في الكتب المشروطة
        if ($book->access_type === 'conditional') {
            $finishedBooks = $user->finishedReading()->count();

            if ($finishedBooks < $book->required_books_read) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك قراءة هذا الكتاب قبل إنهاء عدد الكتب المطلوب.',
                    'allowed' => false
                ], 403);
            }
        }

        //  جلب التقدم الحالي أو إنشاؤه
        $progress = ReadProgress::firstOrCreate(
            ['user_id' => $user->id, 'book_id' => $bookId],
            ['pages_read' => 0]
        );

//  منع القفز في الصفحات ( أكثر من 50 صفحة دفعة واحدة)
if ($pages > 50) {
    return response()->json([
        'success' => false,
        'message' => 'لا يمكنك تجاوز عدد كبير من الصفحات دفعة واحدة.',
        'allowed' => false
    ], 403);
}
//  منع التراجع في القراءة 
if ($progress->pages_read + $pages < $progress->pages_read) {
    return response()->json([
        'success' => false,
        'message' => 'لا يمكنك تقليل عدد الصفحات المقروءة.',
        'allowed' => false
    ], 403);
}

        //منطق التجريبي
        if ($book->access_type === 'trial') {

            // إذا دفع افتح كامل الكتاب
            if ($hasPaid) {
                $progress->pages_read = min($progress->pages_read + $pages, $book->PageNumber);
            } else {
                // لم يدفع طبق الحد التجريبي
                $current = $progress->pages_read;
                $limit = $book->trial_pages;

                if ($current >= $limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد وصلت إلى الحد التجريبي لهذا الكتاب. يرجى الدفع لمتابعة القراءة.',
                        'allowed' => false
                    ], 403);
                }

                // لا نسمح بتجاوز الحد
                $progress->pages_read = min($current + $pages, $limit);
            }
        }
        else {
            //  مجاني أو مشروط أو مدفوع (بعد الدفع)
            $progress->pages_read = min($progress->pages_read + $pages, $book->PageNumber);
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'allowed' => true,
            'pages_read' => $progress->pages_read,
        ]);
    }
}
