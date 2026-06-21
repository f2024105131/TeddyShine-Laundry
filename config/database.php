<?php

/**
 * Database Configuration - Teddy Shine Laundry Management System
 * 
 * Handles database connection and provides helper functions
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'teddyshine');
define('DB_PORT', 3306);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base URL (Update this for your project)
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/TeddyShine-Laundry');
}

/**
 * Database Connection Class (Singleton Pattern)
 */
class Database
{
    private static $connection = null;
    private static $error = null;

    /**
     * Get database connection
     * @return mysqli|false Connection object or false on failure
     */
    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                self::$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

                if (!self::$connection) {
                    throw new Exception(mysqli_connect_error());
                }

                // Set charset to UTF-8
                mysqli_set_charset(self::$connection, "utf8mb4");

                // Set timezone
                mysqli_query(self::$connection, "SET time_zone = '+05:00'");
            } catch (Exception $e) {
                self::$error = $e->getMessage();
                error_log("Database Connection Error: " . $e->getMessage());

                // For development - show error
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    die("Database Connection Failed: " . $e->getMessage());
                }

                return false;
            }
        }
        return self::$connection;
    }

    /**
     * Close database connection
     */
    public static function closeConnection()
    {
        if (self::$connection !== null) {
            mysqli_close(self::$connection);
            self::$connection = null;
        }
    }

    /**
     * Get last error
     * @return string|null
     */
    public static function getError()
    {
        return self::$error;
    }

    /**
     * Escape string for safe SQL
     * @param string $string
     * @return string
     */
    public static function escape($string)
    {
        $conn = self::getConnection();
        if ($conn) {
            return mysqli_real_escape_string($conn, $string);
        }
        return addslashes($string);
    }

    /**
     * Execute query with error handling
     * @param string $query
     * @return mysqli_result|bool
     */
    public static function query($query)
    {
        $conn = self::getConnection();
        if (!$conn) {
            return false;
        }

        $result = mysqli_query($conn, $query);

        if ($result === false) {
            error_log("Query Error: " . mysqli_error($conn));
            error_log("Query: " . $query);
        }

        return $result;
    }

    /**
     * Get last inserted ID
     * @return int
     */
    public static function lastInsertId()
    {
        $conn = self::getConnection();
        return $conn ? mysqli_insert_id($conn) : 0;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public static function beginTransaction()
    {
        $conn = self::getConnection();
        return $conn ? mysqli_begin_transaction($conn) : false;
    }

    /**
     * Commit transaction
     * @return bool
     */
    public static function commit()
    {
        $conn = self::getConnection();
        return $conn ? mysqli_commit($conn) : false;
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public static function rollback()
    {
        $conn = self::getConnection();
        return $conn ? mysqli_rollback($conn) : false;
    }
}
// ============================================
// GLOBAL CONNECTION VARIABLE
// ============================================

// Create global $conn for functions that use it directly
global $conn;
$conn = Database::getConnection();

// Check if connection was successful
if (!$conn) {
    error_log("Database connection failed: " . Database::getError());
}
