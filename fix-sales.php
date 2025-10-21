<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

echo "<h2>Sales Table Fix</h2>";

// Check if sales table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'sales'");
if ($checkTable->num_rows == 0) {
    echo "<p>❌ Sales table doesn't exist. Creating it...</p>";
    
    // Create sales table
    $createTable = "CREATE TABLE sales (
        saleID INT AUTO_INCREMENT PRIMARY KEY,
        clientID INT NOT NULL,
        productID INT NOT NULL,
        userID INT NOT NULL,
        quantity INT NOT NULL,
        unitPrice DECIMAL(10,2) NOT NULL,
        totalAmount DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unitPrice) STORED,
        saleDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable)) {
        echo "<p>✅ Sales table created successfully!</p>";
    } else {
        echo "<p>❌ Error creating sales table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ Sales table exists</p>";
    
    // Check if saleID is auto-increment
    $checkStructure = $conn->query("SHOW COLUMNS FROM sales WHERE Field = 'saleID'");
    $column = $checkStructure->fetch_assoc();
    
    if (strpos($column['Extra'], 'auto_increment') === false) {
        echo "<p>⚠️ Fixing saleID column to be auto-increment...</p>";
        $conn->query("ALTER TABLE sales MODIFY saleID INT AUTO_INCREMENT PRIMARY KEY");
        echo "<p>✅ saleID column fixed</p>";
    }
}

// Check current sales count
$salesCount = $conn->query("SELECT COUNT(*) as count FROM sales");
$count = $salesCount->fetch_assoc()['count'];
echo "<p>Current sales count: " . $count . "</p>";

// If no sales exist, create some sample data
if ($count == 0) {
    echo "<p>Creating sample sales data...</p>";
    
    // Get first client and product
    $clientResult = $conn->query("SELECT clientID FROM clientinfo LIMIT 1");
    $productResult = $conn->query("SELECT productID FROM products LIMIT 1");
    
    if ($clientResult->num_rows > 0 && $productResult->num_rows > 0) {
        $client = $clientResult->fetch_assoc();
        $product = $productResult->fetch_assoc();
        
        // Insert sample sales (without totalAmount since it's a generated column)
        $sampleSales = [
            [1, 1, 2, 2, 1200.00], // clientID=1, productID=1, userID=2, qty=2, price=1200
            [1, 1, 1, 1, 1200.00], // clientID=1, productID=1, userID=2, qty=1, price=1200
        ];
        
        foreach ($sampleSales as $sale) {
            $stmt = $conn->prepare("INSERT INTO sales (clientID, productID, userID, quantity, unitPrice) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiid", $sale[0], $sale[1], $sale[2], $sale[3], $sale[4]);
            if ($stmt->execute()) {
                echo "<p>✅ Sample sale added</p>";
            } else {
                echo "<p>❌ Error adding sample sale: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    } else {
        echo "<p>❌ No clients or products found to create sample sales</p>";
    }
}

// Show current sales
echo "<h3>Current Sales:</h3>";
$salesResult = $conn->query("SELECT s.*, c.clientName, p.productName FROM sales s 
                           LEFT JOIN clientinfo c ON s.clientID = c.clientID 
                           LEFT JOIN products p ON s.productID = p.productID 
                           ORDER BY s.saleID DESC");

if ($salesResult && $salesResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Sale ID</th><th>Client</th><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th><th>Date</th></tr>";
    
    while($row = $salesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['saleID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['clientName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['productName']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>₱" . number_format($row['unitPrice'], 2) . "</td>";
        echo "<td>₱" . number_format($row['totalAmount'], 2) . "</td>";
        echo "<td>" . $row['saleDate'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sales found</p>";
}

echo "<p><a href='sales-admin.php'>→ Go to Sales Admin</a></p>";
?>
