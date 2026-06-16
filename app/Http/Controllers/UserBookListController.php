<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\UserBookList;
use App\Notifications\ReaderLevelUp;
use Illuminate\Http\Request;

class UserBookListController extends Controller
{
    //عرض قائمة الكتب للمستخدم
    public function index(Request $request)
    {
        $user = $request->user();

        $status = $request->query('status');
        if ($status) {
            $request->validate([
                'status' => ['required', 'string', 'in:' . implode(',', UserBookList::statuses())],
            ]);

            $items = $user->bookList()->where('status', $status)->with('book')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'items' => $items,
                ],
            ], 200);
        }

        $items = $user->bookList()->with('book')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'أرغب بقراءتها' => $items->where('status', UserBookList::STATUS_WANT_TO_READ)->values(),
                'أقرأها الآن' => $items->where('status', UserBookList::STATUS_READING)->values(),
                'أنهيتها' => $items->where('status', UserBookList::STATUS_FINISHED)->values(),
            ],
        ], 200);
    }
    //-------------------------------------------------------------------------------------------------------------------
//إضافة كتاب إلى قوائم المستخدم
    public function add(Request $request)
    {
        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'status' => ['required', 'string', 'in:' . implode(',', UserBookList::statuses())],
        ]);

        $user = $request->user();

        //نتحقق إذا الكتاب موجود في القائمة بنفس الحالة
        $exists = UserBookList::where('user_id', $user->id)
            ->where('book_id', $validated['book_id'])
            ->where('status', $validated['status'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الكتاب موجود بالفعل في نفس القائمة.',
            ], 409); // 409 Conflict
        }

        // إذا لم يكن موجود بنفس الحالة
        $entry = UserBookList::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $validated['book_id']],
            ['status' => $validated['status']]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الكتاب إلى القائمة بنجاح',
            'data' => $entry->load('book'),
        ], 200);
    }
    //------------------------------------------------------------------------------------------------------------------
//تحديث حالة كتاب في قوائم المستخدم
    public function update(Request $request, $bookId)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', UserBookList::statuses())],
        ]);

        $user = $request->user();

        //  جلب سجل القائمة أو إنشاء سجل جديد
        $entry = UserBookList::firstOrNew(
            ['user_id' => $user->id, 'book_id' => $bookId]
        );

        $oldStatus = $entry->exists ? $entry->status : null;

        //  إذا كان الكتاب جديد في القائمة نفرض له حالة ابتدائية
        if (!$entry->exists) {
            $entry->status = UserBookList::STATUS_WANT_TO_READ;
            $entry->save();
        }

        // حساب عدد الكتب المنتهية قبل التحديث
        $oldFinishedCount = UserBookList::where('user_id', $user->id)
            ->where('status', UserBookList::STATUS_FINISHED)
            ->count();

        //  تحديث حالة الكتاب
        $entry->update(['status' => $validated['status']]);

        // إذا كان الكتاب موجود مسبقاً داخل أنهيتها والآن تتغير حالته يجب إنقاص العدد من القوائم
        if (
            $oldStatus === UserBookList::STATUS_FINISHED &&
            $validated['status'] !== UserBookList::STATUS_FINISHED
        ) {

            $oldFinishedCount--;
        }

        //  حساب عدد الكتب المنتهية بعد التحديث
        $newFinishedCount = UserBookList::where('user_id', $user->id)
            ->where('status', UserBookList::STATUS_FINISHED)
            ->count();

        // حساب اللقب القديم والجديد
        $oldNickname = $user->nickname;
        $newNickname = app(UsersController::class)->getReaderTitle($newFinishedCount);

        // إذا تغيّر اللقب نحفظه نرسل إشعار
        if ($oldNickname !== $newNickname) {
            $user->nickname = $newNickname;
            $user->save();
            $user->notify(new ReaderLevelUp($newNickname));
        }
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الكتاب بنجاح',
            'data' => $entry->load('book'),
        ], 200);
    }
    //-------------------------------------------------------------------------------------------------------------------
//حذف كتاب من قوائم المستخدم
    public function delete(Request $request, $bookId)
    {
        $user = $request->user();

        $entry = UserBookList::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->first();

        if (!$entry) {
            return response()->json([
                'success' => false,
                'message' => 'الكتاب غير موجود في القائمة',
            ], 404);
        }

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الكتاب من القائمة بنجاح',
        ], 200);
    }
    //----------------------------------------------------------------------------------------------------
}