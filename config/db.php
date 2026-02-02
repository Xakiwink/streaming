<?php
/**
 * Database configuration
 * Credentials and connection helper
 */

// ---------------------------------------------------------------------------
// Credentials
// ---------------------------------------------------------------------------

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '598Luja@');
define('DB_NAME', 'streaming_platform');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------------
// Connection
// ---------------------------------------------------------------------------

function get_db_connection() {
    static $conn = null;

    if ($conn === null) {
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            error_log('Database connection failed: ' . mysqli_connect_error());
            return false;
        }
        if (!mysqli_set_charset($conn, DB_CHARSET)) {
            error_log('Failed to set charset: ' . mysqli_error($conn));
        }
    }

    return $conn;
}

function close_db_connection() {
    $conn = get_db_connection();
    if ($conn) mysqli_close($conn);
}
