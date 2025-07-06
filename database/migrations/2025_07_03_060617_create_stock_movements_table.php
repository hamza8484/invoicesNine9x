<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('warehouse_id'); // المستودع المتأثر بالحركة

            // نوع الحركة:
            // 'purchase_receipt': استلام شراء (زيادة مخزون)
            // 'sale_issue': صرف بيع (نقصان مخزون)
            // 'transfer_out': تحويل خارج (نقصان من المستودع الحالي)
            // 'transfer_in': تحويل داخل (زيادة في المستودع الحالي)
            // 'adjustment_in': تسوية بالزيادة (زيادة مخزون لسبب غير شراء)
            // 'adjustment_out': تسوية بالنقصان (نقصان مخزون لسبب غير بيع/صرف)
            // 'project_issue': صرف لمشروع (نقصان مخزون)
            // 'project_return': إرجاع من مشروع (زيادة مخزون)
            $table->enum('transaction_type', [
                'purchase_receipt', 'sale_issue', 'transfer_out', 'transfer_in',
                'adjustment_in', 'adjustment_out', 'project_issue', 'project_return'
            ]);

            $table->integer('quantity'); // الكمية المتحركة (دائمًا قيمة موجبة، نوع الحركة يحدد الاتجاه)
            $table->decimal('unit_cost', 15, 2)->nullable(); // تكلفة الوحدة وقت الحركة
            $table->decimal('total_cost', 15, 2)->nullable(); // إجمالي التكلفة لهذه الحركة

            $table->timestamp('transaction_date')->useCurrent(); // تاريخ ووقت الحركة

            // علاقة بوليمورفية لربط الحركة بالكيان المصدر (فاتورة، أمر شراء، مشروع، إلخ)
            $table->string('reference_type')->nullable(); // مثال: 'App\Models\Invoice', 'App\Models\Project'
            $table->unsignedBigInteger('reference_id')->nullable(); // معرف الكيان المرتبط

            $table->text('notes')->nullable(); // ملاحظات إضافية حول الحركة
            $table->unsignedBigInteger('user_id')->nullable(); // المستخدم الذي قام بالحركة

            $table->timestamps();

            // المفاتيح الأجنبية
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // لا يوجد قيد فريد هنا لأن نفس المادة يمكن أن تتحرك عدة مرات في نفس المستودع.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
}
