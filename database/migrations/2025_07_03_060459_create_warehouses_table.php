<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousesTable extends Migration
{
    public function up()
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            $table->string('name');            // اسم المستودع
            $table->string('code')->nullable()->unique(); // رمز داخلي اختياري
            $table->string('location')->nullable();        // الموقع أو العنوان
            $table->text('description')->nullable();       // ملاحظات إضافية
            $table->boolean('is_active')->default(true);   // هل المستودع مفعل؟

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouses');
    }
}
