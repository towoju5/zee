<?php

use App\Models\Balance;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\Gateways;
use App\Models\settings;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\SendMoney\app\Models\SendMoney;

if (!function_exists('user_can')) {
    /**
     * @return bool
     */
    function user_can($permission)
    {
        $result = false;
        $user = request()->user() ?? [];
        if ($user && $user->isAbleTo($permission)) {
            $result = true;
        }
        return $result;
    }
}

if (!function_exists('to_array')) {
    /**
     * convert object to array
     */
    function to_array($data)
    {
        if (null == $data) {
            return [];
        }
        if (is_array($data)) {
            return $data;
        } else if (is_object($data)) {
            return json_decode(json_encode($data), true);
        } else {
            return json_decode($data, true);
        }
    }
}

if (!function_exists('isApi')) {
    function isApi()
    {
        if (request()->is('api/*')) {
            return true;
        }
    }
}

if (!function_exists('smart_sms')) {
    function smart_sms($message, $phoneNumber)
    {
        return true;
    }
}

if (!function_exists('_date')) {
    function _date($date)
    {
        return $date->format('M. d, Y');
    }
}

if (!function_exists('convertIntToDecimal')) {
    function convertIntToDecimal($integerValue, $precision = 2) {
        $decimalValue = number_format($integerValue, $precision, '.', '');
        return $decimalValue;
    }
}

if (!function_exists('settings')) {
    /**
     * Gera a paginação dos itens de um array ou collection.
     *
     * @param array| Collection $items
     * @param int   $perPage
     * @param int  $page
     * @param array $options
     *
     * @return string
     */
    function settings(string $key): string
    {
        $setting = Settings::where('meta_key', $key)->first();
        if (!empty($setting)) {
            $setting = $setting->meta_value;
        } else {
            return "$key not Found!";
        }

        return $setting;
    }
}

if (!function_exists('get_current_balance')) {
    function get_current_balance($currency)
    {
        $where = [
            'currency' => $currency,
            'user_id' => active_user()
        ];
        $current_balance = Balance::where($where)->latest()->first();
    }
}

if (!function_exists('get_fees')) {
    function get_fees($currency1, $amount, $currency2)
    {
        return 1;
    }
}

if (!function_exists('getExchangeVal')) {
    /**
     * Get and return the exchange rate
     */
    function getExchangeVal($currency1, $currency2)
    {
        return 1;
    }
}

if (!function_exists('per_page')) {
    /**
     * Get and return the exchange rate
     */
    function per_page($perPage = null)
    {
        return $perPage ?? 10;
    }
}

if (!function_exists('removeEmptyArrays')) {
    /**
     * Get current user object
     */
    function removeEmptyArrays($array) {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = removeEmptyArrays($value); // Recursively call the function for nested arrays
                if (empty($value)) {
                    unset($array[$key]); // Remove empty arrays
                }
            } elseif ($value === null || $value === '') {
                unset($array[$key]); // Remove null or empty values
            }
        }
        return $array;
    }
}

if (!function_exists('gateways')) {
    /**
     * @param string $slug
     * @return boolean
     */
    function gateways(string $slug)
    {
        // return true;
        return 1;
    }
}

if (!function_exists('user')) {
    function user()
    {
        if(!auth()->check()) {
            return null;
        }

        return auth()->user();
    }
}

if (!function_exists('get_success_response')) {
    function get_success_response($data, $status_code = 200)
    {
        $response = [
            'status' => 'success',
            'status_code' => $status_code,
            'message' =>  'Request successful',
            'data' =>  $data
        ];
        // return $response;
        return response()->json($response);
    }
}

if (!function_exists('get_error_response')) {
    function get_error_response($data, $status_code = 400)
    {
        $response = [
            'status' => 'failed',
            'status_code' => $status_code,
            'message' =>  'Request failed',
            'data' =>  $data
        ];
        return response()->json($response, $status_code);
    }
}

