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

    // Auto-update settings
    'update_env' => env('VAULT_UPDATE_ENV', true),
    'update_config' => env('VAULT_UPDATE_CONFIG', true),

    // Sync mode: 'env' or 'vault'
    // 'env': Read .env file, find empty keys, then sync from Vault (default)
    // 'vault': Read all Vault secrets, then apply only if env() is empty (for Docker/container environments)
    'sync_mode' => env('VAULT_SYNC_MODE', 'env'),

    // Custom config mappings (ENV_KEY => config.path)
    // Example: 'MY_API_KEY' => 'services.myapi.key'
    'config_mappings' => [
        // Add your custom mappings here
    ],
];
