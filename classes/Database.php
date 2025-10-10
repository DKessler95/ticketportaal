<?php
/**
 * Database Class
 * 
 * Singleton PDO wrapper for database connections and query execution
 * Provides secure database operations with prepared statements
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Load database configuration
        require_once __DIR__ . '/../config/database.php';
        
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        
        $this->connect();
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get singleton instance of Database
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish PDO connection to database
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->logError('Database Connection', 'Failed to connect to database', [
                'error' => $e->getMessage()
            ]);
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Get PDO connection object
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement|false
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Database Query', 'Query execution failed', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Fetch all rows from query result
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|false
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            if ($stmt === false) {
                return false;
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('Database FetchAll', 'Failed to fetch all rows', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Fetch single row from query result
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            if ($stmt === false) {
                return false;
            }
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError('Database FetchOne', 'Failed to fetch single row', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Execute INSERT, UPDATE, or DELETE query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return bool
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError('Database Execute', 'Failed to execute query', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get last inserted ID
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Log database errors
     * 
     * @param string $context Error context
     * @param string $message Error message
     * @param array $data Additional data
     */
    private function logError($context, $message, $data = []) {
        // Check if logError function exists (from includes/functions.php)
        if (function_exists('logError')) {
            logError($context, $message, $data);
        } else {
            // Fallback logging if helper function not available
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'context' => $context,
                'message' => $message,
                'data' => $data
            ];
            error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/../logs/database.log');
        }
    }
}
