<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimesheetsTable extends Migration
{
    public function up()
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');         // العامل أو الموظف
            $table->unsignedBigInteger('project_id');      // المشروع المرتبط
            $table->unsignedBigInteger('task_id')->nullable(); // المهمة (اختياري)

            $table->date('work_date');                     // تاريخ العمل
            $table->time('start_time');                    // وقت البداية
            $table->time('end_time');                      // وقت النهاية
            $table->decimal('duration', 5, 2)->default(0); // عدد الساعات = end - start

            $table->text('notes')->nullable();             // ملاحظات إضافية

            $table->timestamps();

            // علاقات
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('timesheets');
    }
}
