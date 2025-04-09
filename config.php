<?php
// Database connection configuration
define('DB_SERVER', 'localhost'); // Using localhost for MariaDB
define('DB_USERNAME', 'phpuser');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'vps');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}
?>