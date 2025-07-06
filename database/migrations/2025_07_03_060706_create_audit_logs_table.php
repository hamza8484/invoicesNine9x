<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable(); // من قام بالعملية
            $table->string('action');                         // نوع العملية: create, update, delete, login, logout, etc.
            $table->string('auditable_type');                 // اسم الجدول أو الكلاس الذي تأثر (Polymorphic relationship)
            $table->unsignedBigInteger('auditable_id');       // معرف العنصر الذي تأثر

            // تم التغيير من 'json' إلى 'longText' لحل مشكلة التوافق مع قاعدة البيانات
            $table->longText('old_values')->nullable();           // القيم السابقة (ستخزن كـ JSON String)
            $table->longText('new_values')->nullable();           // القيم الجديدة (ستخزن كـ JSON String)

            $table->string('ip_address')->nullable();         // عنوان IP للمستخدم
            $table->text('user_agent')->nullable();           // معلومات المتصفح/وكيل المستخدم

            $table->timestamps();

            // العلاقات
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // إضافة فهارس لتحسين أداء البحث
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
