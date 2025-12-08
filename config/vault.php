<?php

return [
    'addr' => env('VAULT_ADDR', 'http://127.0.0.1:8200'),
    'token' => env('VAULT_TOKEN', ''),
    'engine' => env('VAULT_ENGINE', 'secret'),
    'path' => env('VAULT_PATH', ''),
    'timeout' => 5,
    'cache_ttl' => 300,
];
