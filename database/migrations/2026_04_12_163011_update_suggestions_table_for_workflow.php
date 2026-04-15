<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            if (!Schema::hasColumn('suggestions', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('set null');
            }
            if (!Schema::hasColumn('suggestions', 'related_book_id')) {
                $table->foreignId('related_book_id')->nullable()->constrained('books')->onDelete('set null');
            }
            if (!Schema::hasColumn('suggestions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            if (Schema::hasColumn('suggestions', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
            if (Schema::hasColumn('suggestions', 'related_book_id')) {
                $table->dropForeign(['related_book_id']);
                $table->dropColumn('related_book_id');
            }
            if (Schema::hasColumn('suggestions', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};
