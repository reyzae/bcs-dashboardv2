<?php
/**
 * Rate Limiter - Simple rate limiting dengan fallback
 * Gunakan Redis jika tersedia, fallback ke file system
 */

class RateLimiter
{
    private $redis = null;
    private $useRedis = false;

    public function __construct()
    {
        // Cek apakah Redis extension tersedia
        if (extension_loaded('redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->useRedis = true;
                Debug::log('RateLimiter using Redis');
            } catch (Exception $e) {
                Debug::log('Redis not available, using file fallback');
                $this->useRedis = false;
            }
        }
    }

    /**
     * Check rate limit untuk IP address
     * 
     * @param string $ip IP address
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window dalam detik
     * @return bool True jika allowed, False jika exceeded
     */
    public function checkLimit($ip, $maxRequests = 60, $timeWindow = 60)
    {
        if ($this->useRedis) {
            return $this->checkLimitRedis($ip, $maxRequests, $timeWindow);
        } else {
            return $this->checkLimitFile($ip, $maxRequests, $timeWindow);
        }
    }

    /**
     * Check rate limit menggunakan Redis (FAST)
     */
    private function checkLimitRedis($ip, $maxRequests, $timeWindow)
    {
        $key = "rate_limit:{$ip}";

        // Increment counter
        $current = $this->redis->incr($key);

        // Set expiry pada first request
        if ($current === 1) {
            $this->redis->expire($key, $timeWindow);
        }

        // Check if limit exceeded
        return $current <= $maxRequests;
    }

    /**
     * Check rate limit menggunakan file system (FALLBACK)
     */
    private function checkLimitFile($ip, $maxRequests, $timeWindow)
    {
        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/rate_limit_' . md5($ip) . '.json';
        $now = time();
        $requests = [];

        // Load existing requests
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data) {
                $requests = $data['requests'] ?? [];
            }
        }

        // Remove old requests outside time window
        $requests = array_filter($requests, function ($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });

        // Check if limit exceeded
        if (count($requests) >= $maxRequests) {
            return false; // Limit exceeded
        }

        // Add current request
        $requests[] = $now;

        // Save to cache
        file_put_contents($cacheFile, json_encode(['requests' => $requests]));

        return true; // Allowed
    }

    /**
     * Get remaining requests
     */
    public function getRemaining($ip, $maxRequests = 60, $timeWindow = 60)
    {
        if ($this->useRedis) {
            $key = "rate_limit:{$ip}";
            $current = (int) $this->redis->get($key);
            return max(0, $maxRequests - $current);
        } else {
            // File-based: calculate from stored requests
            $cacheFile = __DIR__ . '/../../storage/cache/rate_limit_' . md5($ip) . '.json';
            if (!file_exists($cacheFile)) {
                return $maxRequests;
            }

            $data = json_decode(file_get_contents($cacheFile), true);
            $requests = $data['requests'] ?? [];
            $now = time();

            // Count valid requests
            $validRequests = array_filter($requests, function ($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            });

            return max(0, $maxRequests - count($validRequests));
        }
    }
}
