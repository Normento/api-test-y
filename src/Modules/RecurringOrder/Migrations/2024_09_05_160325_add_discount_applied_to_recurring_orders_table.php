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
        Schema::table('recurring_orders', function (Blueprint $table) {
            $table->integer('discount_rate')->nullable()->after('customer_budget');
            $table->boolean('discount_applied')->nullable()->after('discount_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_rate', 'discount_applied']);
        });
    }
};