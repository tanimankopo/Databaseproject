<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Insert Client and Sale together
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_client'])) {
    $clientName    = $_POST['clientName'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];
    $productID     = intval($_POST['productID']);
    $userID        = intval($_POST['userID']);
    $quantity      = intval($_POST['quantity']);
    $unitPrice     = floatval($_POST['unitPrice']);

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, insert the client
        $stmt = $conn->prepare("INSERT INTO clientinfo (clientName, contactNumber, email, address) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $clientName, $contactNumber, $email, $address);
        $stmt->execute();
        $newClientID = $conn->insert_id;
        $stmt->close();

        // Then, insert the sale with the new client ID
        $stmt = $conn->prepare("INSERT INTO sales (clientID, productID, userID, quantity, unitPrice, saleDate) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiid", $newClientID, $productID, $userID, $quantity, $unitPrice);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();
        
        // Simple success message
        $success_message = "✅ Client and Sale added successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "❌ Error: " . $e->getMessage();
    }
}

// ✅ Fetch Sales
$salesResult = $conn->query("SELECT * FROM sales ORDER BY saleID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - 1-GARAGE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #1a1d29;
            color: #e4e7eb;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background: #252836;
            padding: 20px 0;
        }

        .logo {
            padding: 0 20px 30px;
            font-size: 20px;
            font-weight: bold;
            color: #fff;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #b0b3ba;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: #2d303e;
            color: #fff;
        }

        .nav-links a.active {
            background: #3b3f51;
            color: #fff;
            border-left: 3px solid #5c9eff;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #5c9eff;
            color: #fff;
        }

        .btn-primary:hover {
            background: #4a8de8;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .stat-card h3 {
            color: #b0b3ba;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .sales-section {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #2d303e;
            font-weight: 600;
            font-size: 13px;
            color: #b0b3ba;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #2d303e;
        }

        tr:hover {
            background: #2d303e;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #252836;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-btn {
            background: none;
            border: none;
            color: #b0b3ba;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b0b3ba;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid #4caf50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include("sidebar-admin.php"); ?>

        <main class="main-content">
            <div class="header">
                <h1><span>💰</span> Sales Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal()">
                        <span>+</span> New Sale
                    </button>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>

            <?php
            // Calculate statistics
            $totalSales = 0;
            $totalOrders = 0;
            
            $salesResult->data_seek(0);
            while($row = $salesResult->fetch_assoc()) {
                $totalSales += $row['totalAmount'];
                $totalOrders++;
            }
            
            $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <div class="stat-value">₱<?= number_format($totalSales, 2) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?= $totalOrders ?></div>
                </div>
                <div class="stat-card">
                    <h3>Avg. Order Value</h3>
                    <div class="stat-value">₱<?= number_format($avgOrderValue, 2) ?></div>
                </div>
            </div>

            <div class="sales-section">
                <div class="section-header">
                    <h2>Recent Sales Transactions</h2>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Client</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $salesResult->data_seek(0);
                        while($row = $salesResult->fetch_assoc()): 
                            // Get client details
                            $clientQuery = $conn->query("SELECT clientName FROM clientinfo WHERE clientID = " . $row['clientID']);
                            $client = $clientQuery->fetch_assoc();
                            
                            // Get product details
                            $productQuery = $conn->query("SELECT productName FROM products WHERE productID = " . $row['productID']);
                            $product = $productQuery->fetch_assoc();
                        ?>
                        <tr>
                            <td>#S-<?= $row['saleID'] ?></td>
                            <td><?= $client ? htmlspecialchars($client['clientName']) : "Client #" . $row['clientID'] ?></td>
                            <td><?= $product ? htmlspecialchars($product['productName']) : "Product #" . $row['productID'] ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td>₱<?= number_format($row['totalAmount'], 2) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($row['saleDate'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Client + Sales Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Client & Sale</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="sales-simple.php">
                <h4 style="color: #5c9eff; margin-bottom: 15px;">👥 Client Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Client Name:</label>
                        <input type="text" name="clientName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number:</label>
                        <input type="text" name="contactNumber" class="form-input">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Address:</label>
                        <input type="text" name="address" class="form-input">
                    </div>
                </div>

                <h4 style="color: #5c9eff; margin: 20px 0 15px 0;">💰 Sales Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Product:</label>
                        <select name="productID" class="form-input" required>
                            <option value="">Select Product</option>
                            <?php
                            $productsQuery = $conn->query("SELECT productID, productName FROM products ORDER BY productName");
                            while($product = $productsQuery->fetch_assoc()):
                            ?>
                            <option value="<?= $product['productID'] ?>"><?= htmlspecialchars($product['productName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>User ID:</label>
                        <input type="number" name="userID" class="form-input" value="<?= $_SESSION['user_id'] ?? 1 ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" name="quantity" class="form-input" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Unit Price:</label>
                        <input type="number" step="0.01" name="unitPrice" class="form-input" min="0" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add_client" class="btn btn-primary">Save Client & Sale</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
