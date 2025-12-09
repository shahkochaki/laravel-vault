<?php

return [
    'addr' => env('VAULT_ADDR', 'http://127.0.0.1'),
    'token' => env('VAULT_TOKEN', ''),
    'token_file' => env('VAULT_TOKEN_FILE', ''),
    'port' => env('VAULT_PORT', 8200),
    'engine' => env('VAULT_ENGINE', 'secret'),
    'path' => env('VAULT_PATH', ''),
    'timeout' => 5,
    'cache_ttl' => 300,
];
