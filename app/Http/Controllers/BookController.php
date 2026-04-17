<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Gener;

class BookController extends Controller
{
    //عرض جميع الكتب
    public function index()
    {
        $books = Book::all();
        return response()->json([
            'success' => true,
            'message' => "تم جلب الكتب بنجاح",
            'data' => $books
        ], 200);
    }
    //----------------------------------------------------------------------------------
//عرض تفاصيل الكتاب
    public function show($id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => "الكتاب غير موجود"
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => "تم جلب تفاصيل الكتاب بنجاح",
            'data' => $book
        ], 200);
    }
    //----------------------------------------------------------------------------------    
// البحث عن كتاب حسب الاسم، الكاتب، والتصنيف
    public function search(Request $request)
    {
        $query = Book::query();

        $title = $request->input('title');
        $author = $request->input('author');
        $gener = $request->input('gener');

        if (!empty($title)) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        if (!empty($author)) {
            $query->where('author', 'like', '%' . $author . '%');
        }

        if (!empty($gener)) {
            $query->whereHas('geners', function ($q) use ($gener) {
                $q->where('name', 'like', '%' . $gener . '%');
            });
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
        ]);

        $existing = Book::where('title', $validatedData['title'])
            ->where('author', $validatedData['author'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "هذا الكتاب مضاف مسبقًا",
                'data' => $existing
            ], 409);
        }

        // تجهيز IDs للأنواع
        $generIds = [];
        foreach ($validatedData['gener'] as $generName) {
            $gener = Gener::firstOrCreate(['name' => $generName]);
            $generIds[] = $gener->id;
        }

        // رفع الصورة
        $coverPath = null;
        if ($request->hasFile('cover_img')) {
            $coverPath = $request->file('cover_img')->store('books/images', 'public');
        }

        // رفع PDF
        $pdfPath = $request->file('pdf_path')->store('books/pdfs', 'public');


        // إنشاء الكتاب
        $book = Book::create([
            'title' => $validatedData['title'],
            'author' => $validatedData['author'],
            'PageNumber' => $validatedData['PageNumber'],
            'description' => $validatedData['description'] ?? null,
            'cover_img' => $coverPath,
            'pdf_path' => $pdfPath,
        ]);

        // ربط التصنيفات
        $book->geners()->sync($generIds);

        // تحميل العلاقة للعرض
        $book->load('geners');

        return response()->json([
            'success' => true,
            'message' => "تم إضافة الكتاب بنجاح",
            'data' => $book
        ], 201);
    }

    //---------------------------------------------------------------------------------------------------    
//تعديل بيانات كتاب فقط من عند المدير
    public function update(Request $request, $id)
    {
        // التحقق من صلاحية المستخدم (يجب أن يكون مديرًا)
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
            'PageNumber' => 'sometimes|required|integer',
            'gener' => 'sometimes|required|array',
            'gener.*' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cover_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'pdf_path' => 'nullable|file|mimes:pdf|max:40000',
        ]);
        // رفع الصورة
        $coverPath = null;
        if ($request->hasFile('cover_img')) {
            $coverPath = $request->file('cover_img')->store('books/images', 'public');
        }

        // رفع pdf
        $pdfPath = $request->file('pdf_path')->store('books/pdfs', 'public');

        // تحديث بيانات الكتاب
        $book->update(array_merge($validatedData, [
            'cover_img' => $coverPath,
            'pdf_path' => $pdfPath,
        ]));

        return response()->json([
            'success' => true,
            'message' => "تم تحديث بيانات الكتاب بنجاح",
            'data' => $book
        ], 200);
    }
    //---------------------------------------------------------------------------------------------------
//حذف كتاب فقط من عند المدير
    public function delete(Request $request, $id)
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