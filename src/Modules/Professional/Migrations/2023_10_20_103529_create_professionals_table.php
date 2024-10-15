<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('enterprise_name')->nullable();
            $table->string('address');
            $table->string('email')->nullable();
            $table->string('phone_number');
            $table->dateTime('accepted_at')->nullable();
            $table->string('profile_image');
            $table->string('confirmation_code')->nullable();
            $table->integer('status')->default(0);//0= non validé; 1= validé; 2=suspendu;3=candidature spontanée
            $table->uuid('saved_by')->nullable();
            $table->uuid('wallet_id')->nullable();
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('saved_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement('CREATE UNIQUE INDEX professionals_unique ON professionals
        USING btree (email, phone_number) WHERE (deleted_at IS NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX professionals_unique');

        Schema::dropIfExists('professionals');
    }
};
