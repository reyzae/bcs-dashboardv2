<?php

/**
 * Logging System for Audit Trail
 * Tracks all important actions in the system
 */
class Logger {
    private $pdo;
    private $logFile;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->logFile = __DIR__ . '/../../storage/logs/app.log';
        
        // Create logs directory if not exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Log to database (audit trail)
     */
    public function audit($action, $entity, $entityId, $details = null, $userId = null) {
        if (!$this->pdo) return false;
        
        try {
            // Get user ID from session if not provided
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs 
                (user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $detailsJson = $details ? json_encode($details) : null;
            
            return $stmt->execute([
                $userId,
                $action,
                $entity,
                $entityId,
                $detailsJson,
                $ipAddress,
                $userAgent
            ]);
        } catch (PDOException $e) {
            $this->error("Failed to log audit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log info message to file
     */
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * Log warning message to file
     */
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    /**
     * Log error message to file
     */
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    /**
     * Log debug message to file
     */
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    /**
     * Write log to file
     */
    private function log($level, $message, $context = []) {
        try {
            // Rotate log file if oversized (5MB)
            $maxSize = 5 * 1024 * 1024;
            if (file_exists($this->logFile) && filesize($this->logFile) >= $maxSize) {
                $timestamp = date('Ymd_His');
                $rotated = $this->logFile . '.' . $timestamp;
                @rename($this->logFile, $rotated);
                // Keep only last 3 rotated files
                $pattern = $this->logFile . '.*';
                $files = glob($pattern);
                if ($files && count($files) > 3) {
                    // Sort by modification time ascending and delete oldest
                    usort($files, function($a, $b) { return filemtime($a) <=> filemtime($b); });
                    foreach (array_slice($files, 0, count($files) - 3) as $old) {
                        @unlink($old);
                    }
                }
            }
            $timestamp = date('Y-m-d H:i:s');
            $userId = $_SESSION['user_id'] ?? 'guest';
            $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
            
            $logMessage = "[$timestamp] [$level] [User: $userId] $message$contextStr" . PHP_EOL;
            
            @file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Silently fail - don't let logging errors break the application
            error_log('Failed to write to log file: ' . $e->getMessage());
        }
    }
    
    /**
     * Log API request
     */
    public function apiRequest($endpoint, $method, $data = []) {
        $message = "API Request: $method $endpoint";
        $context = [
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        $this->info($message, $context);
    }
    
    /**
     * Log API response
     */
    public function apiResponse($endpoint, $statusCode, $success) {
        $message = "API Response: $endpoint - Status: $statusCode";
        $context = ['success' => $success];
        $this->info($message, $context);
    }
    
    /**
     * Get recent logs from file
     */
    public function getRecentLogs($lines = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $file = new SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);
        
        $logs = [];
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logs[] = $line;
            }
            $file->next();
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Clear old logs (older than X days)
     */
    public function clearOldLogs($days = 30) {
        if (!file_exists($this->logFile)) {
            return true;
        }
        
        $cutoffDate = date('Y-m-d', strtotime("-$days days"));
        $tempFile = $this->logFile . '.tmp';
        
        $oldFile = fopen($this->logFile, 'r');
        $newFile = fopen($tempFile, 'w');
        
        while (($line = fgets($oldFile)) !== false) {
            // Extract date from log line [YYYY-MM-DD HH:II:SS]
            if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                if ($matches[1] >= $cutoffDate) {
                    fwrite($newFile, $line);
                }
            }
        }
        
        fclose($oldFile);
        fclose($newFile);
        
        return rename($tempFile, $this->logFile);
    }
}

