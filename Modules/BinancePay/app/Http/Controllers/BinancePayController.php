<?php

namespace Modules\BinancePay\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class BinancePayController extends Controller
{
    public function init(int $quoteId, float $amount, string $currency)
    {
        try {
            $trxId = uuid();
            $fee = self::getfees($amount);
            $payload = [
                'env' => [
                    'terminalType' => 'APP',
                ],
                'orderTags' => [
                    'ifProfitSharing' => false,
                ],
                'merchantTradeNo' => $trxId,
                'fiatAmount' => $amount + $fee,
                'fiatCurrency' => $currency,
                'goods' => [
                    'goodsType' => '02',
                    'goodsCategory' => 'Z000',
                    'referenceGoodsId' => '7876763A3B',
                    'goodsName' => 'Wallet TopUp',
                    'goodsDetail' => 'Wallet Topup for customer ' . auth()->user()->businessName,
                ],
                'returnUrl' => getenv('APP_URL').'dashboard',
                'webhookUrl' => 'https://api.zinar.io/api/binance-webhook',
            ];

            $call = $this->api_call('/binancepay/openapi/v2/order', 'POST', $payload);
            $apiRequest = "binance-pay";

            $user = request()->user();
            $fees = get_fees("USDT", $amount, $currency);
            $_rate = getExchangeVal($currency, "USDT");
            $amountInCrypto = floatval($_rate * $amount);
            // balance  before after
            $bba = get_current_balance($currency);

            $deposit = Deposit::create([
                "user_id" => $user->id,
                'deposit_id' => $trxId,
                'deposit_fee' => $fee,
                "fiat" => $currency,
                "amount" => $amount,
                "cryptoAmount" => number_format($amountInCrypto, 8),
                "wallet_type" => "USDT",
                "wallet_address" => $apiRequest,
                "timeout" => now()->addMinutes(30),
                "raw_data" => $call,
                "balance_before" => $bba['before'],
                "balance_after" => $bba['after'],
            ]);

            $deposit['id'] = $deposit->id;

            if ($deposit) {
                $merchant = $user->id;
                $deposit_data = [
                    'user_id' => $merchant,
                    "reference" => $trxId,
                    'customerName' => $user->name,
                    'customerEmail' => $user->email,
                    'coin' => "USDT",
                    'currency' => $currency,
                    'fiatAmount' => $amount,
                    'cryptoAmount' => number_format($amountInCrypto, 8),
                    'feeInCrypto' => $fee,
                    'order_type' => 'crypto_Topup',
                    'address' => $apiRequest,
                    'ip_address' => request()->ip(),
                    'exchange_rate' => $_rate,
                ];

                Transaction::create($deposit_data);
                
                updateSendMoneyRawData($quoteId, $call);
                return $call;
            } else {
                return get_error_response(["error" => "Unable to initiate deposit action."]);
            }
        } catch (\Throwable $th) {
            return get_error_response($th->getMessage(), 500);
        }
    }

    public static function verifyOrder($trxId)
    {
        $endpoint = "/binancepay/openapi/v2/order/query";
        $payload = [
            "merchantTradeNo" => $trxId,
        ];
        $call = (new BinancePayController())->api_call($endpoint, 'POST', $payload);
        return to_array($call);
    }

    public function selfVerifyOrder()
    {
        $endpoint = "/binancepay/openapi/v2/order/query";
        $deposits = Deposit::where('deposit_status', 'pending')->get();
        foreach ($deposits as $deposit) {
            $userId = $deposit->user_id;
            $order = Transaction::where("reference", $deposit->deposit_id)->first();
            $payload = ["merchantTradeNo" => $deposit->deposit_id];
            // $payload = ["merchantTradeNo" => "654F7C4B65608"];
            $verify = self::api_call($endpoint, 'POST', $payload);
            if (isset($verify['data'])) {
                $data = $verify['data'];
                if ($data['status'] == "PAID" and $deposit->deposit_status == 'pending' and $order->status == 'pending') {
                    $order->status = "completed";
                    $deposit->deposit_status = 'success';
                    $rechargeAmount = floatval($data['orderAmount'] - $deposit->deposit_fee);
                    $order->save();
                    if ($deposit->save()) {
                        // \Log::info($rechargeAmount);
                        // $user__balance = Balance::where(["user_id" => $userId, "ticker_name" => "USDT"])->first();
                        // $save = $user__balance->increment('balance', $rechargeAmount);
                        // \Log::error($save);
                    }
                }
            }
        }
    }

