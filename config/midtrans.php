<?php

return [
    'isProduction' => env('MIDTRANS_IS_PRODUCTION', false),
    'serverKey' => env('MIDTRANS_SERVER_KEY'),
    'clientKey' => env('MIDTRANS_CLIENT_KEY'),
];
