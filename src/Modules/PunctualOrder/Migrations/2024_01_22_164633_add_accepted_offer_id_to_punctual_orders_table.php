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
        Schema::table('punctual_orders', function (Blueprint $table) {
            $table->uuid('accepted_offer_id')->nullable();
            $table->foreign('accepted_offer_id')
                ->references('id')
                ->on('offers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punctual_orders', function (Blueprint $table) {
            //
        });
    }
};
