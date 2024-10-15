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
        Schema::create('punctual_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('budget');
            $table->text('description');
            $table->dateTime('desired_date');
            $table->string('address');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('service_id');
            $table->foreign('service_id')->references('id')->on('punctual_services')->onDelete('cascade');
            $table->uuid('note_id')->nullable(); 
            $table->foreign('note_id')->references('id')->on('notes')->onDelete('cascade');
            $table->boolean('accept_button_has_been_clicked')->nullable();
            $table->integer('status')->default(0); // 0: pending, 1: with_offer, 2: offer_accepted, 3: finished,
            $table->boolean('payment_button_has_been_clicked')->nullable();
            $table->dateTime('accept_button_has_been_clicked_at')->nullable();
            $table->integer('payment_method')->nullable();
            $table->json('pictures')->nullable();
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
        Schema::dropIfExists('puntual_orders');
    }
};
