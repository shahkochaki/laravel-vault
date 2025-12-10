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
    protected ?string $customEngine = null;
    protected ?string $customPath = null;

    public function __construct(Client $http, CacheRepository $cache, LoggerInterface $log, array $config = [])
    {
        $this->http = $http;
        $this->cache = $cache;
        $this->log = $log;
        $this->config = $config;
    }

    /**
     * Set a custom engine for the next requests (overrides config)
     *
     * @param string $engine The engine name (e.g., 'secret', 'kv', 'custom-engine')
     * @return self
     */
    public function setEngine(string $engine): self
    {
        $this->customEngine = $engine;
        return $this;
    }

    /**
     * Get the current engine (custom or from config)
     *
     * @return string
     */
    public function getEngine(): string
    {
        return $this->customEngine ?? ($this->config['engine'] ?? 'secret');
    }

    /**
     * Reset engine to config default
     *
     * @return self
     */
    public function resetEngine(): self
    {
        $this->customEngine = null;
        return $this;
    }

    /**
     * Set a custom base path for the next requests (overrides config)
     *
     * @param string $path The base path (e.g., 'app/production', 'my/custom/path')
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->customPath = $path;
        return $this;
    }

    /**
     * Get the current base path (custom or from config)
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->customPath ?? ($this->config['path'] ?? '');
    }

    /**
     * Reset path to config default
     *
     * @return self
     */
    public function resetPath(): self
    {
        $this->customPath = null;
        return $this;
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

        // Use custom engine if set, otherwise fall back to config
        $engine = $this->getEngine();
        $engine = trim($engine, '/');

        // Assume KV v2 at engine path: /v1/{engine}/data/{path}
        return 'v1/' . $engine . '/data/' . ltrim($p, '/');
    }

    /**
     * Read data from Vault (supports KV v2 response shape).
     * Returns associative array of data or null.
     *
     * @param string $path The secret path (relative to base path if set)
     * @return array|null
     */
    public function read(string $path): ?array
    {
        // Build full path: basePath + path
        $basePath = $this->getPath();
        $fullPath = $basePath ? rtrim($basePath, '/') . '/' . ltrim($path, '/') : $path;

        // Include custom engine and path in cache key if set
        $cacheKey = 'vault_secret_' . md5($fullPath . json_encode($this->config) . ($this->customEngine ?? '') . ($this->customPath ?? ''));
        $ttl = $this->config['cache_ttl'] ?? 30;
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) return $cached;

        try {
            $requestPath = $this->buildRequestPath($fullPath);
            $res = $this->http->request('GET', $requestPath, [
                'headers' => $this->buildHeaders(),
                'timeout' => $this->config['timeout'] ?? 5,
            ]);
            $body = json_decode((string) $res->getBody(), true);
            $status = method_exists($res, 'getStatusCode') ? $res->getStatusCode() : null;
            if ($status && $status >= 400) {
                $this->log->warning("VaultService read HTTP {$status} for {$requestPath}");
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
            $this->log->warning('VaultService read failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Alias for read() method for backward compatibility
     * @deprecated Use read() instead
     */
    public function getSecret(string $path): ?array
    {
        return $this->read($path);
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
        // Build full path like in read()
        $basePath = $this->getPath();
        $fullPath = $basePath ? rtrim($basePath, '/') . '/' . ltrim($path, '/') : $path;

        // Clear cache for both default and custom engine/path
        $key = 'vault_secret_' . md5($fullPath . json_encode($this->config));
        $this->cache->forget($key);

        if ($this->customEngine !== null || $this->customPath !== null) {
            $keyCustom = 'vault_secret_' . md5($fullPath . json_encode($this->config) . ($this->customEngine ?? '') . ($this->customPath ?? ''));
            $this->cache->forget($keyCustom);
        }
    }
}
