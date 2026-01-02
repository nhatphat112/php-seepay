<?php
/**
 * Silkroad Huyền Thoại - Connection Manager
 * Quản lý kết nối database và khởi tạo tự động
 */

class ConnectionManager {
    private static $instance = null;
    private static $connections = [];
    private static $connectionStatus = [];
    private static $initTime = null;
    private static $lastHealthCheck = 0;
    private static $healthCheckInterval = 30; // 30 seconds
    
    // Prevent direct instantiation
    private function __construct() {}
    
    /**
     * Singleton pattern - get instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::initialize();
        }
        return self::$instance;
    }
    
    /**
     * Khởi tạo tất cả kết nối khi web startup
     */
    public static function initialize() {
        if (self::$initTime !== null) {
            return; // Already initialized
        }
        
        self::$initTime = time();
        error_log("ConnectionManager: Initializing database connections...");
        
        // Load database config
        require_once __DIR__ . '/database.php';
        
        // Initialize all database connections
        $databases = [
            'account' => DatabaseConfig::DB_ACCOUNT,
            'log' => DatabaseConfig::DB_LOG,
            'shard' => DatabaseConfig::DB_SHARD
        ];
        
        // Log available PDO drivers before attempting connections
        $availableDrivers = PDO::getAvailableDrivers();
        error_log("ConnectionManager: Available PDO drivers: " . implode(', ', $availableDrivers));
        error_log("ConnectionManager: Attempting to connect to " . count($databases) . " databases");
        
        foreach ($databases as $key => $dbName) {
            error_log("ConnectionManager: Attempting to connect to $key database ($dbName)...");
            try {
                self::createConnection($key, $dbName);
                self::$connectionStatus[$key] = [
                    'status' => 'connected',
                    'last_connect' => time(),
                    'connect_count' => 1,
                    'error_count' => 0,
                    'last_error' => null
                ];
                error_log("ConnectionManager: ✅ Successfully connected to $key database ($dbName)");
            } catch (Exception $e) {
                self::$connectionStatus[$key] = [
                    'status' => 'failed',
                    'last_connect' => null,
                    'connect_count' => 0,
                    'error_count' => 1,
                    'last_error' => $e->getMessage()
                ];
                error_log("ConnectionManager: ❌ Failed to connect to $key database ($dbName): " . $e->getMessage());
                error_log("ConnectionManager: Error details - Type: $key, Database: $dbName, Error: " . $e->getMessage());
            }
        }
        
        // Log summary
        $successCount = 0;
        $failedCount = 0;
        foreach (self::$connectionStatus as $key => $status) {
            if ($status['status'] === 'connected') {
                $successCount++;
            } else {
                $failedCount++;
            }
        }
        error_log("ConnectionManager: Initialization summary - Success: $successCount, Failed: $failedCount");
        
        // Register shutdown function to clean up connections
        register_shutdown_function([__CLASS__, 'cleanup']);
        
        error_log("ConnectionManager: Initialization complete at " . date('Y-m-d H:i:s'));
    }
    
