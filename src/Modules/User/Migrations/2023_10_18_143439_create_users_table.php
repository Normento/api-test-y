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


        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('profile_image')->nullable();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('email');
            $table->string('password');
            $table->string('phone_number');
            $table->boolean("is_activated")->default(false);
            $table->string('id_card')->nullable();
            $table->boolean("is_certified")->default(false);

            $table->boolean('is_company')->default(false);
            $table->string('company_name')->nullable();
            $table->string('ifu')->nullable()->unique();
            $table->string('company_address')->nullable();

            $table->string('verification_code')->nullable();
            $table->string('token')->nullable(); //for reset password
            $table->text('delete_account_reason')->nullable();

            $table->uuid('wallet_id')->nullable();
            $table->foreign('wallet_id')->references('id')->nullable()->on('wallets')->onDelete('cascade')->onUpdate('cascade');

            $table->boolean('status')->default(true); // True user admin actif False admin unactif
            $table->dateTime('deactivate_date')->nullable();
            $table->string('notif_token')->nullable();

            $table->string('signature')->nullable();
            $table->string('contract')->nullable();
            $table->boolean('contract_status')->nullable(); // True user approve contract:  False user disapprove contract
            $table->text('contract_rejection_reason')->nullable();
            $table->date('contract_start_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });


        DB::statement('CREATE UNIQUE INDEX users_unique ON users
        USING btree (email, phone_number) WHERE (deleted_at IS NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX users_unique');
        Schema::dropIfExists('users');
    }
};
