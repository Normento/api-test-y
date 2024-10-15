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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->integer('views')->default(0);
            $table->integer('status')->default(0);  // 0 = EN ATTENTE DE VALIDATION, 1 = VALIDÉ, 2 = PUBLIÉ, 3 = REJETÉ
            $table->string('image')->nullable();
            $table->dateTime('published_date')->nullable();
            $table->string('slug');
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('published_by')->nullable();
            $table->foreign('published_by')->references('id')->on('users')->onDelete('cascade');
            $table->dateTime('validation_date')->nullable();
            $table->text('rejection_reason')->nullable();
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
        Schema::dropIfExists('posts');
    }
};
