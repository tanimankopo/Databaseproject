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

        // Then, insert the sale with the new client ID (totalAmount is auto-calculated)
        $stmt = $conn->prepare("INSERT INTO sales (clientID, productID, userID, quantity, unitPrice, saleDate) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiid", $newClientID, $productID, $userID, $quantity, $unitPrice);
    $stmt->execute();
    $stmt->close();

        // Commit transaction
        $conn->commit();
        
        // Success redirect with parameter
        header("Location: sales-admin.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "<script>alert('❌ Error: " . $e->getMessage() . "'); window.location='sales-admin.php';</script>";
    exit();
}
}

// ✅ Fetch clients
$result = $conn->query("SELECT * FROM clientinfo ORDER BY clientID ASC");
if (!$result) {
    die("❌ Error fetching clients: " . $conn->error);
}

// ✅ Fetch Sales
$salesResult = $conn->query("SELECT * FROM sales ORDER BY saleID ASC");
if (!$salesResult) {
    die("❌ Error fetching sales: " . $conn->error);
}
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            display: flex;
            flex-direction: column;
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

        .nav-links li {
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

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
        }

        .sidebar-footer button {
            width: 100%;
            padding: 12px;
            background: #ff6b6b;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .sidebar-footer button:hover {
            background: #ff5252;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
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

        .header-actions {
            display: flex;
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

        .btn-secondary {
            background: #3b3f51;
            color: #e4e7eb;
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

        .stat-trend {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-trend.positive {
            color: #4caf50;
        }

        .stat-trend.negative {
            color: #ff6b6b;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .chart-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-header h3 {
            font-size: 16px;
        }

        .chart-placeholder {
            width: 100%;
            height: 250px;
            background: #2d303e;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b0b3ba;
            font-size: 14px;
        }

        .top-products {
            list-style: none;
        }

        .top-products li {
            padding: 12px;
            background: #2d303e;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-rank {
            width: 30px;
            height: 30px;
            background: #3b3f51;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
        }

        .product-rank.gold {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #1a1d29;
        }

        .product-rank.silver {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #1a1d29;
        }

        .product-rank.bronze {
            background: linear-gradient(135deg, #cd7f32, #e6a857);
            color: #1a1d29;
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

        .search-filter {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        .filter-select {
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
            cursor: pointer;
        }

        .date-input {
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        .table-container {
            overflow-x: auto;
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

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.completed {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .badge.pending {
            background: rgba(255, 167, 38, 0.2);
            color: #ffa726;
        }

        .badge.cancelled {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .action-btn {
            padding: 6px 12px;
            background: #3b3f51;
            border: none;
            border-radius: 6px;
            color: #e4e7eb;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #4a5061;
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
            max-width: 700px;
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

        .modal-header h2 {
            font-size: 24px;
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

        .sale-items {
            margin: 20px 0;
        }

        .sale-item {
            background: #2d303e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 40px;
            gap: 15px;
            align-items: center;
        }

        .remove-btn {
            width: 30px;
            height: 30px;
            background: #ff6b6b;
            border: none;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-item-btn {
            width: 100%;
            padding: 10px;
            background: #3b3f51;
            border: 1px dashed #5c9eff;
            border-radius: 8px;
            color: #5c9eff;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .add-item-btn:hover {
            background: #4a5061;
        }

        .sale-summary {
            background: #2d303e;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 600;
            padding-top: 10px;
            border-top: 1px solid #3b3f51;
            color: #5c9eff;
        }

        .product-image {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 10px;
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
                    <button class="btn btn-secondary">
                        <span>📊</span> Reports
                    </button>
                    <button class="btn btn-primary" onclick="openModal()">
                        <span>+</span> New Sale
                    </button>
                </div>
            </div>

    <?php
            // Calculate statistics
            $totalSales = 0;
            $totalOrders = 0;
            $avgOrderValue = 0;
            
            // Reset result pointer
            $salesResult->data_seek(0);
            while($row = $salesResult->fetch_assoc()) {
                $totalSales += $row['totalAmount'];
                $totalOrders++;
            }
            
            if ($totalOrders > 0) {
                $avgOrderValue = $totalSales / $totalOrders;
            }
            
            // Get today's sales
            $todaySales = 0;
            $today = date('Y-m-d');
            $salesResult->data_seek(0);
            while($row = $salesResult->fetch_assoc()) {
                if (date('Y-m-d', strtotime($row['saleDate'])) == $today) {
                    $todaySales += $row['totalAmount'];
                }
            }
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Today's Sales</h3>
                    <div class="stat-value">₱<?= number_format($todaySales, 2) ?></div>
                    <div class="stat-trend positive">
                        <span>↑</span> Today's revenue
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <div class="stat-value">₱<?= number_format($totalSales, 2) ?></div>
                    <div class="stat-trend positive">
                        <span>↑</span> All time revenue
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?= $totalOrders ?></div>
                    <div class="stat-trend positive">
                        <span>↑</span> Total transactions
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Avg. Order Value</h3>
                    <div class="stat-value">₱<?= number_format($avgOrderValue, 2) ?></div>
                    <div class="stat-trend positive">
                        <span>↑</span> Average per order
                    </div>
                </div>
            </div>

            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Overview</h3>
                        <select class="filter-select">
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>Last 3 Months</option>
                            <option>This Year</option>
                        </select>
                    </div>
                    <div class="chart-placeholder">
                        📈 Sales Chart (Daily Revenue Trend)
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Top Selling Products</h3>
                    </div>
                    <ul class="top-products">
                        <?php
                        // Get products with sales data
                        $productSales = [];
                        $salesResult->data_seek(0);
                        while($row = $salesResult->fetch_assoc()) {
                            if (!isset($productSales[$row['productID']])) {
                                $productSales[$row['productID']] = [
                                    'quantity' => 0,
                                    'revenue' => 0,
                                    'productID' => $row['productID']
                                ];
                            }
                            $productSales[$row['productID']]['quantity'] += $row['quantity'];
                            $productSales[$row['productID']]['revenue'] += $row['totalAmount'];
                        }
                        
                        // Sort by revenue
                        uasort($productSales, function($a, $b) {
                            return $b['revenue'] - $a['revenue'];
                        });
                        
                        $rank = 1;
                        foreach(array_slice($productSales, 0, 5, true) as $productID => $data):
                            // Get product details
                            $productQuery = $conn->query("SELECT productName, productsImg FROM products WHERE productID = $productID");
                            $product = $productQuery->fetch_assoc();
                        ?>
                        <li>
                            <div style="display: flex; align-items: center;">
                                <div class="product-rank <?= $rank <= 3 ? ($rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : 'bronze')) : '' ?>"><?= $rank ?></div>
                                <div>
                                    <div style="font-weight: 600;"><?= $product ? htmlspecialchars($product['productName']) : "Product #$productID" ?></div>
                                    <div style="font-size: 12px; color: #b0b3ba;"><?= $data['quantity'] ?> units sold</div>
                                </div>
                            </div>
                            <div style="font-weight: 600; color: #5c9eff;">₱<?= number_format($data['revenue'], 2) ?></div>
                        </li>
                        <?php 
                        $rank++;
                        endforeach; 
                        ?>
                    </ul>
                </div>
            </div>

            <div class="sales-section">
                <div class="section-header">
                    <h2>Recent Sales Transactions</h2>
                </div>

                <div class="search-filter">
                    <input type="text" class="search-box" placeholder="Search by sale ID, client name, or product...">
                    <input type="date" class="date-input">
                    <input type="date" class="date-input">
                    <select class="filter-select">
                        <option>All Status</option>
                        <option>Completed</option>
                        <option>Pending</option>
                        <option>Cancelled</option>
                    </select>
                    <select class="filter-select">
                        <option>All Clients</option>
                        <option>VIP Clients</option>
                        <option>Regular Clients</option>
                    </select>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
            <tr>
                <th>Sale ID</th>
                                <th>Client</th>
                                <th>Product</th>
                <th>Quantity</th>
                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
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
                                $productQuery = $conn->query("SELECT productName, productsImg FROM products WHERE productID = " . $row['productID']);
                                $product = $productQuery->fetch_assoc();
                                
                                // Determine status (random for demo)
                                $statuses = ['completed', 'pending', 'cancelled'];
                                $status = $statuses[array_rand($statuses)];
                            ?>
                            <tr>
                                <td style="font-weight: 600; color: #5c9eff;">#S-<?= $row['saleID'] ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= $client ? htmlspecialchars($client['clientName']) : "Client #" . $row['clientID'] ?></div>
                                    <div style="font-size: 12px; color: #b0b3ba;">Client ID: <?= $row['clientID'] ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <?php if($product && !empty($product['productsImg'])): ?>
                                            <img src="<?= $product['productsImg'] ?>" class="product-image" alt="Product">
                                        <?php endif; ?>
                                        <div>
                                            <div><?= $product ? htmlspecialchars($product['productName']) : "Product #" . $row['productID'] ?></div>
                                            <div style="font-size: 12px; color: #b0b3ba;">₱<?= number_format($row['unitPrice'], 2) ?> per unit</div>
                                        </div>
                                    </div>
                                </td>
                <td><?= $row['quantity'] ?></td>
                                <td style="font-weight: 600;">₱<?= number_format($row['totalAmount'], 2) ?></td>
                                <td><span class="badge <?= $status ?>"><?= ucfirst($status) ?></span></td>
                                <td>
                                    <div><?= date('Y-m-d', strtotime($row['saleDate'])) ?></div>
                                    <div style="font-size: 12px; color: #b0b3ba;"><?= date('H:i A', strtotime($row['saleDate'])) ?></div>
                                </td>
                                <td>
                                    <button class="action-btn">👁️ View</button>
                                    <button class="action-btn">🖨️ Print</button>
                                </td>
            </tr>
            <?php endwhile; ?>
                        </tbody>
        </table>
                </div>
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
            <form method="POST" action="sales-admin.php">
            <!-- ✅ Client Section -->
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

            <!-- ✅ Sales Section -->
                <h4 style="color: #5c9eff; margin: 20px 0 15px 0;">💰 Sales Information</h4>
                <div class="form-row">
                    <div class="form-group">
            <label>Product ID:</label>
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
                        <input type="number" name="quantity" id="quantity" class="form-input" min="1" required>
                    </div>
                    <div class="form-group">
            <label>Unit Price:</label>
                        <input type="number" step="0.01" name="unitPrice" id="unitPrice" class="form-input" min="0" required>
                    </div>
                </div>

                <div class="form-group">
            <label>Total Amount:</label>
                    <input type="text" name="totalAmount" id="totalAmount" class="form-input" readonly style="background: #1a1d29; color: #5c9eff; font-weight: 600;">
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

    // Auto calculate totalAmount
    document.getElementById("quantity").addEventListener("input", calcTotal);
    document.getElementById("unitPrice").addEventListener("input", calcTotal);

    function calcTotal() {
        let qty = parseFloat(document.getElementById("quantity").value) || 0;
        let price = parseFloat(document.getElementById("unitPrice").value) || 0;
            let total = (qty * price).toFixed(2);
            document.getElementById("totalAmount").value = total;
            
            // Update the display with currency formatting
            if (total > 0) {
                document.getElementById("totalAmount").value = "₱" + total;
            }
        }

        // Form validation and submission
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            let qty = parseFloat(document.getElementById("quantity").value) || 0;
            let price = parseFloat(document.getElementById("unitPrice").value) || 0;
            
            if (qty <= 0) {
                alert('❌ Please enter a valid quantity (greater than 0)');
                return false;
            }
            
            if (price <= 0) {
                alert('❌ Please enter a valid unit price (greater than 0)');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Saving...';
            submitBtn.disabled = true;
            
            // Create form data
            const formData = new FormData(this);
            
            // Submit via fetch
            fetch('sales-admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show success message
                    showSuccessMessage();
                    // Close modal
                    closeModal();
                    // Reload page after a short delay
                    setTimeout(() => {
                        window.location.href = 'sales-admin.php';
                    }, 1500);
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error saving data. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Success message function
        function showSuccessMessage() {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                font-weight: 600;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = '✅ Client and Sale added successfully!';
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
