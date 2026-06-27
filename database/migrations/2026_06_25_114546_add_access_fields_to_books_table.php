<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // نوع الوصول للكتاب: مجاني، مدفوع، تجريبي، مشروط بعدد الكتب التي تم قرائتها
            $table->enum('access_type', ['free', 'paid', 'trial', 'conditional'])
                  ->default('free')
                  ->after('pdf_path');

            // عدد الصفحات المسموح بها في حالة الكتاب التجريبي
            $table->integer('trial_pages')
                  ->nullable()
                  ->after('access_type');

            // عدد الكتب المطلوب قراءتها لفتح هذا الكتاب (في حالة مشروط)
            $table->integer('required_books_read')
                  ->nullable()
                  ->after('trial_pages');

            // سعر الكتاب في حالة كونه مدفوع
            $table->decimal('price', 8, 2)
                  ->nullable()
                  ->after('required_books_read');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['access_type', 'trial_pages', 'required_books_read', 'price']);
        });
    }
};
