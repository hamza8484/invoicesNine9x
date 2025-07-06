<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable(); // المستخدم المستهدف بالإشعار
            $table->string('title');        // عنوان الإشعار
            $table->text('message');        // محتوى الإشعار
            $table->string('type')->nullable(); // نوع الإشعار (نظام، مهمة، فاتورة، الخ)
            $table->boolean('is_read')->default(false); // هل تم قراءة الإشعار

            $table->timestamps();

            // العلاقات
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
