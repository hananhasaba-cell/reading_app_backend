<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Gener;
use App\Models\ReadProgress;
use \App\Models\Payment;

class BookController extends Controller
{
    //عرض جميع الكتب
    public function index()
    {
        // تحميل التقييمات + التصنيفات
        $books = Book::with(['ratings', 'geners'])->get()->map(function (Book $book) {

            $book->average_rating = $book->ratings->avg('rating') ?? 0;

            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'pages' => $book->PageNumber,

                'cover_img' => $book->cover_img
                    ? asset('books/images/' . $book->cover_img)
                    : null,

                'pdf_path' => $book->pdf_path
                    ? asset('books/pdfs/' . $book->pdf_path)
                    : null,

                // نوع الوصول للكتاب (مجاني، مدفوع، تجريبي، مشروط بعدد الكتب المنهية)
                'access_type' => $book->access_type,
                // لو كان تجريبي هنا يتم تحديد عدد الكتب المسموح بفتحها
                'trial_pages' => $book->trial_pages,
                // لو كان مشروط بعدد الكتب المنهية هنا يتم تحديد عدد الكتب التي يجب أن يقرأها المستخدم قبل فتح هذا الكتاب
                'required_books_read' => $book->required_books_read,
                // سعر الكتاب
                'price' => $book->price,
                    

                // متوسط التقييم
                'average_rating' => $book->average_rating,

                // التصنيفات
                'geners' => $book->geners->map(function ($gener) {
                    return [
                        'id' => $gener->id,
                        'name' => $gener->name,
                    ];
                }),

                // التقييمات
                'ratings' => $book->ratings->map(function ($rating) {
                    return [
                        'user_id' => $rating->user_id,
                        'rating' => $rating->rating,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => "تم جلب الكتب بنجاح",
            'data' => $books
        ], 200);
    }
    //----------------------------------------------------------------------------------
//عرض تفاصيل الكتاب
    public function show(Request $request, int $id)
{
    $lockMessage = null;
    $book = Book::with(['ratings', 'geners'])->find($id);

    if (!$book) {
        return response()->json([
            'success' => false,
            'message' => "الكتاب غير موجود"
        ], 404);
    }

    // جلب المستخدم والتقدم في القراءة
    $user = $request->user();
    $progress = null;

    if ($user) {
        $progress = ReadProgress::where('user_id', $user->id)
                                ->where('book_id', $book->id)
                                ->first();
    }

    // هل المستخدم دفع ثمن الكتاب؟
    $hasPaid = false;

    if ($user) {
        $hasPaid = Payment::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'succeeded')
            ->exists();
    }

    //  منطق قفل الـ PDF
    $pdfPath = null;

    // مجاني مفتوح دائماً
    if ($book->access_type === 'free') {
        $pdfPath = $book->pdf_path
            ? asset('books/pdfs/' . $book->pdf_path)
            : null;
    }

    // مدفوع مفتوح فقط إذا دفع المستخدم
    if ($book->access_type === 'paid') {

        if ($hasPaid) {
            // المستخدم دفع افتح الكتاب
            $pdfPath = $book->pdf_path
                ? asset('books/pdfs/' . $book->pdf_path)
                : null;
        } else {
            // لم يدفع أغلق الكتاب
            $pdfPath = null;
            $lockMessage = "هذا الكتاب مدفوع. يرجى إتمام عملية الدفع لفتحه.";
        }
    }

    // تجريبي مفتوح فقط إذا لم يتجاوز الحد
  if ($book->access_type === 'trial') {

    // إذا دفع المستخدم  افتح الكتاب دائماً
    if ($hasPaid) {
        $pdfPath = $book->pdf_path
            ? asset('books/pdfs/' . $book->pdf_path)
            : null;
    } else {
        // لم يدفع  نطبق الحد التجريبي
        $pagesRead = $progress->pages_read ?? 0;

        if ($pagesRead < $book->trial_pages) {
            $pdfPath = asset('books/pdfs/' . $book->pdf_path);
        } else {
            $pdfPath = null;
            $lockMessage = "لقد وصلت إلى الحد التجريبي. قم بشراء الكتاب لمتابعة القراءة.";
        }
    }
}
    // مشروط مفتوح فقط إذا أنهى المستخدم العدد المطلوب
    if ($book->access_type === 'conditional') {
        if ($user) {
            $finishedBooks = $user->finishedReading()->count();

            if ($finishedBooks >= $book->required_books_read) {
                $pdfPath = asset('books/pdfs/' . $book->pdf_path);
            } else {
                $pdfPath = null;
                $lockMessage = "يجب إنهاء {$book->required_books_read} كتاباً قبل فتح هذا الكتاب.";
            }
        }
    }

    // حساب متوسط التقييم
    $averageRating = $book->ratings->avg('rating') ?? 0;

    // جلب الكتب المشابهة حسب التصنيفات
    $genreIds = $book->geners->pluck('id');

    $idColumn = 'id';
    $genreTableColumn = 'geners.id';

    $similarBooks = Book::whereHas('geners', function ($q) use ($genreIds, $genreTableColumn) {
        $q->whereIn($genreTableColumn, $genreIds);
    })
        ->where($idColumn, '!=', $book->id)
        ->with('geners')
        ->take(10)
        ->get()
        ->map(function (Book $similar) {
            return [
                'id' => $similar->id,
                'title' => $similar->title,
                'author' => $similar->author,
                'cover_img' => $similar->cover_img
                    ? asset('books/images/' . $similar->cover_img)
                    : null,
            ];
        });

    //  return النهائي
    return response()->json([
        'success' => true,
        'message' => "تم جلب تفاصيل الكتاب بنجاح",
        'data' => [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'pages' => $book->PageNumber,
            'description' => $book->description,

            // رابط الصورة
            'cover_img' => $book->cover_img
                ? asset('books/images/' . $book->cover_img)
                : null,

            //  رابط PDF بعد القفل
            'pdf_path' => $pdfPath,
            'lock_message' => $lockMessage,

            // نوع الوصول
            'access_type' => $book->access_type,
            'trial_pages' => $book->trial_pages,
            'required_books_read' => $book->required_books_read,
            'price' => $book->price,

            // متوسط التقييم
            'average_rating' => $averageRating,

            // التصنيفات
            'geners' => $book->geners->map(function ($gener) {
                return [
                    'id' => $gener->id,
                    'name' => $gener->name,
                ];
            }),

            // التقييمات
            'ratings' => $book->ratings->map(function ($rating) {
                return [
                    'user_id' => $rating->user_id,
                    'rating' => $rating->rating,
                ];
            }),

            // الكتب المشابهة
            'similar_books' => $similarBooks,
        ]
    ], 200);
}
    //----------------------------------------------------------------------------------    
//  البحث عن كتاب حسب الاسم، الكاتب، والتصنيف، والسعر
public function search(Request $request)
{
    $query = Book::query();

    $title = (string) $request->input('title');
    $author = (string) $request->input('author');
    $gener = (string) $request->input('gener');
    $accessType = (string) $request->input('access_type');

    //   التحويل من العربية إلى الإنجليزية
    $accessTypeArabicMap = [
        'مجاني' => 'free',
        'مدفوع' => 'paid',
        'تجريبي' => 'trial',
        'مشروط' => 'conditional',
    ];

    //  إذا كانت القيمة عربية نحولها
    if (isset($accessTypeArabicMap[$accessType])) {
        $accessType = $accessTypeArabicMap[$accessType];
    }

    //  البحث بالعنوان
    if ($title !== '') {
        $query->where('title', 'like', '%' . $title . '%');
    }

    //  البحث بالمؤلف
    if ($author !== '') {
        $query->where('author', 'like', '%' . $author . '%');
    }

    //  البحث بالتصنيف
    if ($gener !== '') {
        $query->whereHas('geners', function ($q) use ($gener) {
            $q->where('name', 'like', '%' . $gener . '%');
        });
    }

    //  البحث حسب نوع الوصول (مجاني، مدفوع، تجريبي، مشروط)
    if ($accessType !== '') {
        $query->where('access_type', $accessType);
    }

    $books = $query->with('geners')->get();

    return response()->json([
        'success' => true,
        'message' => "تم جلب نتائج البحث بنجاح",
        'data' => $books
    ], 200);
}

    //---------------------------------------------------------------------------------------------------
//إضافة كتاب جديد فقط من عند المدير
   public function add(Request $request)
{
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => "غير مصرح لك بإضافة كتاب جديد"
        ], 403);
    }

