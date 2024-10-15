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
        Schema::create('quiz', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('recurring_service_id');
            $table->foreign('recurring_service_id')->references('id')->on('recurring_services')->onUpdate('cascade')->onDelete('cascade');
            $table->uuid('add_by');
            $table->foreign('add_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->text('question');
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
        Schema::dropIfExists('quiz');
    }
};
