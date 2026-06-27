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

    //  جلب الكتاب
    $book = Book::find($validated['book_id']);

    //  هل المستخدم دفع؟
    $hasPaid = \App\Models\Payment::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->where('status', 'succeeded')
        ->exists();

    //  منع إضافة الكتاب إلى "أقرؤه الآن" أو "أنهيتها" إذا لم يدفع
    if (!$hasPaid && in_array($validated['status'], [
        UserBookList::STATUS_READING,
        UserBookList::STATUS_FINISHED
    ])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك إضافة هذا الكتاب إلى هذه القائمة قبل شرائه.',
        ], 403);
    }
//  منع التجريبي إذا تجاوز الحد ولم يدفع
if ($book->access_type === 'trial' && !$hasPaid) {
    $progress = \App\Models\ReadProgress::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->first();

    $pagesRead = $progress->pages_read ?? 0;

    if ($pagesRead >= $book->trial_pages && in_array($validated['status'], [
        UserBookList::STATUS_READING,
        UserBookList::STATUS_FINISHED
    ])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك إضافة هذا الكتاب إلى هذه القائمة بعد انتهاء الحد التجريبي إلا بعد شرائه.',
        ], 403);
    }
}

//  منع المشروط إذا لم يستوفِ عدد الكتب المطلوبة
if ($book->access_type === 'conditional') {
    $finishedBooks = $user->finishedReading()->count();

    if ($finishedBooks < $book->required_books_read && in_array($validated['status'], [
        UserBookList::STATUS_READING,
        UserBookList::STATUS_FINISHED
    ])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك إضافة هذا الكتاب إلى هذه القائمة قبل استيفاء الشروط.',
        ], 403);
    }
}

    // جلب السجل إن وجد
    $userColumn = 'user_id';
    $bookColumn = 'book_id';
    $statusColumn = 'status';

    $entry = UserBookList::where($userColumn, $user->id)
        ->where($bookColumn, $validated['book_id'])
        ->first();

    $oldStatus = $entry ? $entry->status : null;

    // إذا كان موجود بنفس الحالة  منع إضافته مجدداً
    if ($entry && $entry->status === $validated['status']) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الكتاب موجود بالفعل في نفس القائمة.',
        ], 409);
    }

    // إذا لم يكن موجود  ينشأه
    if (!$entry) {
        $entry = UserBookList::create([
            'user_id' => $user->id,
            'book_id' => $validated['book_id'],
            'status' => $validated['status'],
        ]);
    } else {
        // إذا كان موجود بحالة أخرى حدّثه
        $entry->update(['status' => $validated['status']]);
    }

    // حساب عدد الكتب المنتهية قبل وبعد
    $oldFinishedCount = UserBookList::where($userColumn, $user->id)
        ->where($statusColumn, UserBookList::STATUS_FINISHED)
        ->count();

    if (
        $validated['status'] === UserBookList::STATUS_FINISHED &&
        $oldStatus !== UserBookList::STATUS_FINISHED
    ) {
        $oldFinishedCount++;
    }
//  إشعار: قراءة كتاب مشترك بين المتابعين
if ($validated['status'] === UserBookList::STATUS_FINISHED) {

    // جلب المتابعين
    $followers = $user->followers;

    foreach ($followers as $follower) {

        // هل المتابع أنهى نفس الكتاب؟
        $hasFinished = $follower->bookList()
            ->where('book_id', $book->id)
            ->where('status', UserBookList::STATUS_FINISHED)
            ->exists();

        if ($hasFinished) {
            $follower->notify(new \App\Notifications\SharedBookFinished($user, $book));
        }
    }
}
    $newFinishedCount = $oldFinishedCount;

    // حساب اللقب
    $oldNickname = $user->nickname;
    $newNickname = app(UsersController::class)->getReaderTitle($newFinishedCount);

if ($oldNickname !== $newNickname) {

    // تحديث اللقب
    $user->nickname = $newNickname;
    $user->save();
// إشعار لصاحب الحساب
$user->notify(new ReaderLevelUp($newNickname));
// إشعار للمتابعين1
$followers = $user->followers;

foreach ($followers as $follower) {
    $follower->notify(new ReaderLevelUp($newNickname, $user));

    }
}
    return response()->json([
        'success' => true,
        'message' => 'تم إضافة الكتاب إلى القائمة بنجاح',
        'data' => $entry->load('book'),
    ], 200);
}
    //------------------------------------------------------------------------------------------------------------------
