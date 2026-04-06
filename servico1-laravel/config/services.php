<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Serviços internos (inter-serviços)
    |--------------------------------------------------------------------------
    */
    'logs' => [
        'url'    => env('LOG_SERVICE_URL', 'http://localhost:8002/api'),
        'secret' => env('LOG_SERVICE_SECRET', 'segredo-interno-123'),
    ],

    'analise' => [
        'url' => env('ANALISE_SERVICE_URL', 'http://localhost:8001/api'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Serviços de terceiros (padrão Laravel)
    |--------------------------------------------------------------------------
    */
    'mailgun'  => ['domain' => env('MAILGUN_DOMAIN'), 'secret' => env('MAILGUN_SECRET'), 'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'), 'scheme' => 'https'],
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
];
