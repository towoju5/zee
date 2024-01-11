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
        Schema::create('send_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->string('send_amount');
            $table->string('receive_amount');
            $table->string('send_gateway');
            $table->string('receive_gateway');
            $table->string('send_currency');
            $table->string('receive_currency');
            $table->string('transfer_purpose');
            $table->string('rate');
            $table->string('user_id');
            $table->string('total_amount');
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_quotes');
    }
};
