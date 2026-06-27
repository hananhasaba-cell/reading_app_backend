<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Book;

class CommentController extends Controller
{
    // إضافة تعليق جديد
   public function add(Request $request)
{
    $request->validate([
        'book_id' => 'required|exists:books,id',
        'content' => 'required|string|max:1000',
    ]);

    $user = $request->user();

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بإجراء هذا الطلب',
        ], 401);
    }

    //  جلب الكتاب
    $book = Book::find((int) $request->input('book_id'));

    //  هل المستخدم دفع؟
    $hasPaid = Payment::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->where('status', 'succeeded')
        ->exists();

    //  منع التعليق حسب نوع الكتاب

    // كتاب مدفوع  يجب الدفع
    if ($book->access_type === 'paid' && ! $hasPaid) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك التعليق على كتاب مدفوع قبل شرائه.',
        ], 403);
    }

    // كتاب تجريبي يجب الدفع للتعليق
    if ($book->access_type === 'trial' && ! $hasPaid) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك التعليق على كتاب تجريبي قبل شرائه.',
        ], 403);
    }

    // كتاب مشروط يجب إنهاء العدد المطلوب
    if ($book->access_type === 'conditional') {
        $finishedBooks = $user->finishedReading()->count();

        if ($finishedBooks < $book->required_books_read) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك التعليق على هذا الكتاب قبل فتحه وقراءته.',
            ], 403);
        }
    }

    //  مسموح بالتعليق
    $comment = Comment::create([
        'user_id' => $user->getAttribute('id'),
        'book_id' => (int) $request->input('book_id'),
        'content' => (string) $request->input('content'),
    ]);

    $comment->load([
        'user' => fn ($query) => $query->select('id', 'name', 'profile_img'),
        'book' => fn ($query) => $query->select('id', 'title'),
    ]);

    return response()->json(
        [
            'message' => 'تمت إضافة التعليق بنجاح',
            'comment' => $comment,
        ],
        201
    );
}
    //--------------------------------------------------------------------------------------------------------------
    // الرد على تعليق
    public function reply(Request $request, int $commentId)
{
    $request->validate([
        'content' => 'required|string|max:1000',
    ]);

    $user = $request->user();

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بإجراء هذا الطلب',
        ], 401);
    }

    $parentComment = Comment::query()->findOrFail($commentId);

    //  جلب الكتاب
    $book = Book::find((int) $parentComment->getAttribute('book_id'));

    //  هل المستخدم دفع؟
    $hasPaid = Payment::where('user_id', $user->getAttribute('id'))
        ->where('book_id', $book->getAttribute('id'))
        ->where('status', 'succeeded')
        ->exists();

    //  منع الرد حسب نوع الكتاب
    // كتاب مدفوع يجب الدفع
    if ($book->access_type === 'paid' && ! $hasPaid) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك الرد على كتاب مدفوع قبل شرائه.',
        ], 403);
    }

    // كتاب تجريبي يجب الدفع
    if ($book->access_type === 'trial' && ! $hasPaid) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك الرد على كتاب تجريبي قبل شرائه.',
        ], 403);
    }

    // كتاب مشروط يجب إنهاء العدد المطلوب
    if ($book->access_type === 'conditional') {
        $finishedBooks = $user->finishedReading()->count();

        if ($finishedBooks < $book->required_books_read) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك الرد على هذا الكتاب قبل فتحه وقراءته.',
            ], 403);
        }
    }

    //   الرد مسموح
    $reply = Comment::create([
        'user_id' => $user->getAttribute('id'),
        'book_id' => (int) $parentComment->getAttribute('book_id'),
        'parent_id' => (int) $parentComment->getAttribute('id'),
        'content' => (string) $request->input('content'),
    ]);

    // تحميل بيانات المستخدم
    $reply->load([
        'user' => fn ($query) => $query->select('id', 'name', 'profile_img'),
    ]);

    // بعد إضافة الرد نرجع الشجرة كاملة
    $bookColumn = 'book_id';
    $createdAtColumn = 'created_at';

    $comments = Comment::query()
        ->where($bookColumn, (int) $parentComment->getAttribute('book_id'))
        ->whereNull('parent_id')
        ->with('user:id,name,profile_img')
        ->with([
            'replies' => function ($replyQuery) use ($createdAtColumn) {
                $replyQuery->orderBy($createdAtColumn, 'asc')
                    ->with('user:id,name,profile_img')
                    ->with(['replies' => function ($nestedQuery) use ($createdAtColumn) {
                        $nestedQuery->orderBy($createdAtColumn, 'asc')
                            ->with('user:id,name,profile_img');
                    }]);
            },
        ])
        ->orderBy($createdAtColumn, 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'تمت إضافة الرد بنجاح',
        'reply' => $reply,
        'comments' => $comments,
    ], 201);
}
    //--------------------------------------------------------------------------------------------------------------
    //  حذف تعليق أو رد على تعليق
    public function delete(Request $request, int $commentId)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذا الطلب',
            ], 401);
        }

        $comment = Comment::query()->findOrFail($commentId);

        // التأكد أن المستخدم هو صاحب التعليق أو الرد
        if ((int) $comment->getAttribute('user_id') !== (int) $user->getAttribute('id')) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا التعليق'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'تم حذف التعليق بنجاح']);
    }
    //--------------------------------------------------------------------------------------------------------------
