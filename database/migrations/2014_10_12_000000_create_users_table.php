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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bussinessName');
            $table->string('idNumber')->nullable();
            $table->string('idType')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable()->comment('same as province');
            $table->string('country')->nullable();
            $table->string('zipCode')->nullable();
            $table->string('street')->nullable();
            $table->string('additionalInfo')->nullable()->comment('Line 2 comment');
            $table->string('houseNumber')->nullable();
            $table->string('verificationDocument')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
