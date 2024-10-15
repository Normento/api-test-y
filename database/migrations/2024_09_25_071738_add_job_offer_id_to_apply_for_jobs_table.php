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
        Schema::table('apply_for_jobs', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apply_for_jobs', function (Blueprint $table) {
            $table->dropForeign(['job_offer_id']);
            $table->dropColumn('job_offer_id');
        });
    }
};
