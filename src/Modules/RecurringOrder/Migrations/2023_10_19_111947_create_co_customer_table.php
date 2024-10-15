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
        Schema::create('co_customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('assign_at');
            $table->date('terminate_at')->nullable();
            $table->uuid('co_id');
            $table->foreign('co_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('ca_packages');
    }
};
