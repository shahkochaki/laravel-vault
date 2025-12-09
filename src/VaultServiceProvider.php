<?php

namespace shahkochaki\Vault;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class VaultServiceProvider extends ServiceProvider
{
    private static $bootApplied = false;

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vault.php', 'vault');

        $this->app->singleton(VaultService::class, function ($app) {
            $config = $app['config']->get('vault', []);
            $rawAddr = $config['addr'] ?? env('VAULT_ADDR', '');
            $addr = trim((string) $rawAddr);
            if ($addr !== '' && !preg_match('#^https?://#i', $addr)) {
                $addr = 'http://' . $addr;
            }

            // If a port is provided separately and the address doesn't already include one, append it
            $port = $config['port'] ?? env('VAULT_PORT', null);
            if ($port && !preg_match('#:\\d+(?:$|/)#', $addr)) {
                $addr = rtrim($addr, '/') . ':' . $port;
            }

            $base = rtrim($addr, '/');

            $client = new Client([
                'base_uri' => $base !== '' ? $base . '/' : null,
                'timeout' => $config['timeout'] ?? 5,
            ]);
            // use logger from container
            $logger = $app->make(LoggerInterface::class);
            return new VaultService($client, $app['cache.store'], $logger, $config);
        });
    }

    public function boot()
    {
        // publish config
        $this->publishes([
            __DIR__ . '/../config/vault.php' => config_path('vault.php'),
        ], 'config');

        try {
            $vault = $this->app->make(VaultService::class);
            if (!$vault) return;

            if (self::$bootApplied) return;

            $config = $this->app['config']->get('vault', []);
            $path = $config['path'] ?? env('VAULT_PATH', '');
            $secretName = env('VAULT_SECRET', '');

            if ($path === '') {
                $secretPath = $secretName;
            } else {
                if (preg_match('#/data/[^/]+$#', $path)) {
                    $secretPath = $path;
                } elseif (preg_match('#/data$#', $path)) {
                    $secretPath = rtrim($path, '/') . '/' . $secretName;
                } else {
                    $secretPath = rtrim($path, '/') . '/' . $secretName;
                }
            }

            $secretPath = trim((string) $secretPath);
            if ($secretPath === '') {
                Log::debug('VaultServiceProvider: secretPath is empty; skipping Vault fetch.');
                return;
            }

            $secret = $vault->getSecret($secretPath);
            if (!is_array($secret)) {
                Log::debug('VaultServiceProvider: no secret found at ' . $secretPath);
                return;
            }

            // apply keys (simple behavior — populate config and env at runtime)
            $secretUpper = array_change_key_case($secret, CASE_UPPER);
            // set DB config if present
            if (isset($secretUpper['DB_PASSWORD'])) {
                config(['database.connections.mysql.password' => $secretUpper['DB_PASSWORD']]);
            }
            if (isset($secretUpper['DB_USER'])) {
                config(['database.connections.mysql.username' => $secretUpper['DB_USER']]);
            }
            if (isset($secretUpper['DB_HOST'])) {
                config(['database.connections.mysql.host' => $secretUpper['DB_HOST']]);
            }
            if (isset($secretUpper['DB_DATABASE'])) {
                config(['database.connections.mysql.database' => $secretUpper['DB_DATABASE']]);
            }

            // set vault.test if provided
            if (isset($secretUpper['VAULT_TEST'])) {
                config(['vault.test' => $secretUpper['VAULT_TEST']]);
            }

            self::$bootApplied = true;
        } catch (\Throwable $e) {
            Log::warning('VaultServiceProvider bootstrap vault fetch failed: ' . $e->getMessage());
        }
    }
}
