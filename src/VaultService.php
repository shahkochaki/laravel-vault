<?php

namespace shahkochaki\Vault;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class VaultService
{
    protected Client $http;
    protected CacheRepository $cache;
    protected LoggerInterface $log;
    protected array $config;

    public function __construct(Client $http, CacheRepository $cache, LoggerInterface $log, array $config = [])
    {
        $this->http = $http;
        $this->cache = $cache;
        $this->log = $log;
        $this->config = $config;
    }

    protected function normalizeAddr(?string $rawAddr, $port = null): string
    {
        $addr = trim((string) $rawAddr);
        if ($addr === '') return '';
        // If scheme is missing, default to http
        if (!preg_match('#^https?://#i', $addr)) {
            $addr = 'http://' . $addr;
        }
        // if no port present and port provided, append it
        if ($port && !preg_match('#:\d+$#', $addr)) {
            $addr = rtrim($addr, '/') . ':' . $port;
        }
        return rtrim($addr, '/');
    }

    protected function buildRequestPath(string $path): string
    {
        $p = trim($path);
        if ($p === '') return '';

        // If already an absolute URL or API path, use as-is (Guzzle will respect absolute URLs)
        if (preg_match('#^https?://#i', $p) || preg_match('#^/v1/#', $p) || preg_match('#^v1/#', $p)) {
            return ltrim($p, '/');
        }

        $engine = $this->config['engine'] ?? 'secret';
        $engine = trim($engine, '/');

        // Assume KV v2 at engine path: /v1/{engine}/data/{path}
        return 'v1/' . $engine . '/data/' . ltrim($p, '/');
    }

    /**
     * Read a Vault secret (supports KV v2 response shape).
     * Returns associative array of secret data or null.
     */
    public function getSecret(string $path): ?array
    {
        $cacheKey = 'vault_secret_' . md5($path . json_encode($this->config));
        $ttl = $this->config['cache_ttl'] ?? 30;
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) return $cached;

        try {
            $requestPath = $this->buildRequestPath($path);
            $res = $this->http->request('GET', $requestPath, [
                'headers' => $this->buildHeaders(),
                'timeout' => $this->config['timeout'] ?? 5,
            ]);
            $body = json_decode((string) $res->getBody(), true);
            $status = method_exists($res, 'getStatusCode') ? $res->getStatusCode() : null;
            if ($status && $status >= 400) {
                $this->log->warning("VaultService getSecret HTTP {$status} for {$requestPath}");
                if ($status === 404) return null;
            }
            if (isset($body['data']['data']) && is_array($body['data']['data'])) {
                $data = $body['data']['data'];
            } elseif (isset($body['data']) && is_array($body['data'])) {
                $data = $body['data'];
            } else {
                $data = [];
            }
            $this->cache->put($cacheKey, $data, $ttl);
            return $data;
        } catch (\Throwable $e) {
            $this->log->warning('VaultService getSecret failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];
        $token = $this->config['token'] ?? null;
        // allow token to be provided via a token file (e.g. Vault Agent or mounted secret)
        if (empty($token) && !empty($this->config['token_file'])) {
            $file = $this->config['token_file'];
            if (is_readable($file)) {
                try {
                    $content = trim((string) @file_get_contents($file));
                    if ($content !== '') {
                        $token = $content;
                        $this->log->debug('VaultService: using token from token_file');
                    }
                } catch (\Throwable $e) {
                    $this->log->warning('VaultService token_file read failed: ' . $e->getMessage());
                }
            }
        }

        if (!empty($token)) {
            $headers['X-Vault-Token'] = $token;
        }
        return $headers;
    }

    public function clearCache(string $path): void
    {
        $key = 'vault_secret_' . md5($path . json_encode($this->config));
        $this->cache->forget($key);
    }
}
