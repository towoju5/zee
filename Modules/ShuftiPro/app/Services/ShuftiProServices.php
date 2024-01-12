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
                "reference"             => uuid(),            //your unique request reference
                "callback_url"          => "https://zeenahapp.azurewebsites.net/shufti-pro/callback/$user->id",         //URL where you will receive the webhooks from Shufti Pro
                "email"                 => $user->email,           //end-user email
                "country"               => get_iso2($user->country) ?? null,          //end-user country
                "language"              => "EN",            //select ISO2 code for your desired language on verification screen
                "redirect_url"          => "https://voubeta.com",           //URL where end-user will be redirected after verification completed
                "verification_mode"     => "any",           //what kind of proofs will be provided to Shufti Pro for verification?
                "allow_offline"         => "0",         //allow end-user to upload verification proofs if the webcam is not accessible
                "allow_online"          => "1",         //allow end-user to upload real-time or already catured proofs
                "show_privacy_policy"   => "1",            //privacy policy screen will be shown to end-user
                "show_results"          => "1",            //verification results screen will be shown to end-user
                "show_consent"          => "1",            //consent screen will be shown to end-user
                "show_feedback_form"    => "0"         //User cannot send Feedback
            ];
            //face onsite verification
            $verification_request['face'] = [];
            //document onsite verification with OCR
            $verification_request['document'] = [
                'dob'                   => $user->dob,
                'gender'                => $user->gender,
                'place_of_issue'        => $user->idIssuedAt,
                'document_number'       => $user->idNumber,
                'expiry_date'           => $user->idExpiryDate,
                'issue_date'            => $user->idIssueDate,
                'fetch_enhanced_data'   => "1",
                'supported_types'       => ['id_card', 'passport']
            ];
            $auth       = $client_id . ":" . $secret_key;
            $headers    = ['Content-Type: application/json'];
            $post_data  = json_encode($verification_request);
            $response   = self::api_call($url, $post_data, $headers, $auth);
            $response_data  = $response['body'];
            $sp_signature   = self::get_header_keys($response['headers'])['signature'];
            $calculate_signature = hash('sha256', $response_data . hash('sha256', $secret_key));
            $decoded_response = json_decode($response_data, true);

            if ($sp_signature == $calculate_signature) {
                return get_success_response([$response_data, $decoded_response]);
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
        return ['headers' => $headers, 'body' => $body];
    }
}
