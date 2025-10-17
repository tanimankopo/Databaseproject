<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';



// ✅ Insert Client + Sale
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_client_sale'])) {
    // --- Client Info ---
    $clientName    = $_POST['clientName'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];

    // Insert Client
    $stmt = $conn->prepare("INSERT INTO clientinfo (clientName, contactNumber, email, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $clientName, $contactNumber, $email, $address);
    $stmt->execute();
    $clientID = $stmt->insert_id; // ✅ Get new clientID
    $stmt->close();

    // --- Sales Info ---
    $productID = intval($_POST['productID']);
    $userID    = intval($_POST['userID']);
    $quantity  = intval($_POST['quantity']);
    $unitPrice = floatval($_POST['unitPrice']);
    $totalAmount = $quantity * $unitPrice;

    // Insert Sale linked to client
    $stmt = $conn->prepare("INSERT INTO sales (clientID, productID, userID, quantity, unitPrice, totalAmount, saleDate) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiiidd", $clientID, $productID, $userID, $quantity, $unitPrice, $totalAmount);
    $stmt->execute();
    $stmt->close();

    header("Location: sales.php");
    exit();
}

// ✅ Fetch Sales
$salesResult = $conn->query("SELECT * FROM sales ORDER BY saleID ASC");




// ✅ Insert Sale (auto total & date)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_sale'])) {
    $clientID  = intval($_POST['clientID']);
    $productID = intval($_POST['productID']);
    $userID    = intval($_POST['userID']);
    $quantity  = intval($_POST['quantity']);
    $unitPrice = floatval($_POST['unitPrice']);

    // calculate total automatically
    $totalAmount = $quantity * $unitPrice;

    $stmt = $conn->prepare("INSERT INTO sales (clientID, productID, userID, quantity, unitPrice, totalAmount, saleDate) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiiidd", $clientID, $productID, $userID, $quantity, $unitPrice, $totalAmount);
    $stmt->execute();
    $stmt->close();
}

// ✅ Fetch Sales
$salesResult = $conn->query("SELECT * FROM sales ORDER BY saleID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <link rel="stylesheet" type="text/css" href="Sales.css">
</head>
<body>

    <?php
            include("sidebar.php")
    ?>
    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <h1>💰 Sales</h1>
            <div class="settings-menu">
                <button class="settings-btn">&#9776;</button>
                <div class="settings-dropdown">
                    <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add </button>
                    
                </div>
            </div>
        </header>

        

        <!-- ✅ Sales Table -->
        <table class="sales-table">
            <tr>
                <th>Sale ID</th>
                <th>Client ID</th>
                <th>Product ID</th>
                <th>User ID</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Amount</th>
                <th>Sale Date</th>
            </tr>
            <?php while($row = $salesResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['saleID'] ?></td>
                <td><?= $row['clientID'] ?></td>
                <td><?= $row['productID'] ?></td>
                <td><?= $row['userID'] ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['unitPrice'] ?></td>
                <td><?= $row['totalAmount'] ?></td>
                <td><?= $row['saleDate'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

    <!-- Add Client + Sales Modal -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3>Add New Client & Sale</h3>
        <form method="POST" action="sales.php">
            <!-- ✅ Client Section -->
            <h4>👥 Client Information</h4>
            <label>Client Name:</label>
            <input type="text" name="clientName" required><br>

            <label>Contact Number:</label>
            <input type="text" name="contactNumber"><br>

            <label>Email:</label>
            <input type="email" name="email"><br>

            <label>Address:</label>
            <input type="text" name="address"><br><br>

            <!-- ✅ Sales Section -->
            <h4>💰 Sales Information</h4>
            <label>Product ID:</label>
            <input type="number" name="productID" required><br>

            <label>User ID:</label>
            <input type="number" name="userID" required><br>

            <label>Quantity:</label>
            <input type="number" name="quantity" id="quantity" required><br>

            <label>Unit Price:</label>
            <input type="number" step="0.01" name="unitPrice" id="unitPrice" required><br>

            <label>Total Amount:</label>
            <input type="text" name="totalAmount" id="totalAmount" readonly><br>

            <label>Sale Date:</label>
            <input type="text" value="<?= date('Y-m-d H:i:s') ?>" readonly><br><br>

            <!-- ✅ One Save for Both -->
            <button type="submit" name="add_client_sale">Save Client & Sale</button>
            <button type="button" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>


<script>
    // Auto calculate totalAmount
    document.getElementById("quantity").addEventListener("input", calcTotal);
    document.getElementById("unitPrice").addEventListener("input", calcTotal);

    function calcTotal() {
        let qty = parseFloat(document.getElementById("quantity").value) || 0;
        let price = parseFloat(document.getElementById("unitPrice").value) || 0;
        document.getElementById("totalAmount").value = (qty * price).toFixed(2);
    }

        // Toggle settings dropdown
        document.querySelector(".settings-btn").addEventListener("click", function() {
            document.querySelector(".settings-menu").classList.toggle("show");
        });
    </script>

</body>
</html>
