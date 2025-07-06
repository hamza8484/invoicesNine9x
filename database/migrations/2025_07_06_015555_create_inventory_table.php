<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('material_id'); // المادة
            $table->unsignedBigInteger('warehouse_id'); // المستودع الذي توجد فيه المادة
            $table->integer('quantity'); // الكمية الموجودة من هذه المادة في هذا المستودع
            $table->decimal('cost_price', 15, 2)->nullable(); // سعر التكلفة لهذه الكمية (إذا لزم الأمر)

            $table->timestamps();

            // المفاتيح الأجنبية
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            // القيد الفريد: لضمان أن كل مادة فريدة في كل مستودع
            $table->unique(['material_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory');
    }
}