//تعديل تعليق
public function update(Request $request, int $commentId)
{
    $request->validate([
        'content' => 'required|string|max:1000',
    ]);

    $user = $request->user();

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بإجراء هذا الطلب',
        ], 401);
    }

    $comment = Comment::query()->findOrFail($commentId);

    // التأكد أن المستخدم هو صاحب التعليق أو الرد
    if ((int) $comment->getAttribute('user_id') !== (int) $user->getAttribute('id')) {
        return response()->json(['message' => 'غير مصرح لك بتعديل هذا التعليق'], 403);
    }

    //  جلب الكتاب
    $book = Book::find((int) $comment->getAttribute('book_id'));

    //  هل المستخدم دفع؟
    $hasPaid = Payment::where('user_id', $user->getAttribute('id'))
        ->where('book_id', $book->getAttribute('id'))
        ->where('status', 'succeeded')
        ->exists();

    //  منع التعديل حسب نوع الكتاب
    // كتاب مدفوع يجب الدفع
    if ($book->access_type === 'paid' && ! $hasPaid) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك تعديل تعليق على كتاب مدفوع قبل شرائه.',
        ], 403);
    }

    // كتاب تجريبي يجب الدفع
    if ($book->access_type === 'trial' && ! $hasPaid) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك تعديل تعليق على كتاب تجريبي قبل شرائه.',
        ], 403);
    }

    // كتاب مشروط يجب إنهاء العدد المطلوب
    if ($book->access_type === 'conditional') {
        $finishedBooks = $user->finishedReading()->count();

        if ($finishedBooks < $book->required_books_read) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل تعليق على هذا الكتاب قبل فتحه وقراءته.',
            ], 403);
        }
    }

    //  التعديل مسموح
    $comment->setAttribute('content', (string) $request->input('content'));
    $comment->save();

    return response()->json([
        'message' => 'تم تعديل التعليق بنجاح',
        'comment' => $comment
    ]);
}
    //--------------------------------------------------------------------------------------------------------------
// عرض التعليقات والردود لكتاب معين
    public function index(int $bookId)
    {
        // جلب التعليقات الرئيسية بترتيب تنازلي
        $bookColumn = 'book_id';
        $createdAtColumn = 'created_at';

        $comments = Comment::query()
            ->where($bookColumn, $bookId)
            ->whereNull('parent_id')
            ->with('user:id,name,profile_img')
            ->with([
                'replies' => function ($replyQuery) use ($createdAtColumn) {
                    $replyQuery->orderBy($createdAtColumn, 'asc')
                        ->with('user:id,name,profile_img')
                        ->with(['replies' => function ($nestedQuery) use ($createdAtColumn) {
                            $nestedQuery->orderBy($createdAtColumn, 'asc')
                                ->with('user:id,name,profile_img');
                        }]);
                },
            ])
            ->orderBy($createdAtColumn, 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }
    //--------------------------------------------------------------------------------------------------------------

}
