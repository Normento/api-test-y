<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tags')->insert([
            ['id' => (string) Str::uuid(), 'name' => 'Courtois'],
            ['id' => (string) Str::uuid(), 'name' => 'Dirigant'],
            ['id' => (string) Str::uuid(), 'name' => 'Fiable'],
            ['id' => (string) Str::uuid(), 'name' => 'Assidu'],
            ['id' => (string) Str::uuid(), 'name' => 'Ponctuel'],
            ['id' => (string) Str::uuid(), 'name' => 'En retard'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