    public function withdrawal(Request $request)
    {
        try {
            $request->validate([
                'wallet_type' => 'required',
                'amount' => 'required',
                'wallet_address' => 'required',
                'type' => 'required',
            ], [
                'wallet_type.requierd' => "Crypto currency is required",
                'wallet_address.requierd' => "Wallet address is required",
                'amount.required' => "Please provide topup amount",
            ]);

            $trxId = uuid();
            $user = $request->user();
            $fee = 0; // self::getfees($request->amount);
            $total__amount = round($request->amount - $fee, 2);
            $payload = [
                'requestId' => $trxId,
                'batchName' => $trxId,
                'currency' => $request->wallet_type,
                'totalAmount' => $total__amount,
                'totalNumber' => 1,
                'bizScene' => 'MERCHANT_PAYMENT',
                'transferDetailList' => [
                    [
                        'merchantSendId' => '716022147',
                        'transferAmount' => $total__amount,
                        'receiveType' => strtoupper($request->type),
                        'transferMethod' => 'SPOT_WALLET',
                        'receiver' => $request->wallet_address,
                        'remark' => "Withdrawal via $request->type to $request->wallet_address by @$user->bussinessName",
                    ],
                ],
            ];
            $apiRequest = "binance-pay";
            $where['user_id'] = $request->user()->id;
            $where['ticker_name'] = $request->wallet_type;
            $balance = Balance::where($where)->first();
            $oldBalance = $balance->balance;
            // echo "here i am";
            if ($balance && $balance->balance < $request->amount) {
                return get_error_response(["error" => "Insufficient wallet balance"]);
            }

            $balance->decrement('balance', $request->amount);
            $save = Withdraw::create([
                "user_id" => $request->user()->id,
                'transaction_id' => $trxId,
                "amount" => $request->amount,
                "wallet_type" => $request->wallet_type,
                "wallet_address" => $request->wallet_address,
                'ip_address' => $request->ip(),
                'balance_before' => $oldBalance,
                'balance_after' => $oldBalance - $request->amount,
            ]);
            // $save['id'] = $save->id;

            // if($save){
            $merchant = $user->id;
            $withdrawal_data = [
                'user_id' => $merchant,
                "reference" => $trxId,
                'customerName' => $user->name,
                'customerEmail' => $user->email,
                'coin' => $request->wallet_type,
                'currency' => $request->currency,
                'fiatAmount' => $request->amount,
                'cryptoAmount' => number_format($amountInCrypto ?? 0, 8),
                'feeInCrypto' => $fee,
                'order_type' => 'crypto_payout',
                'address' => $apiRequest,
                'ip_address' => $request->ip(),
                'exchange_rate' => 1,
                'balance_before' => $oldBalance,
                'balance_after' => floatval($oldBalance - $request->amount),
            ];

            // store withdrawal data in ::DB
            $createOrder = Order::create($withdrawal_data);
            // return get_success_response(["msg" => "Your withdrawal request is received and will be processed soon.", "data" => $createOrder]);

            // Make a payout request to Binance pay endpoint
            $call = $this->api_call('/binancepay/openapi/payout/transfer', 'POST', $payload);
            $response = $call;
            if (isset($response['data'])) {
                return get_success_response(["msg" => "Your withdrawal request is received and will be processed soon.", "data" => $response['data']]);
            } else {
                Log::info("Error while making a payout request by user ID: $merchant  with the response:  " . json_encode($call));
                return get_error_response(["error" => $response['errorMessage']]);
            }
        } catch (\Throwable $th) {
            return get_error_response(["error" => $th->getMessage()], 500);
        }
    }

    private function api_call(string $url, string $method, array $request = [])
    {
        // Generate nonce string
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nonce = '';
        for ($i = 1; $i <= 32; $i++) {
            $pos = mt_rand(0, strlen($chars) - 1);
            $char = $chars[$pos];
            $nonce .= $char;
        }
        $ch = curl_init();
        $timestamp = round(microtime(true) * 1000);
        $json_request = json_encode($request);
        $payload = $timestamp . "\n" . $nonce . "\n" . $json_request . "\n";
        $binance_pay_key = "hrgonasego2ljjgphbecde6s3mfdbsnmoev2ngmw0vidbgwj4dmh8mylbkoeikv0";
        $binance_pay_secret = "tjmjcrludtsfulcx2z1s6ztwy7twhwctoobrbqok4q78krly0q3e1fbgbdt43vgh";
        $signature = strtoupper(hash_hmac('SHA512', $payload, $binance_pay_secret));
        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "BinancePay-Timestamp: $timestamp";
        $headers[] = "BinancePay-Nonce: $nonce";
        $headers[] = "BinancePay-Certificate-SN: $binance_pay_key";
        $headers[] = "BinancePay-Signature: $signature";

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, "https://bpay.binanceapi.com" . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = get_error_response(['error' => 'Error:' . curl_error($ch), 400]);
        }
        curl_close($ch);
        $response = to_array($result);
        return $response;
    }

    public function get_rate()
    {
        $endpoint = "api/v3/avgPrice";
        $url = "https://api.binance.com/$endpoint?".http_build_query([
            'symbol' => "BTCUSDT",
        ]);
        $request = Http::get($url)->toJson();
        $data = to_array($request);

        if (!isset($data['price'])) {
            $data['price'] = 1 / 1000;
        }
    }

    public function getfees($amount, $currency = 'USDT')
    {
        $fee = DepositFee::where('currency', $currency)->first();
        $fees = floatval(get_commision($amount, $fee->percentage) + $fee->flat);
        return $fees;
    }
}
