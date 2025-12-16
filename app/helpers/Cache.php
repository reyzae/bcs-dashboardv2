<?php
/**
 * Cache Helper - Simple caching layer
 * Gunakan Redis jika tersedia, fallback ke file system
 */

require_once __DIR__ . '/Debug.php';

class Cache
{
    private $redis = null;
    private $useRedis = false;
    private $cacheDir;

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../storage/cache';

        // Create cache directory jika belum ada
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Cek apakah Redis extension tersedia
        if (extension_loaded('redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->useRedis = true;
                Debug::log('Cache using Redis');
            } catch (Exception $e) {
                Debug::log('Redis not available, using file cache');
                $this->useRedis = false;
            }
        }
    }

    /**
     * Get cached value
     */
    public function get($key, $default = null)
    {
        if ($this->useRedis) {
            $value = $this->redis->get($key);
            return $value !== false ? json_decode($value, true) : $default;
        } else {
            return $this->getFile($key, $default);
        }
    }

    /**
     * Set cache value
     */
    public function set($key, $value, $ttl = 3600)
    {
        if ($this->useRedis) {
            $this->redis->setex($key, $ttl, json_encode($value));
        } else {
            $this->setFile($key, $value, $ttl);
        }
    }

    /**
     * Delete cache key
     */
    public function delete($key)
    {
        if ($this->useRedis) {
            $this->redis->del($key);
        } else {
            $this->deleteFile($key);
        }
    }

    /**
     * Check if key exists
     */
    public function has($key)
    {
        if ($this->useRedis) {
            return $this->redis->exists($key) > 0;
        } else {
            return $this->hasFile($key);
        }
    }

    /**
     * Clear all cache
     */
    public function clear()
    {
        if ($this->useRedis) {
            $this->redis->flushDB();
        } else {
            $this->clearFiles();
        }
    }

    /**
     * Remember: Get dari cache atau execute callback dan cache hasilnya
     */
    public function remember($key, $ttl, callable $callback)
    {
        // Cek apakah sudah ada di cache
        if ($this->has($key)) {
            return $this->get($key);
        }

        // Execute callback dan cache hasilnya
        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    // ===== FILE-BASED CACHE METHODS =====

    private function getFile($key, $default)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = json_decode(file_get_contents($file), true);

        // Check if expired
        if ($data && isset($data['expires']) && $data['expires'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'] ?? $default;
    }

    private function setFile($key, $value, $ttl)
    {
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        file_put_contents($file, json_encode($data));
    }

    private function deleteFile($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function hasFile($key)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode(file_get_contents($file), true);

        // Check if expired
        if ($data && isset($data['expires']) && $data['expires'] < time()) {
            unlink($file);
            return false;
        }

        return true;
    }

    private function clearFiles()
    {
        $files = glob($this->cacheDir . '/cache_*.json');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function getCacheFile($key)
    {
        return $this->cacheDir . '/cache_' . md5($key) . '.json';
    }
}