    /**
     * Tạo kết nối đến database cụ thể - Simplified method from web_tet (WORKING VERSION)
     */
    private static function createConnection($key, $database) {
        try {
            // Log connection attempt details
            error_log("ConnectionManager::createConnection() - Key: $key, Database: $database");
            error_log("  - Server: " . DatabaseConfig::SERVER_NAME);
            error_log("  - User: " . DatabaseConfig::SERVER_USER);
            
            $availableDrivers = PDO::getAvailableDrivers();
            error_log("  - Available PDO drivers: " . implode(', ', $availableDrivers));
            
            $dsn = null;
            $driverUsed = null;
            
            // Try drivers in order of preference
            // 1. sqlsrv (preferred for Windows/SQL Server)
            if (in_array('sqlsrv', $availableDrivers)) {
                $dsn = "sqlsrv:server=" . DatabaseConfig::SERVER_NAME . ";database=" . $database;
                $driverUsed = 'sqlsrv';
            }
            // 2. dblib (for FreeTDS on Linux/macOS)
            elseif (in_array('dblib', $availableDrivers)) {
                // Convert server format: "localhost,1433" -> "localhost:1433"
                $server = str_replace(',', ':', DatabaseConfig::SERVER_NAME);
                $dsn = "dblib:host=$server;dbname=$database";
                $driverUsed = 'dblib';
            }
            // 3. odbc (fallback, requires ODBC driver configuration)
            elseif (in_array('odbc', $availableDrivers)) {
                // Try ODBC with SQL Server driver
                $server = DatabaseConfig::SERVER_NAME;
                // Remove port if present for ODBC
                $serverParts = explode(',', $server);
                $host = $serverParts[0];
                $port = isset($serverParts[1]) ? $serverParts[1] : '1433';
                
                // Try different ODBC driver names
                $odbcDrivers = [
                    'ODBC Driver 17 for SQL Server',
                    'ODBC Driver 18 for SQL Server',
                    'ODBC Driver 13 for SQL Server',
                    'FreeTDS'
                ];
                
                $odbcConnected = false;
                foreach ($odbcDrivers as $odbcDriver) {
                    try {
                        $dsn = "odbc:Driver={$odbcDriver};Server=$host,$port;Database=$database;";
                        error_log("  - Trying ODBC driver: $odbcDriver");
                        $testConn = new PDO($dsn, DatabaseConfig::SERVER_USER, DatabaseConfig::SERVER_PASS);
                        $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $testConn->query("SELECT 1");
                        $driverUsed = "odbc ($odbcDriver)";
                        $odbcConnected = true;
                        break;
                    } catch (Exception $e) {
                        error_log("  - ODBC driver $odbcDriver failed: " . $e->getMessage());
                        continue;
                    }
                }
                
                if (!$odbcConnected) {
                    throw new Exception("No working ODBC driver found. Tried: " . implode(', ', $odbcDrivers));
                }
            }
            else {
                throw new Exception("No suitable PDO driver found for SQL Server. Available drivers: " . implode(', ', $availableDrivers));
            }
            
            error_log("  - Using driver: $driverUsed");
            error_log("  - DSN: $dsn");
            
            $conn = new PDO($dsn, DatabaseConfig::SERVER_USER, DatabaseConfig::SERVER_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Test the connection
            error_log("  - Testing connection...");
            $stmt = $conn->query("SELECT 1");
            if (!$stmt) {
                throw new Exception("Connection test failed for database: $database");
            }
            
            self::$connections[$key] = $conn;
            error_log("ConnectionManager: ✅ Connection created successfully for $key ($database) using $driverUsed");
            return $conn;
        } catch (PDOException $e) {
            $errorDetails = [
                'key' => $key,
                'database' => $database,
                'server' => DatabaseConfig::SERVER_NAME,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'available_drivers' => PDO::getAvailableDrivers()
            ];
            error_log("ConnectionManager: ❌ PDOException for $key ($database): " . json_encode($errorDetails, JSON_PRETTY_PRINT));
            throw new Exception("Connection failed to $database: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("ConnectionManager: ❌ Exception for $key ($database): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Lấy kết nối database
     */
    public static function getConnection($type = 'account') {
        // Ensure manager is initialized
        self::getInstance();
        
        // Health check if needed
        self::performHealthCheck();
        
        // Debug: Log current state
        error_log("ConnectionManager::getConnection('$type') called");
        error_log("  - Available connections: " . implode(', ', array_keys(self::$connections)));
        error_log("  - Connection status for '$type': " . (isset(self::$connectionStatus[$type]) ? json_encode(self::$connectionStatus[$type]) : 'not set'));
        
        if (!isset(self::$connections[$type])) {
            // Detailed error logging
            $errorDetails = [
                'type' => $type,
                'available_connections' => array_keys(self::$connections),
                'connection_status' => self::$connectionStatus[$type] ?? 'not initialized',
                'available_pdo_drivers' => PDO::getAvailableDrivers(),
                'initialized_at' => self::$initTime ? date('Y-m-d H:i:s', self::$initTime) : 'not initialized'
            ];
            error_log("ConnectionManager: Connection '$type' not available. Details: " . json_encode($errorDetails, JSON_PRETTY_PRINT));
            
            // Try to reconnect if status shows it was attempted
            if (isset(self::$connectionStatus[$type]) && self::$connectionStatus[$type]['status'] === 'failed') {
                error_log("ConnectionManager: Attempting to reconnect '$type' due to previous failure...");
                try {
                    return self::reconnect($type);
                } catch (Exception $e) {
                    error_log("ConnectionManager: Reconnection failed: " . $e->getMessage());
                    throw new Exception("Database connection '$type' not available. Reason: " . $e->getMessage() . ". Available drivers: " . implode(', ', PDO::getAvailableDrivers()));
                }
            }
            
            throw new Exception("Database connection '$type' not available. Available connections: " . implode(', ', array_keys(self::$connections)) . ". Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()));
        }
        
        // Test connection before returning
        try {
            $conn = self::$connections[$type];
            $stmt = $conn->query("SELECT 1");
            if (!$stmt) {
                throw new Exception("Connection test failed");
            }
            error_log("ConnectionManager: Successfully retrieved connection '$type'");
            return $conn;
        } catch (Exception $e) {
            // Connection is dead, try to reconnect
            error_log("ConnectionManager: Connection '$type' is dead, attempting reconnection... Error: " . $e->getMessage());
            try {
                return self::reconnect($type);
            } catch (Exception $reconnectError) {
                error_log("ConnectionManager: Reconnection failed: " . $reconnectError->getMessage());
                throw new Exception("Database connection '$type' not available. Reconnection failed: " . $reconnectError->getMessage());
            }
        }
    }
    
    /**
     * Kết nối lại khi connection bị đứt
     */
    private static function reconnect($type) {
        $databases = [
            'account' => DatabaseConfig::DB_ACCOUNT,
            'log' => DatabaseConfig::DB_LOG,
            'shard' => DatabaseConfig::DB_SHARD
        ];
        
        if (!isset($databases[$type])) {
            throw new Exception("Unknown database type: $type");
        }
        
        try {
            self::createConnection($type, $databases[$type]);
            
            // Update status
            self::$connectionStatus[$type]['status'] = 'connected';
            self::$connectionStatus[$type]['last_connect'] = time();
            self::$connectionStatus[$type]['connect_count']++;
            self::$connectionStatus[$type]['last_error'] = null;
            
            error_log("ConnectionManager: Successfully reconnected to $type database");
            return self::$connections[$type];
            
        } catch (Exception $e) {
            // Update error status
            self::$connectionStatus[$type]['status'] = 'failed';
            self::$connectionStatus[$type]['error_count']++;
            self::$connectionStatus[$type]['last_error'] = $e->getMessage();
            
            error_log("ConnectionManager: Failed to reconnect to $type database: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Kiểm tra sức khỏe kết nối định kỳ
     */
    private static function performHealthCheck() {
        $now = time();
        if ($now - self::$lastHealthCheck < self::$healthCheckInterval) {
            return; // Skip health check
        }
        
        self::$lastHealthCheck = $now;
        
        foreach (self::$connections as $type => $connection) {
            try {
                $stmt = $connection->query("SELECT 1");
                if ($stmt) {
                    self::$connectionStatus[$type]['status'] = 'healthy';
                }
            } catch (Exception $e) {
                self::$connectionStatus[$type]['status'] = 'unhealthy';
                self::$connectionStatus[$type]['last_error'] = $e->getMessage();
                error_log("ConnectionManager: Health check failed for $type: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Lấy trạng thái tất cả kết nối
     */
    public static function getConnectionStatus() {
        self::getInstance();
        self::performHealthCheck();
        
        return [
            'initialized_at' => self::$initTime ? date('Y-m-d H:i:s', self::$initTime) : null,
            'uptime_seconds' => self::$initTime ? (time() - self::$initTime) : 0,
            'connections' => self::$connectionStatus,
            'last_health_check' => date('Y-m-d H:i:s', self::$lastHealthCheck),
            'server_info' => [
                'server' => DatabaseConfig::SERVER_NAME,
                'user' => DatabaseConfig::SERVER_USER
            ]
        ];
    }
    
    /**
     * Force kết nối lại tất cả database
     */
    public static function reconnectAll() {
        error_log("ConnectionManager: Force reconnecting all databases...");
        
        $databases = [
            'account' => DatabaseConfig::DB_ACCOUNT,
            'log' => DatabaseConfig::DB_LOG,
            'shard' => DatabaseConfig::DB_SHARD
        ];
        
        $results = [];
        foreach ($databases as $type => $dbName) {
            try {
                self::createConnection($type, $dbName);
                self::$connectionStatus[$type]['status'] = 'connected';
                self::$connectionStatus[$type]['last_connect'] = time();
                self::$connectionStatus[$type]['connect_count']++;
                $results[$type] = 'success';
            } catch (Exception $e) {
                self::$connectionStatus[$type]['status'] = 'failed';
                self::$connectionStatus[$type]['error_count']++;
                self::$connectionStatus[$type]['last_error'] = $e->getMessage();
                $results[$type] = 'failed: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Cleanup khi web shutdown
     */
    public static function cleanup() {
        if (self::$connections) {
            error_log("ConnectionManager: Cleaning up " . count(self::$connections) . " database connections...");
            self::$connections = [];
        }
    }
    
    /**
     * Convenience methods for specific databases
     */
    public static function getAccountDB() {
        return self::getConnection('account');
    }
    
    public static function getLogDB() {
        return self::getConnection('log');
    }
    
    public static function getShardDB() {
        return self::getConnection('shard');
    }
    
    /**
     * Test tất cả kết nối
     */
    public static function testAllConnections() {
        $results = [];
        $databases = ['account', 'log', 'shard'];
        
        foreach ($databases as $type) {
            try {
                $conn = self::getConnection($type);
                $stmt = $conn->query("SELECT @@VERSION as version, GETDATE() as [server_time]");
                $result = $stmt->fetch();
                
                $results[$type] = [
                    'status' => 'success',
                    'version' => $result['version'],
                    'server_time' => $result['server_time'],
                    'response_time' => microtime(true)
                ];
            } catch (Exception $e) {
                $results[$type] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'response_time' => null
                ];
            }
        }
        
        return $results;
    }
}

// Auto-initialize when this file is included
ConnectionManager::getInstance();
?>
