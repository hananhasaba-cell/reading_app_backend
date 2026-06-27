<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('type');
                $table->json('data')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
