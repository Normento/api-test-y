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
        Schema::create('recurring_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->uuid('recurring_service_id');
            $table->foreign('recurring_service_id')->references('id')
                ->on('recurring_services')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('type'); // 1 recurring_recruitment, 2 recurring_employee_management  3 punctual_recruitment

            $table->integer('number_of_employees');

            $table->integer('status')->default(0);

            $table->boolean('is_paid')->default(false);

            $table->boolean('payment_is_exonerated')->default(false);

            $table->uuid('recommended_by')->nullable();

            $table->foreign('recommended_by')->references('id')
                ->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('intervention_frequency')->nullable();

            $table->text('description');

            $table->string('address');

            $table->boolean('created_from_dashboard')->default(false);

            $table->boolean('cnss')->nullable();;
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');


            $table->boolean("is_archived")->default(false);
            $table->text('archiving_reason')->nullable();
            $table->date('archived_date')->nullable();
            $table->uuid('archived_by')->nullable();
            $table->foreign('archived_by')
                ->references("id")->on('users')->onUpdate('cascade')
                ->onDelete('cascade');

            $table->integer('employee_salary')->nullable();
            $table->boolean('affiliated')->default(false);
            $table->integer('numbers_of_deployment')->default(0);
            $table->boolean('customer_budget')->nullable();
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
        Schema::dropIfExists('recurring_orders');
    }
};
