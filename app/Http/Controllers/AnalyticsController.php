<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\UserBookList;
use App\Models\Book;
use App\Models\Gener;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    // يعرض الربح من كل كتاب تم بيعه، مع عدد المبيعات لكل كتاب
public function booksEarnings(Request $request)
{
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }

    $books = Payment::where('status', 'succeeded')
        ->selectRaw('book_id, COUNT(*) as sales_count, SUM(amount) as total_earnings')
        ->groupBy('book_id')
        ->with('book:id,title,author')
        ->orderByDesc('total_earnings') //  الترتيب من الأعلى للأقل
        ->get()
        ->map(function ($row) {
            return [
                'book_id' => $row->book_id,
                'title' => $row->book->title ?? null,
                'author' => $row->book->author ?? null,
                'sales_count' => $row->sales_count,
                'total_earnings' => (float) $row->total_earnings,
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $books
    ]);
}
//----------------------------------------------------------------------------------------------------
// المؤلفين الذي تباع كتبهم بكثرة، مع عدد المبيعات لكل مؤلف وإجمالي الأرباح
public function authorsEarnings(Request $request)
{
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }
// استعلام للحصول على المؤلفين الأكثر مبيعًا
    $authors = Payment::where('payments.status', 'succeeded')
        ->join('books', 'payments.book_id', '=', 'books.id')
        ->selectRaw('books.author, COUNT(*) as sales_count, SUM(payments.amount) as total_earnings')
        ->groupBy('books.author')
        ->orderByDesc('total_earnings') //  الترتيب من الأعلى للأقل
        ->get();

    return response()->json([
        'success' => true,
        'data' => $authors
    ]);
}
//---------------------------------------------------------------------------------------------------
// التصنيفات الأكثر مبيعًا، مع عدد المبيعات لكل تصنيف وإجمالي الأرباح
public function categoriesEarnings(Request $request)
{
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }
// استعلام للحصول على التصنيفات الأكثر مبيعًا
    $categories = Payment::where('payments.status', 'succeeded')
        ->join('books', 'payments.book_id', '=', 'books.id')
        ->join('book__geners', 'books.id', '=', 'book__geners.book_id')
        ->join('geners', 'book__geners.gener_id', '=', 'geners.id')
        ->selectRaw('geners.id, geners.name, COUNT(*) as sales_count, SUM(payments.amount) as total_earnings')
        ->groupBy('geners.id', 'geners.name')
        ->orderByDesc('total_earnings') //  الترتيب من الأعلى للأقل
        ->get();

    return response()->json([
        'success' => true,
        'data' => $categories
    ]);
}
//---------------------------------------------------------------------------------------------------
    // تقارير الأرباح الشهرية لعام معين
   public function monthlyEarnings(Request $request)
{
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }
// الحصول على السنة من الطلب، أو استخدام السنة الحالية إذا لم يتم تحديدها
    $year = $request->get('year', date('Y'));

    $months = Payment::where('status', 'succeeded')
        ->whereYear('created_at', $year)
        ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');
// إنشاء مصفوفة تحتوي على جميع الأشهر مع تعيين القيمة إلى 0 إذا لم يكن هناك أرباح للشهر
    $result = [];
    for ($m = 1; $m <= 12; $m++) {
        $result[$m] = isset($months[$m]) ? (float) $months[$m] : 0.0;
    }

    return response()->json([
        'success' => true,
        'year' => $year,
        'monthly_totals' => $result
    ]);
}
//--------------------------------------------------------------------------------------------------
// تقارير الأرباح السنوية لتبقى كأرشيف
public function yearlyEarnings(Request $request)
{
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
        ], 403);
    }

    $years = Payment::where('status', 'succeeded')
        ->selectRaw('YEAR(created_at) as year, COUNT(*) as sales_count, SUM(amount) as total_earnings')
        ->groupBy('year')
        ->orderByDesc('year')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $years
    ]);
}
//---------------------------------------------------------------------------------------------------
    // أكثر الكتب/المؤلفين/التصنيفات قراءةً
    public function mostRead(Request $request)
    {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'
            ], 403);
        }

        $Status = 'status';
        $USERList = 'user_book_lists';
        $BOOKGENERS = 'book__geners';
        $BOOKCOLUMN = 'books';
        $top = (int) $request->get('top', 10);

        // أكثر الكتب قراءةً
        $books = UserBookList::where($Status, UserBookList::STATUS_FINISHED)
            ->selectRaw('book_id, COUNT(*) as cnt')
            ->groupBy('book_id')
            ->orderByDesc('cnt')
            ->limit($top)
            ->get()
            ->map(function ($row) {
                $book = Book::find($row->book_id);
              return [
           'book_id' => $row->book_id,
           'title' => $book?->title,
           'author' => $book?->author,
           'access_type' => $book?->access_type,
           'price' => $book?->price,
           'trial_pages' => $book?->trial_pages,
           'required_books_read' => $book?->required_books_read,
           'count' => $row->cnt
];

            });

        // أكثر المؤلفين قراءةً
        $authors = DB::table($USERList)
            ->join('books', $USERList . '.book_id', '=', 'books.id')
            ->where($USERList . '.' . $Status, UserBookList::STATUS_FINISHED)
            ->selectRaw('books.author, COUNT(*) as cnt')
            ->groupBy('books.author')
            ->orderByDesc('cnt')
            ->limit($top)
            ->get();

        // أكثر التصنيفات قراءةً
        $categories = DB::table($USERList)
            ->join('books', $USERList . '.book_id', '=', 'books.id')
            ->join('book__geners', $BOOKCOLUMN . '.id', '=', 'book__geners.book_id')
            ->join('geners', $BOOKGENERS . '.gener_id', '=', 'geners.id')
            ->where($USERList . '.' . $Status, UserBookList::STATUS_FINISHED)
            ->selectRaw('geners.id, geners.name, COUNT(*) as cnt')
            ->groupBy('geners.id', 'geners.name')
            ->orderByDesc('cnt')
            ->limit($top)
            ->get();

        return response()->json([
            'success' => true,
            'books' => $books,
            'authors' => $authors,
            'categories' => $categories
        ]);
    }
}