//تحديث حالة كتاب في قوائم المستخدم
    public function update(Request $request, int $bookId)
{
    $userColumn = 'user_id';
    $bookColumn = 'book_id';
    $statusColumn = 'status';

    $validated = $request->validate([
        'status' => ['required', 'string', 'in:' . implode(',', UserBookList::statuses())],
    ]);

    $user = $request->user();

    //  جلب الكتاب
    $book = Book::find($bookId);

    //  هل المستخدم دفع؟
    $hasPaid = \App\Models\Payment::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->where('status', 'succeeded')
        ->exists();

    //  منع التغيير إلى "أقرؤه الآن" أو "أنهيتها" إذا لم يدفع
    if (!$hasPaid && in_array($validated['status'], [
        UserBookList::STATUS_READING,
        UserBookList::STATUS_FINISHED
    ])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك تغيير حالة هذا الكتاب قبل شرائه.',
        ], 403);
    }
   //  منع التجريبي إذا تجاوز الحد ولم يدفع
if ($book->access_type === 'trial' && !$hasPaid) {
    $progress = \App\Models\ReadProgress::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->first();

    $pagesRead = $progress->pages_read ?? 0;

    if ($pagesRead >= $book->trial_pages && in_array($validated['status'], [
        UserBookList::STATUS_READING,
        UserBookList::STATUS_FINISHED
    ])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك تغيير حالة هذا الكتاب بعد انتهاء الحد التجريبي إلا بعد شرائه.',
        ], 403);
    }
}
//  منع المشروط إذا لم يستوفِ عدد الكتب المطلوبة
if ($book->access_type === 'conditional') {
    $finishedBooks = $user->finishedReading()->count();

    if ($finishedBooks < $book->required_books_read && in_array($validated['status'], [
        UserBookList::STATUS_READING,
        UserBookList::STATUS_FINISHED
    ])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك تغيير حالة هذا الكتاب قبل استيفاء الشروط.',
        ], 403);
    }
}
    // جلب سجل القائمة أو إنشاء سجل جديد
    $entry = UserBookList::firstOrNew(
        [$userColumn => $user->id, $bookColumn => $bookId]
    );
    // حفظ الحالة القديمة قبل التحديث
    $oldStatus = $entry->exists ? $entry->status : null;
    // إذا لم يكن موجودًا، يتم تعيين الحالة الافتراضية إلى "أرغب بقراءتها" قبل التحديث   
    if (!$entry->exists) {
        $entry->status = UserBookList::STATUS_WANT_TO_READ;
        $entry->save();
    }
     // حساب عدد الكتب المنتهية قبل وبعد التحديث
    $oldFinishedCount = UserBookList::where($userColumn, $user->id)
        ->where($statusColumn, UserBookList::STATUS_FINISHED)
        ->count();
    // تحديث الحالة
    $entry->update(['status' => $validated['status']]);
    //  إشعار: قراءة كتاب مشترك بين المتابعين
if ($validated['status'] === UserBookList::STATUS_FINISHED && $oldStatus !== UserBookList::STATUS_FINISHED) {

    // جلب المتابعين
    $followers = $user->followers;

    foreach ($followers as $follower) {

        // هل المتابع أنهى نفس الكتاب؟
        $hasFinished = $follower->bookList()
            ->where('book_id', $book->id)
            ->where('status', UserBookList::STATUS_FINISHED)
            ->exists();

        if ($hasFinished) {
            $follower->notify(new \App\Notifications\SharedBookFinished($user, $book));
        }
    }
}
    // إذا كانت الحالة القديمة هي "أنهيتها" والحالة الجديدة ليست كذلك، يتم تقليل عدد الكتب المنتهية
    if (
        $oldStatus === UserBookList::STATUS_FINISHED &&
        $validated['status'] !== UserBookList::STATUS_FINISHED
    ) {
        $oldFinishedCount--;
    }
    // إذا كانت الحالة القديمة ليست "أنهيتها" والحالة الجديدة هي كذلك، يتم زيادة عدد الكتب المنتهية
    $newFinishedCount = UserBookList::where($userColumn, $user->id)
        ->where($statusColumn, UserBookList::STATUS_FINISHED)
        ->count();
    // حساب اللقب
    $oldNickname = $user->nickname;
    $newNickname = app(UsersController::class)->getReaderTitle($newFinishedCount);

    if ($oldNickname !== $newNickname) {
        $user->nickname = $newNickname;
        $user->save();
        // إشعار لصاحب الحساب
$user->notify(new ReaderLevelUp($newNickname));

// إشعار للمتابعين
$followers = $user->followers;

foreach ($followers as $follower) {
    $follower->notify(new ReaderLevelUp($newNickname, $user));
}

    }

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث حالة الكتاب بنجاح',
        'data' => $entry->load('book'),
    ], 200);
}
    //-------------------------------------------------------------------------------------------------------------------
//حذف كتاب من قوائم المستخدم
    public function delete(Request $request,int $bookId)
    {
        $userColumn = 'user_id';
        $bookColumn = 'book_id';

        $user = $request->user();

        $entry = UserBookList::where($userColumn, $user->id)
            ->where($bookColumn, $bookId)
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