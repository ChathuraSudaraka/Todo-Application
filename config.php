<?php
// Database connection configuration
// Detect environment and set appropriate connection details
$docker_db = getenv('DOCKER_ENV');

if ($docker_db) {
    // Docker environment - use the service name as hostname
    define('DB_SERVER', 'db');
    define('DB_PORT', 3306);
} else {
    // Check if we're on a production server or local development
    if (
        strpos($_SERVER['SERVER_ADDR'] ?? '', '192.168.') === 0 ||
        strpos($_SERVER['SERVER_ADDR'] ?? '', '127.0.0.1') === 0 ||
        strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    ) {
        // Local development outside Docker - use 127.0.0.1 instead of localhost
        define('DB_SERVER', '127.0.0.1');
    } else {
        // Production server
        define('DB_SERVER', 'localhost');
    }
    define('DB_PORT', 3306);
}

define('DB_USERNAME', 'phpuser');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'vps');

// Attempt to connect to MySQL database (with port specified)
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

// Check connection
if ($conn === false) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    echo "<br>Connection attempted to: " . DB_SERVER . ":" . DB_PORT;
    echo "<br>Environment: " . ($docker_db ? "Docker" : "Non-Docker");
    echo "<br>Server address: " . ($_SERVER['SERVER_ADDR'] ?? 'unknown');
    die("<br>ERROR: Could not connect to database. " . mysqli_connect_error());
}
?>