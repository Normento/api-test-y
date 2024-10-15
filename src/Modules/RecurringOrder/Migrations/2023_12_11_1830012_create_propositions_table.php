<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('propositions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onUpdate('cascade')->onDelete('cascade');

            $table->uuid('recurring_order_id');
            $table->foreign('recurring_order_id')->references('id')->on('recurring_orders')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('salary');
            $table->integer('status')->default(0); //-2 désistement, -1 réjeté, 0 proposition effectué, 1 proposition accepté, 2 proposition active, 3 en pause, 4 Fin de contrat

            $table->date('proposed_at')->nullable();

            $table->boolean('contract_is_approved')->default(false);

            $table->text('rejection_reason')->nullable();
            $table->date('end_date')->nullable();
            $table->date('started_date')->nullable();
            $table->text('end_reason')->nullable();
            $table->string('signature')->nullable();
            $table->string('contract')->nullable();
            $table->boolean('applied_cnss')->default(false);
            $table->dateTime('interview_asked_at')->nullable();
            $table->string('interview_location')->nullable();

            $table->uuid('proposed_by')->nullable();
            $table->foreign('proposed_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

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
        Schema::dropIfExists('propositions');
    }
};
