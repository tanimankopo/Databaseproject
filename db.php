<?php
// Database configuration
$servername = "localhost";   // usually localhost in XAMPP
$username   = "root";        // default user for XAMPP
$password   = "";            // default password is blank
$dbname     = "1garage"; // change to your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Optional: set character set to UTF-8
$conn->set_charset("utf8");

// Uncomment this line for debugging (shows success message)
// echo "✅ Connected successfully";
?>