    $validatedData = $request->validate([
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'PageNumber' => 'required|integer',
        'gener' => 'required|array',
        'gener.*' => 'string|max:255',
        'description' => 'nullable|string',
        'cover_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'pdf_path' => 'nullable|file|mimes:pdf|max:40000',
        //  الحقول الجديدة
        'access_type' => 'required|string|in:free,paid,trial,conditional,مجاني,مدفوع,تجريبي,مشروط',
        'price' => 'nullable|numeric|min:0',
        'trial_pages' => 'nullable|integer|min:1',
        'required_books_read' => 'nullable|integer|min:1',
    ]);

    //  تحويل العربية إلى الإنجليزية
    $accessTypeMap = [
        'مجاني' => 'free',
        'مدفوع' => 'paid',
        'تجريبي' => 'trial',
        'مشروط' => 'conditional',
    ];

    $accessType = $validatedData['access_type'];
    if (isset($accessTypeMap[$accessType])) {
        $accessType = $accessTypeMap[$accessType];
    }

    //  التحقق حسب نوع الكتاب
    if ($accessType === 'paid' && empty($validatedData['price'])) {
        return response()->json([
            'success' => false,
            'message' => "يجب تحديد السعر للكتاب المدفوع"
        ], 422);
    }

    if ($accessType === 'trial' && empty($validatedData['trial_pages'])) {
        return response()->json([
            'success' => false,
            'message' => "يجب تحديد عدد الصفحات المسموح بها للكتاب التجريبي"
        ], 422);
    }

