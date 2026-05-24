<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // En desarrollo, '*' es más seguro para evitar discrepancias localhost/127.0.0.1

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'], // Necesario para que Angular vea info del archivo si se requiere

    'max_age' => 0,

    'supports_credentials' => true, 
];