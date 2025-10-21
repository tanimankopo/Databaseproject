<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Starting debug-sales.php -->";

session_start();

echo "<!-- Debug: Session started -->";
echo "<!-- Debug: Session data: " . print_r($_SESSION, true) . " -->";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "<!-- Debug: User not logged in, redirecting to login -->";
    header("Location: login.php");
    exit();
}

echo "<!-- Debug: User is logged in -->";

require 'db.php';

echo "<!-- Debug: Database connected -->";

// Simple test query
$testQuery = $conn->query("SELECT COUNT(*) as count FROM sales");
if ($testQuery) {
    $result = $testQuery->fetch_assoc();
    echo "<!-- Debug: Sales count: " . $result['count'] . " -->";
} else {
    echo "<!-- Debug: Error with sales query: " . $conn->error . " -->";
}

echo "<!-- Debug: About to show HTML -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debug Sales Page</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #1a1d29; 
            color: white; 
        }
        .debug { 
            background: #2d303e; 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h1>Debug Sales Page</h1>
    
    <div class="debug">
        <h3>Session Information:</h3>
        <p>Username: <?= isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set' ?></p>
        <p>User ID: <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set' ?></p>
        <p>Role: <?= isset($_SESSION['role']) ? $_SESSION['role'] : 'Not set' ?></p>
    </div>
    
    <div class="debug">
        <h3>Database Test:</h3>
        <?php
        $salesQuery = $conn->query("SELECT * FROM sales ORDER BY saleID DESC LIMIT 5");
        if ($salesQuery && $salesQuery->num_rows > 0) {
            echo "<p>✅ Sales table accessible</p>";
            echo "<p>Recent sales:</p><ul>";
            while($row = $salesQuery->fetch_assoc()) {
                echo "<li>Sale ID: " . $row['saleID'] . " - Amount: ₱" . number_format($row['totalAmount'], 2) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ No sales found or error: " . $conn->error . "</p>";
        }
        ?>
    </div>
    
    <div class="debug">
        <h3>Client Test:</h3>
        <?php
        $clientQuery = $conn->query("SELECT * FROM clientinfo ORDER BY clientID DESC LIMIT 5");
        if ($clientQuery && $clientQuery->num_rows > 0) {
            echo "<p>✅ Clients table accessible</p>";
            echo "<p>Recent clients:</p><ul>";
            while($row = $clientQuery->fetch_assoc()) {
                echo "<li>Client ID: " . $row['clientID'] . " - Name: " . htmlspecialchars($row['clientName']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ No clients found or error: " . $conn->error . "</p>";
        }
        ?>
    </div>
    
    <div class="debug">
        <h3>Products Test:</h3>
        <?php
        $productQuery = $conn->query("SELECT * FROM products ORDER BY productID DESC LIMIT 5");
        if ($productQuery && $productQuery->num_rows > 0) {
            echo "<p>✅ Products table accessible</p>";
            echo "<p>Recent products:</p><ul>";
            while($row = $productQuery->fetch_assoc()) {
                echo "<li>Product ID: " . $row['productID'] . " - Name: " . htmlspecialchars($row['productName']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ No products found or error: " . $conn->error . "</p>";
        }
        ?>
    </div>
    
    <p><a href="login.php" style="color: #5c9eff;">← Go to Login</a></p>
    <p><a href="sales-admin.php" style="color: #5c9eff;">→ Go to Sales Admin</a></p>
</body>
</html>