    if ($accessType === 'conditional' && empty($validatedData['required_books_read'])) {
        return response()->json([
            'success' => false,
            'message' => "يجب تحديد عدد الكتب المطلوبة لفتح هذا الكتاب"
        ], 422);
    }

    //  تجهيز التصنيفات
    $generIds = [];
    foreach ($validatedData['gener'] as $generName) {
        $gener = Gener::firstOrCreate(['name' => $generName]);
        $generIds[] = $gener->id;
    }

    //  رفع الصورة
    $coverPath = null;
    if ($request->hasFile('cover_img')) {
        $file = $request->file('cover_img');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('books/images'), $fileName);
        $coverPath = $fileName;
    }

    //  رفع PDF
    $pdfPath = null;
    if ($request->hasFile('pdf_path')) {
        $file = $request->file('pdf_path');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('books/pdfs'), $fileName);
        $pdfPath = $fileName;
    }
// منع إضافة كتاب مكرر (نفس العنوان + نفس المؤلف)
$exists = Book::where('title', $validatedData['title'])
              ->where('author', $validatedData['author'])
              ->exists();

if ($exists) {
    return response()->json([
        'success' => false,
        'message' => "هذا الكتاب موجود مسبقاً ولا يمكن إضافته مرة أخرى."
    ], 409);
}

    //  إنشاء الكتاب
    $book = Book::create([
        'title' => $validatedData['title'],
        'author' => $validatedData['author'],
        'PageNumber' => $validatedData['PageNumber'],
        'description' => $validatedData['description'] ?? null,
        'cover_img' => $coverPath,
        'pdf_path' => $pdfPath,

        //  الحقول الجديدة
        'access_type' => $accessType,
        'price' => $validatedData['price'] ?? null,
        'trial_pages' => $validatedData['trial_pages'] ?? null,
        'required_books_read' => $validatedData['required_books_read'] ?? null,
    ]);

    //  ربط التصنيفات
    $book->geners()->sync($generIds);
        //  إرسال إشعار لكل المستخدمين
    $users = \App\Models\User::all();
    foreach ($users as $user) {
    $user->notify(new \App\Notifications\NewBookAdded($book));
}

    return response()->json([
        'success' => true,
        'message' => "تم إضافة الكتاب بنجاح",
        'data' => $book
    ], 201);
}
    //---------------------------------------------------------------------------------------------------    
