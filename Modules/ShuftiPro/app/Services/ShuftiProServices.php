<?php

namespace Modules\ShuftiPro\app\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShuftiProServices
{
    public function init(Request $request)
    {
        try {
            //Shufti Pro API base URL
            $url = 'https://api.shuftipro.com/';
            $client_id    = getenv('SHUFTI_PRO_CLIENT_ID');
            $secret_key = getenv('SHUFTI_PRO_SECRET_KEY');
            $user = User::find(active_user());
            $verification_request = [
                "reference"  => uuid(),
                "journey_id"=> "qlNhsBgo1706225885",
                "email" => $user->email,
            ];
            $auth       = $client_id . ":" . $secret_key;
            $headers    = ['Content-Type: application/json'];
            $post_data  = json_encode($verification_request);
            return $response   = self::api_call($url, $post_data, $headers, $auth);
            if($response["error"]) {
                return get_error_response(['error' => $response['error']['message']]);
            }
            $response_data  = $response['body'];
            $sp_signature   = self::get_header_keys($response['headers'])['signature'];
            $calculate_signature = hash('sha256', $response_data . hash('sha256', $secret_key));
            $decoded_response = json_decode($response_data, true);

            if ($sp_signature == $calculate_signature) {
                return get_success_response($decoded_response);
            } else {
                echo "Invalid signature :	$response_data";
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function callback(Request $request)
    {
        try {
            if($request->has('event') && $request->event == "verification.accepted") {
                return get_error_response(["status" => 'Verification completed successfully']);
            }
        } catch (\Throwable $th) {
            return get_error_response(['error' => $th->getMessage()]);
        }
    }

    public function get_header_keys($header_string)
    {
        $headers = [];
        $exploded = explode("\n", $header_string);
        if (!empty($exploded)) {
            foreach ($exploded as $key => $header) {
                if (!$key) {
                    $headers[] = $header;
                } else {
                    $header = explode(':', $header);
                    $headers[trim($header[0])] = isset($header[1]) ? trim($header[1]) : "";
                }
            }
        }

        return $headers;
    }

    public function api_call($url, $post_data, $headers, $auth)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $html_response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($html_response, 0, $header_size);
        $body = substr($html_response, $header_size);
        curl_close($ch);
        return ['headers' => to_array($headers), 'body' => to_array($body)];
    }

    private function get_gender($gender) 
    {
        if(strlen($gender) > 1) {
            $gender = substr($gender, 0, 1);
        }

        return $gender;
    }
}
