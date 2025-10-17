<?php
// connection.php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "1garage";  // ✅ Your current database

// Create connection (OOP style with MySQLi)
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
