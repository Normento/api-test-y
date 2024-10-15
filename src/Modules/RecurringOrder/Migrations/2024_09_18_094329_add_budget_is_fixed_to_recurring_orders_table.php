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
            $table->boolean('budget_is_fixed')->nullable()->after('discount_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_orders', function (Blueprint $table) {
            $table->dropColumn('budget_is_fixed');
        });
    }
};