//تعديل بيانات كتاب فقط من عند المدير
public function update(Request $request, int $id)
{
    // التحقق من صلاحية المستخدم (يجب أن يكون مدير)
    if (!$request->user() instanceof Admin) {
        return response()->json([
            'success' => false,
            'message' => "غير مصرح لك بتعديل الكتاب"
        ], 403);
    }

    $book = Book::find($id);
    if (!$book) {
        return response()->json([
            'success' => false,
            'message' => "الكتاب غير موجود"
        ], 404);
    }

    // التحقق من صحة البيانات المدخلة
    $validatedData = $request->validate([
        'title' => 'sometimes|required|string|max:255',
        'author' => 'sometimes|required|string|max:255',
        'PageNumber' => 'sometimes|nullable|integer',
        'gener' => 'sometimes|required|array',
        'gener.*' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'cover_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'pdf_path' => 'nullable|file|mimes:pdf|max:40000',
        //  الحقول الجديدة
        'access_type' => 'sometimes|string|in:free,paid,trial,conditional,مجاني,مدفوع,تجريبي,مشروط',
        'price' => 'nullable|numeric|min:0',
        'trial_pages' => 'nullable|integer|min:1',
        'required_books_read' => 'nullable|integer|min:1',
    ]);

    //  تحويل العربية إلى الإنجليزية
    if (isset($validatedData['access_type'])) {
        $accessTypeMap = [
            'مجاني' => 'free',
            'مدفوع' => 'paid',
            'تجريبي' => 'trial',
            'مشروط' => 'conditional',
        ];

        $accessType = $validatedData['access_type'];
        if (isset($accessTypeMap[$accessType])) {
            $validatedData['access_type'] = $accessTypeMap[$accessType];
        }
    }

    //  التحقق حسب نوع الكتاب إذا تم تغييره
    if (isset($validatedData['access_type'])) {

        if ($validatedData['access_type'] === 'paid' && empty($validatedData['price'])) {
            return response()->json([
                'success' => false,
                'message' => "يجب تحديد السعر للكتاب المدفوع"
            ], 422);
        }

        if ($validatedData['access_type'] === 'trial' && empty($validatedData['trial_pages'])) {
            return response()->json([
                'success' => false,
                'message' => "يجب تحديد عدد الصفحات المسموح بها للكتاب التجريبي"
            ], 422);
        }

        if ($validatedData['access_type'] === 'conditional' && empty($validatedData['required_books_read'])) {
            return response()->json([
                'success' => false,
                'message' => "يجب تحديد عدد الكتب المطلوبة لفتح هذا الكتاب"
            ], 422);
        }
    }

    //  رفع الصورة
    if ($request->hasFile('cover_img')) {
        $file = $request->file('cover_img');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('books/images'), $fileName);
        $book->cover_img = $fileName;
    }

    //  رفع PDF
    if ($request->hasFile('pdf_path')) {
        $file = $request->file('pdf_path');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('books/pdfs'), $fileName);
        $book->pdf_path = $fileName;
    }
// منع تعديل الكتاب ليصبح نسخة مكررة من كتاب آخر
if (isset($validatedData['title']) || isset($validatedData['author'])) {

    $newTitle = $validatedData['title'] ?? $book->title;
    $newAuthor = $validatedData['author'] ?? $book->author;

    $duplicate = Book::where('title', $newTitle)
        ->where('author', $newAuthor)
        ->where('id', '!=', $book->id) // استثناء الكتاب الحالي
        ->exists();

    if ($duplicate) {
        return response()->json([
            'success' => false,
            'message' => "لا يمكن تعديل هذا الكتاب ليصبح مطابقاً لكتاب آخر موجود."
        ], 409);
    }
}
    //  تحديث بيانات الكتاب
    $book->update($validatedData);

    //  تحديث التصنيفات إذا تم إرسالها
    if ($request->has('gener')) {
        $generIds = [];
        foreach ($validatedData['gener'] as $generName) {
            $gener = Gener::firstOrCreate(['name' => $generName]);
            $generIds[] = $gener->id;
        }
        $book->geners()->sync($generIds);
    }

    return response()->json([
        'success' => true,
        'message' => "تم تحديث بيانات الكتاب بنجاح",
        'data' => $book
    ], 200);
}
    //---------------------------------------------------------------------------------------------------
//حذف كتاب فقط من عند المدير
    public function delete(Request $request,int $id)
    {
        // التحقق من صلاحية المستخدم (يجب أن يكون مديرًا)
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => "غير مصرح لك بحذف الكتاب"
            ], 403);
        }

        $book = Book::find($id);
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => "الكتاب غير موجود"
            ], 404);
        }

        $book->delete();

        return response()->json([
            'success' => true,
            'message' => "تم حذف الكتاب بنجاح",
            'data' => [
                'id' => $id
            ]
        ]);
    }
    //---------------------------------------------------------------------------------------------------

}

