<?php
/**
 * Debug Helper - Simple debugging tool
 * Hanya aktif ketika APP_DEBUG = true
 */

class Debug {
    /**
     * Log pesan debug (hanya di development)
     */
    public static function log($message, $data = null) {
        // Cek apakah debug mode aktif
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        
        if (!$isDebug) {
            return; // Jangan log jika production
        }
        
        // Format pesan
        $logMessage = "[DEBUG] " . $message;
        
        // Tambahkan data jika ada
        if ($data !== null) {
            $logMessage .= " | Data: " . json_encode($data);
        }
        
        // Tulis ke error log
        error_log($logMessage);
    }
    
    /**
     * Log error (selalu aktif, bahkan di production)
     */
    public static function error($message, $exception = null) {
        $logMessage = "[ERROR] " . $message;
        
        if ($exception instanceof Exception) {
            $logMessage .= " | " . $exception->getMessage();
            $logMessage .= " | File: " . $exception->getFile();
            $logMessage .= " | Line: " . $exception->getLine();
        }
        
        error_log($logMessage);
    }
    
    /**
     * Cek apakah debug mode aktif
     */
    public static function isEnabled() {
        return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    }
}
