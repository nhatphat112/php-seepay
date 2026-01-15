<?php
/**
 * Silkroad Huyền Thoại - Database Configuration
 * Cấu hình database duy nhất và bảo mật
 */

class DatabaseConfig {
    // Database Connection Settings
    const SERVER_NAME = "103.2.227.134,49669";
    const SERVER_USER = "sa";  
    const SERVER_PASS = "251292Son";  
    // Database Names
    const DB_ACCOUNT = "SRO_VT_ACCOUNT";
    const DB_LOG = "SRO_VT_LOG";
    const DB_SHARD = "SRO_VT_SHARD";
    
    // Silk Conversion Rate
    // Tỉ lệ chuyển đổi: 100,000 VNĐ = 2,000 Silk (1 VNĐ = 0.02 Silk)
    const SILK_RATE = 0.02; // 1 VNĐ = 0.02 Silk
    
    // Security Settings
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_TIMEOUT = 900; // 15 minutes
    const SESSION_TIMEOUT = 3600; // 1 hour
    
    private static $connections = [];
    
    /**
     * Create connection - Simplified method from web_tet (WORKING VERSION)
     */
    private static function createConnection($servername, $username, $password, $dbname) {
        try {
            // Use exact format that works in web_tet
            $dsn = "sqlsrv:server=$servername;database=$dbname";
            $conn = new PDO($dsn, $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            error_log("Connection failed to $dbname: " . $e->getMessage());
            throw new Exception("Connection failed to $dbname: " . $e->getMessage());
        }
    }
    
    /**
     * Get database connection with security
     */
    public static function getConnection($database) {
        // Security check
        if (!self::isRequestSecure()) {
            throw new Exception("Insecure request detected");
        }
        
        if (!isset(self::$connections[$database])) {
            self::$connections[$database] = self::createConnection(
                self::SERVER_NAME,
                self::SERVER_USER,
                self::SERVER_PASS,
                $database
            );
        }
        
        return self::$connections[$database];
    }
    
    /**
     * Security check
     */
    private static function isRequestSecure() {
        // Skip security check for API requests (GET method from /api/ directory)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/api/') !== false && $_SERVER['REQUEST_METHOD'] === 'GET') {
            return true; // Allow API GET requests
        }
        
        // Skip security check if no referer (direct access or API call)
        if (!isset($_SERVER['HTTP_REFERER'])) {
            // Allow if it's a GET request or API call, or CLI
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
            if ($requestMethod === 'GET' || $requestMethod === 'CLI') {
                return true;
            }
        }
        
        // Check if request is from same domain
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            $current = $_SERVER['HTTP_HOST'];
            if ($referer !== $current && $referer !== null) {
                // Allow localhost variations
                $allowedHosts = ['localhost', '127.0.0.1', '::1'];
                if (!in_array($referer, $allowedHosts) && !in_array($current, $allowedHosts)) {
                    return false;
                }
            }
        }
        
        // Check if request is POST and has CSRF token
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        if ($requestMethod === 'POST') {
            if (!isset($_POST['csrf_token']) || !self::validateCSRFToken($_POST['csrf_token'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    private static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get Account Database Connection
     */
    public static function getAccountDB() {
        return self::getConnection(self::DB_ACCOUNT);
    }
    
    /**
     * Get Log Database Connection
     */
    public static function getLogDB() {
        return self::getConnection(self::DB_LOG);
    }
    
    /**
     * Get Shard Database Connection
     */
    public static function getShardDB() {
        return self::getConnection(self::DB_SHARD);
    }
    
    /**
     * Close all connections
     */
    public static function closeAllConnections() {
        self::$connections = [];
    }
}
?>