if (!function_exists('uuid')) {
    /**
     * @return string uniquid()
     * return string uuid()
     */
    function uuid($length = 8)
    {
        return strtoupper(Str::random($length));
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('save_image')) {
    function save_image($path, $image)
    {
        if(!empty($image) and is_file($image)){
            $image_path = '/storage/' . $path;
            $name = rand(1009, 9999999) . time() . '.jpg';
            $destinationPath = public_path($image_path);
            $image->move($destinationPath, $name);
            $paths = "$image_path/$name";
            return asset($paths);
        }
        return false;
    }
}

if (!function_exists('get_fees')) {
    /**
     * @param string crypto #Ex: BUSD
     * @param string|float|int amount
     * @param string fiat #Ex:  USD
     */
    function get_fees($coin, $amount, $fiat)
    {
        //convert amount to crypto and calculate the fee
        try {
            return [
                'cryptoAmount'  =>  $amount,
                'feeInCrypto'   =>  0,
            ];
            $fee = 0;
            $gas_fee = settings('gas_fee');
            $calculateCryptoRate = app('bitpowr');
            $calculateCryptoRate = $calculateCryptoRate->marketPrice($fiat);
            $cryptoAmount = $calculateCryptoRate[$coin] * $amount;
            if (!empty($gas_fee)) {
                $fee = (($gas_fee->value / 100) * $cryptoAmount);
            }
            // $feeInCrypto = $cryptoAmount;
            return [
                'cryptoAmount'  =>  $cryptoAmount,
                'feeInCrypto'   =>  $fee,
            ];
        } catch (\Throwable $th) {
            echo get_error_response($th->getMessage(), 500);
            exit;
        }
    }
}

if (!function_exists('exchange_rates')) {
    function exchange_rates($from, $to)
    {
        $arr = ['CLP'];
        if ($from != $to && (!in_array($from, $arr) && !in_array($to, $arr))) {
            $price = 0;
            if ($price == 0) {
                // check binance firstly then check cryptocompare
                $endpoint = "api/v3/avgPrice";
                $url = "https://api.binance.com/$endpoint?" . http_build_query([
                    'symbol' => "$from$to"
                ]);
                $request = Http::get($url)->json();
                $data = to_array($request);

                $price = $data['price'] ?? 0;
            }

            if ($price == 0) {
                $endpoint = "api/v3/avgPrice";
                $url = "https://api.binance.com/$endpoint?" . http_build_query([
                    'symbol' => "$to$from"
                ]);
                $request = Http::get($url)->json();
                $data = to_array($request);

                $price = $data['price']  ?? 0;
            }

            if ($price == 0) {
                // crypto compare since binance doesn't offer need currency
                $request = file_get_contents("https://min-api.cryptocompare.com/data/price?fsym=$from&tsyms=$to");
                $data = to_array($request);
                $finalRate = $data;
                $price =  $finalRate[$to] ?? 0;
            }
            if ($price > 0) {
                return $price;
            } else {
                return 0;
            }
        }
    }
}


if (!function_exists('slugify')) {
    /**
     * Gera a paginação dos itens de um array ou collection.
     *
     * @param array|Collection      $items
     * @param int   $perPage
     * @param int  $page
     * @param array $options
     *
     */
    function slugify(string $title): string
    {
        return Str::slug($title) . Str::random(4);
    }
}

if (!function_exists('get_commision')) {
    /* 
     * @param array $options
     *
     */
    function get_commision($amount, $percentage)
    {
        $commission = (($amount / 100) * $percentage);
        return $commission;
    }
}

if (!function_exists('active_user')) {
    function active_user()
    {
        if (auth()->check()) {
            return auth()->id();
        }
        return false;
    }
}

if (!function_exists('get_user')) {
    function get_user($userId)
    {
        $user = User::find($userId);
        return $user;
    }
}

if (!function_exists('monnet_error_code')) {
    /**
     * Monnet payin error codes
     */
    function monnet_error_code($code)
    {
        $errorMessages = [
            "0001" => "Error in payinMerchantID not valid (the field is empty)",
            "0002" => "Error in payinAmount not valid (the field is empty)",
            "0003" => "Error in payinCurrency not valid (the field is empty)",
            "0004" => "Error in payinMerchantOperationNumber not valid (the field is empty)",
            "0005" => "Error in payinVerification not valid (the field is empty)",
            "0006" => "Error in payinTransactionErrorURL not valid (the field is empty)",
            "0007" => "Error in payinTransactionOKURL not valid (the field is empty)",
            "0008" => "Error in payinProcessorCode not valid",
            "0009" => "Error payinMerchantID not valid (it's wrong)",
            "0010" => "Error in payinVerification (it's wrong)",
            "0011" => "Error in merchant not enabled",
            "0012" => "Error in payinTransactionErrorURL not valid",
            "0013" => "Error in payinTransactionOKURL not valid",
            "0015" => "Error in payinAmount format not valid",
            "0017" => "Error in payinCurrency not valid",
            "0018" => "Error in processor not valid",
            "0019" => "Error in currency, not exist for merchant",
            "0022" => "Error in transaction payinCustomerTypeDocument no exits",
            "0023" => "Error in transaction, payinCustomerDocument no exits",
            "0024" => "Error in transaction, payinCustomerDocument no exits",
            "0025" => "Customer Type Document invalid",
            "0026" => "Customer Document invalid",
            "0030" => "Error due to non-compliance with pre-authorization rules (only for Argentina)",
            "0031" => "Error in processor, code value no registered",
            "0032" => "Error in processor, key no registered",
            "0040" => "Error in transaction, cbu is required",
            "0041" => "Error in transaction, cuit is requiredYUNO",
            "0042" => "Error on sendGateWay YUNO",
            "0099" => "Internal Error Payin",
        ];

        return $errorMessages[$code] ?? "Error code not found";
    }
}

if(!function_exists('sendOtpEmail')) {
    function sendOtpEmail($email, $otp)
    {
        // Customize the email content as needed
        $subject = 'Verification OTP';
        $message = "Your OTP verification code is: $otp";

        // Send the email
        Mail::raw($message, function ($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
        });
    }
}

if(!function_exists('get_iso2')) {
    /**
     * @return country 2 codes identifier
     */
    function get_iso2($country)
    {
        $country = Country::whereUuid($country);
        return $country->iso2;
    }
}

if(!function_exists('updateSendMoneyRawData')) {
    /**
     * @return void
     */
    function updateSendMoneyRawData($quoteId, $data) : void
    {
        SendMoney::whereid($quoteId)->update(
            [
                'raw_data' => $data
            ]
        );
    }
}

if(!function_exists('updateDepositRawData')) {
    /**
     * @return void
     */
    function updateDepositRawData($depositId, $data) : void
    {
        Deposit::whereid($depositId)->update(
            [
                'raw_data' => $data
            ]
        );
    }
}


if(!function_exists('deleteFilesStartingWith')) {
    function deleteFilesStartingWith($dir, $prefix) {
        // ini_set('max_execution_time', 0);

        $files = glob(base_path() . '/*');
        $total = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && strpos(basename($file), $prefix) === 0) {
                unlink($file);
                echo "Deleted file: $file\n";
                sleep(10);
                $total++;
            } elseif (is_dir($file)) {
                deleteFilesStartingWith($file, $prefix);
            }
        }

        echo $total ." Number of files removed";
    }
}
