<?php
define('DB_HOST', 'localhost'); // or your production DB host
define('DB_USER', 'root');      // or your DB username
define('DB_PASS', '');          // or your DB password
define('DB_NAME', 'dashboard_db');

// Error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    throw new Exception("Error creating table: " . $conn->error);
}

$conn->set_charset("utf8mb4");

?>