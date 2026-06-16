<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // إضافة تعليق جديد
    public function add(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'book_id' => $request->book_id,
            'content' => $request->get('content'),
        ]);
        $comment->load('user:id,name,profile_img');
        $comment->load('book:id,title');
        return response()->json(
            [
                'message' => 'تمت إضافة التعليق بنجاح',
                'comment' => $comment
            ],
            201
        );
    }
    //--------------------------------------------------------------------------------------------------------------
    // الرد على تعليق
    public function reply(Request $request, $commentId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $parentComment = Comment::findOrFail($commentId);

        // إنشاء الرد
        $reply = Comment::create([
            'user_id' => auth()->id(),
            'book_id' => $parentComment->book_id,
            'parent_id' => $parentComment->id,
            'content' => $request->get('content'),
        ]);

        // تحميل بيانات المستخدم
        $reply->load('user:id,name,profile_img');

        // بعد إضافة الرد نرجع الشجرة كاملة  
        $comments = Comment::where('book_id', $parentComment->book_id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,profile_img',
                'replies' => function ($q) {
                    $q->orderBy('created_at', 'asc')
                        ->with([
                            'user:id,name,profile_img',
                            'replies' => function ($q2) {
                                $q2->orderBy('created_at', 'asc')
                                    ->with('user:id,name,profile_img');
                            }
                        ]);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الرد بنجاح',
            'reply' => $reply,
            'comments' => $comments //  شجرة الردود
        ], 201);
    }

    //--------------------------------------------------------------------------------------------------------------
    //  حذف تعليق أو رد على تعليق
    public function delete($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // التأكد أن المستخدم هو صاحب التعليق أو الرد
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا التعليق'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'تم حذف التعليق بنجاح']);
    }
    //--------------------------------------------------------------------------------------------------------------
//تعديل تعليق
    public function update(Request $request, $commentId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::findOrFail($commentId);

        // التأكد أن المستخدم هو صاحب التعليق أو الرد
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا التعليق'], 403);
        }

        $comment->content = $request->get('content');
        $comment->save();

        return response()->json(['message' => 'تم تعديل التعليق بنجاح', 'comment' => $comment]);
    }
    //--------------------------------------------------------------------------------------------------------------
// عرض التعليقات والردود لكتاب معين
    public function index($bookId)
    {
        // جلب التعليقات الرئيسية بترتيب تنازلي
        $comments = Comment::where('book_id', $bookId)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,profile_img',
                'replies' => function ($q) {
                    $q->orderBy('created_at', 'asc')
                        ->with([
                            'user:id,name,profile_img',
                            'replies' => function ($q2) {
                                $q2->orderBy('created_at', 'asc')
                                    ->with('user:id,name,profile_img');
                            }
                        ]);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments
        ]);
    }
    //--------------------------------------------------------------------------------------------------------------

}
