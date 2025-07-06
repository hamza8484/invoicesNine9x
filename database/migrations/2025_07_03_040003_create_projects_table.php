<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('current_spend', 15, 2)->default(0);
            $table->enum('status', ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled', 'archived'])->default('planning');
            $table->decimal('total_income', 15, 2)->default(0.00);

            // المفتاح الأجنبي للعميل (يشير إلى جدول clients)
            $table->foreignId('client_id') // Laravel's preferred way for unsignedBigInteger
                  ->nullable() // **اجعلها قابلة للقيم الفارغة**
                  ->constrained('clients') // **يشير إلى جدول 'clients'**
                  ->onDelete('set null'); // **عند حذف عميل، يصبح client_id للمشروع NULL**

            // المفتاح الأجنبي للمدير (يشير إلى جدول المستخدمين)
            $table->foreignId('manager_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->string('location')->nullable();
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}