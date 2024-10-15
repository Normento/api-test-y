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
        Schema::create('professional_punctual_service', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('professional_id');
            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');
            $table->uuid('punctual_service_id');
            $table->foreign('punctual_service_id')->references('id')->on('punctual_services')->onDelete('cascade');
            $table->integer('price')->nullable();
            $table->text('description')->nullable();
            $table->json('works_picture')->nullable();
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
        Schema::dropIfExists('professional_punctual_service');
    }
};
