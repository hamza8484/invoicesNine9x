<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_invoice_id'); // رقم الفاتورة المرتبطة
            $table->decimal('amount', 15, 2);                  // قيمة الدفعة
            $table->date('payment_date')->useCurrent();        // تاريخ الدفع
            $table->string('payment_method')->nullable();      // مثل: cash, bank transfer, cheque
            $table->string('reference')->nullable();           // مرجع التحويل أو الشيك
            $table->text('notes')->nullable();
            
            $table->unsignedBigInteger('user_id')->nullable(); // الموظف الذي أضاف الدفع
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            // العلاقات
            $table->foreign('purchase_invoice_id')
                  ->references('id')
                  ->on('purchase_invoices')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_payments');
    }
}
