<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onUpdate('cascade')->onDelete('cascade');

            $table->uuid('recurring_order_id');
            $table->foreign('recurring_order_id')->references('id')->on('recurring_orders')->onUpdate('cascade')->onDelete('cascade');


            $table->string('month_salary');
            $table->string('year');
            $table->boolean('status')->default(false);
            $table->integer('total_amount_to_paid')->nullable();
            $table->integer('employee_salary_amount')->nullable();
            $table->integer('cnss_customer_amount')->nullable();
            $table->integer('cnss_employee_amount')->nullable();
            $table->integer('vps_amount')->nullable();
            $table->integer('its_amount')->nullable();
            $table->integer('assurance_amount')->nullable();
            $table->boolean('employee_received_his_salary')->default(false);
            $table->boolean('discount_applied')->default(false);
            $table->integer('discount_rate')->nullable();
            $table->boolean('next_link')->default(false);
            $table->integer('ylomi_direct_fees')->nullable();
            $table->date('salary_paid_date')->nullable();
            $table->date('date_employee_received_salary')->nullable();
            $table->boolean('auto_send')->default(true);
            $table->boolean('employee_received_salary_advance')->default(false);
            $table->integer('salary_advance_amount')->nullable();
            $table->boolean('latest')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
