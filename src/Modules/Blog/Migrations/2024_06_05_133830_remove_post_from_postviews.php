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
        Schema::table('postviews', function (Blueprint $table) {
            $table->dropColumn('post');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postviews', function (Blueprint $table) {
            $table->string('post'); // Ajustez le type de colonne selon vos besoins
        });
    }
};
