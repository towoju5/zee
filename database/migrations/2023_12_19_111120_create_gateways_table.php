<?php

use App\Models\Gateways;
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
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name');
            $table->string('slug');
            $table->boolean('status')->default(false);
            $table->boolean('payout')->default(false);
            $table->boolean('deposit')->default(false);
            $table->json('payin_currencies')->nullable();
            $table->json('payout_currencies')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();
        });

        $arr = [
            [
                'gateway_name' => "Binance Pay",
                'slug' => "binance_pay",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["USDT"],
                'payout_currencies' => ["USDT"],
            ],
            [
                'gateway_name' => "Flutterwave",
                'slug' => "flutterwave",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["EUR", "GBP", "USD"],
                'payout_currencies' => ["EUR", "GBP", "USD"],
            ],
            [
                'gateway_name' => "Monnet",
                'slug' => "monnet",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["CLP", "MXN", "PEN"],
                'payout_currencies' => ["CLP", "MXN", "PEN"],
            ],
            [
                'gateway_name' => "Transak",
                'slug' => "transak",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["CLP", "MXN", "PEN"],
                'payout_currencies' => ["USDC"],
            ],
            [
                'gateway_name' => "PayPal",
                'slug' => "paypal",
                'status' => 1,
                'payout' => 1,
                'deposit' => 0,
                'payin_currencies' => null,
                'payout_currencies' => ["USD"],
            ],
            [
                'gateway_name' => "Flow",
                'slug' => "flow",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["CLP"],
                'payout_currencies' => ["CLP"],
            ],
            [
                'gateway_name' => "CoinPayments",
                'slug' => "coinpayment",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["BTC","ETH","BNB","LTC","USDT","USDC"],
                'payout_currencies' => ["USDC"],
            ],
            [
                'gateway_name' => "LocalPayments",
                'slug' => "local_payment",
                'status' => 1,
                'payout' => 1,
                'deposit' => 1,
                'payin_currencies' => ["BTC","ETH","BNB","LTC","USDT","USDC"],
                'payout_currencies' => ["USDC"],
            ],
        ];

        // Insert data into the gateways table
        foreach ($arr as $gateway) {
            Gateways::create($gateway);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
