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
        Schema::table('propositions', function (Blueprint $table) {
            $table->boolean('is_professional_break')->after('end_type')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propositions', function (Blueprint $table) {
        $table->dropColumn('is_professional_break');
        });
    }
};
