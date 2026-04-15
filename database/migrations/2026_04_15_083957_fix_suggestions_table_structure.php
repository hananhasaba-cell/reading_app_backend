<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('suggestions', function (Blueprint $table) {

      
            $table->unsignedBigInteger('book_id')->nullable()->change();

            $table->string('description')->nullable()->change();

             $table->dropColumn('pdf_path');

        });
    }

    public function down()
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->unsignedBigInteger('book_id')->nullable(false)->change();
            $table->string('description')->nullable(false)->change();
            $table->string('pdf_path')->nullable();

        });
    }
};