<?php

return [
    'name' => 'PayPal',
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret' => env('PAYPAL_SECRET_ID'),
    'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
];
