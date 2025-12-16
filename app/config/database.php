<?php
/**
 * Bytebalok Database Configuration
 * Modern database connection with error handling and security
 */

class Database
{
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $pdo;

    public function __construct()
    {
        // Validasi: Pastikan semua konfigurasi database tersedia
        if (empty($_ENV['DB_HOST'])) {
            throw new Exception('DB_HOST tidak ditemukan. Silakan cek file config.env');
        }
        if (empty($_ENV['DB_NAME'])) {
            throw new Exception('DB_NAME tidak ditemukan. Silakan cek file config.env');
        }
        if (!isset($_ENV['DB_USER'])) {
            throw new Exception('DB_USER tidak ditemukan. Silakan cek file config.env');
        }

        $hostConfig = $_ENV['DB_HOST'];

        // Handle host with port (e.g., localhost:33066)
        if (strpos($hostConfig, ':') !== false) {
            list($this->host, $this->port) = explode(':', $hostConfig);
        } else {
            $this->host = $hostConfig;
            $this->port = '3306';
        }

        $this->db_name = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'] ?? ''; // Password boleh kosong
        $this->charset = 'utf8mb4';
    }

    public function getConnection()
    {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10
                ];

                // Add MySQL-specific init command if constant exists
                if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$this->charset}";
                }

                // Add MySQL-specific timeout if available (PHP 7.4+)
                $mysqlConnectTimeout = defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                    ? PDO::MYSQL_ATTR_CONNECT_TIMEOUT
                    : null;
                if ($mysqlConnectTimeout !== null) {
                    $options[$mysqlConnectTimeout] = 10;
                }

                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();
                $errorCode = $e->getCode();

                // Log detailed error
                error_log("Database connection failed: [{$errorCode}] {$errorMessage}");
                error_log("Connection details: host={$this->host}, port={$this->port}, dbname={$this->db_name}, user={$this->username}");

                // Provide more detailed error message when debug is enabled
                $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

                if ($isDebug) {
                    $detailedError = "Database connection failed: {$errorMessage}";
                    $detailedError .= " (Host: {$this->host}, Port: {$this->port}, Database: {$this->db_name}, User: {$this->username})";
                    throw new Exception($detailedError);
                } else {
                    throw new Exception("Database connection failed. Please check your configuration.");
                }
            }
        }

        return $this->pdo;
    }

    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit()
    {
        return $this->getConnection()->commit();
    }

    public function rollback()
    {
        return $this->getConnection()->rollback();
    }
}

// Global database instance
// Safely attempt to create a global PDO connection for legacy includes
// Do NOT throw here to avoid fatal errors when required from controllers
try {
    if (!isset($pdo) || $pdo === null) {
        $database = new Database();
        $pdo = $database->getConnection();
    }
} catch (Throwable $e) {
    // Leave $pdo unset; router or caller should handle connection and errors
    error_log("Database auto-connect failed: " . $e->getMessage());
}