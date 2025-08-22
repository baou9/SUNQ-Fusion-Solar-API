<?php
declare(strict_types=1);

interface CacheInterface {
    public function get(string $key): ?array;
    public function set(string $key, array $value, int $ttl): void;
}

class MemoryCache implements CacheInterface {
    /** @var array<string, array{expires: float, data: array}> */
    private static array $store = [];

    public function get(string $key): ?array {
        $item = self::$store[$key] ?? null;
        if (!$item || $item['expires'] < microtime(true)) {
            return null;
        }
        return $item['data'];
    }

    public function set(string $key, array $value, int $ttl): void {
        self::$store[$key] = [
            'expires' => microtime(true) + $ttl,
            'data' => $value,
        ];
    }
}

class FileCache implements CacheInterface {
    private string $dir;

    public function __construct(string $dir) {
        $this->dir = $dir;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function path(string $key): string {
        return $this->dir . '/' . $key . '.json';
    }

    public function get(string $key): ?array {
        $file = $this->path($key);
        if (!is_file($file)) return null;
        $json = json_decode((string)@file_get_contents($file), true);
        if (!$json || ($json['expires'] ?? 0) < microtime(true)) {
            @unlink($file);
            return null;
        }
        return $json['data'];
    }

    public function set(string $key, array $value, int $ttl): void {
        $file = $this->path($key);
        $payload = ['expires' => microtime(true) + $ttl, 'data' => $value];
        @file_put_contents($file, json_encode($payload));
    }
}

function create_cache(string $backend): CacheInterface {
    if ($backend === 'file') {
        return new FileCache(__DIR__ . '/../storage/cache');
    }
    return new MemoryCache();
}
