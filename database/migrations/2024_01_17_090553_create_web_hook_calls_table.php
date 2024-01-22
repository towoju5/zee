<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebHookCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_hook_calls', function (Blueprint $table) {
            $table->id();
            $table->string('transactionReference');
            $table->string('paymentReference');
            $table->string('amountPaid');
            $table->string('totalPayable');
            $table->string('paidOn');
            $table->string('paymentStatus');
            $table->string('paymentDescription');
            $table->string('transactionHash');
            $table->string('currency');
            $table->string('paymentMethod');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_hook_calls');
    }
}
