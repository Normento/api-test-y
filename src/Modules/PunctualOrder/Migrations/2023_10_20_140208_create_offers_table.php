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
        Schema::create('offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('price');
            $table->text('description')->nullable();
            $table->integer('remaining_order_price')->nullable();
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('punctual_orders')->onDelete('cascade');
            $table->uuid('professional_id');
            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');
            $table->integer('status')->default(0); // 0: pending, 1: offer_rejected, 2: offer_accepted
            $table->text("negotiation")->nullable();
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
        Schema::dropIfExists('offers');
    }
};
