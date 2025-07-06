<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->string('name');                             // اسم المادة
            $table->string('code')->nullable()->unique();       // رمز المادة إن وجد
            $table->unsignedBigInteger('unit_id')->nullable();  // الوحدة
            $table->unsignedBigInteger('material_group_id')->nullable(); // المجموعة
            $table->unsignedBigInteger('tax_id')->nullable();   // الضريبة

            $table->decimal('purchase_price', 15, 2)->default(0); // سعر الشراء
            $table->decimal('sale_price', 15, 2)->default(0);     // سعر البيع
            $table->integer('stock_quantity')->default(0);        // الكمية بالمخزون

            $table->string('image')->nullable();                  // مسار الصورة
            $table->text('description')->nullable();              // وصف المادة

            $table->timestamps();

            // المفاتيح الأجنبية
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
            $table->foreign('material_group_id')->references('id')->on('material_groups')->onDelete('set null');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
}
