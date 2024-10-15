<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('address');
            $table->date('birthday')->nullable();
            $table->string('marital_status');
            $table->string('phone_number')->nullable();
            $table->dateTimeTz('accepted_at')->nullable();
            $table->integer('status')->default(0);
            $table->integer('type')->default(0);
            $table->string('profile_image')->nullable();
            $table->string('degree');
            $table->string('nationality');
            $table->string('flooz_number')->nullable();
            $table->string('mtn_number')->nullable();
            $table->text('suspend_reason')->nullable();
            $table->json('proof_files')->nullable();
            $table->json("pictures")->nullable();
            $table->boolean('is_share')->default(false);
            $table->text('share_observation')->nullable();
            $table->string('ifu')->nullable();
            $table->string('caution_signature')->nullable();
            $table->string('confirmation_code')->nullable();
            $table->uuid('wallet_id')->nullable();
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade')->onUpdate('cascade');
            $table->uuid('partner_id')->nullable();
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade')->onUpdate('cascade');

            $table->uuid('focal_point_id')->nullable();
            $table->foreign('focal_point_id')->references('id')->on('focal_points')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->uuid('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->uuid('saved_by')->nullable();
            $table->foreign('saved_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
        DB::statement('CREATE UNIQUE INDEX employees_unique ON employees
        USING btree (phone_number) WHERE (deleted_at IS NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX employees_unique');

        Schema::dropIfExists('employees');
    }
};
