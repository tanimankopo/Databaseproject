<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit();
}

include "db.php";

// ✅ Get real data from database
$lowStockResult = $conn->query("SELECT * FROM products WHERE stockQuantity <= 3");
$lowStockCount = $lowStockResult->num_rows;

// ✅ Get total products count
$totalProductsResult = $conn->query("SELECT COUNT(*) as total FROM products");
if (!$totalProductsResult) {
    die("❌ Error fetching total products: " . $conn->error);
}
$totalProducts = $totalProductsResult->fetch_assoc()['total'];

// ✅ Get total stock value
$stockValueResult = $conn->query("SELECT SUM(stockQuantity * price) as totalValue FROM products");
if (!$stockValueResult) {
    die("❌ Error fetching stock value: " . $conn->error);
}
$totalStockValue = $stockValueResult->fetch_assoc()['totalValue'] ?? 0;

// ✅ Get out of stock count
$outOfStockResult = $conn->query("SELECT COUNT(*) as outOfStock FROM products WHERE stockQuantity = 0");
if (!$outOfStockResult) {
    die("❌ Error fetching out of stock count: " . $conn->error);
}
$outOfStockCount = $outOfStockResult->fetch_assoc()['outOfStock'];

// ✅ Get products for chart
$productsResult = $conn->query("SELECT productID, productName, stockQuantity, price, productsImg FROM products ORDER BY stockQuantity ASC");
if (!$productsResult) {
    die("❌ Error fetching products: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 1-GARAGE</title>
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

        .stat-trend.warning {
            color: #ffa726;
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

        .inventory-section {
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

        .section-header h2 {
            font-size: 18px;
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

        .badge.in-stock {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .badge.low-stock {
            background: rgba(255, 167, 38, 0.2);
            color: #ffa726;
        }

        .badge.out-of-stock {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #3b3f51;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            object-fit: cover;
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

        .stock-bar {
            width: 100%;
            height: 6px;
            background: #3b3f51;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }

        .stock-fill {
            height: 100%;
            background: #4caf50;
            transition: width 0.3s;
        }

        .stock-fill.low {
            background: #ffa726;
        }

        .stock-fill.critical {
            background: #ff6b6b;
        }

        .chart-section {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #2d303e;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include("sidebar-admin.php"); ?>

        <main class="main-content">
            <div class="header">
                <h1><span>📊</span> Dashboard</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.href='products-admin.php'">
                        <span>📦</span> Manage Products
                    </button>
                    <button class="btn btn-primary" onclick="window.location.href='sales-admin.php'">
                        <span>💰</span> View Sales
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="stat-value"><?= $totalProducts ?></div>
                    <div class="stat-trend positive">
                        <span>📦</span> Products in inventory
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Total Stock Value</h3>
                    <div class="stat-value">₱<?= number_format($totalStockValue, 2) ?></div>
                    <div class="stat-trend positive">
                        <span>💰</span> Total inventory value
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Low Stock Items</h3>
                    <div class="stat-value"><?= $lowStockCount ?></div>
                    <div class="stat-trend warning">
                        <span>⚠</span> Needs attention
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Out of Stock</h3>
                    <div class="stat-value"><?= $outOfStockCount ?></div>
                    <div class="stat-trend negative">
                        <span>❌</span> Restock required
                    </div>
                </div>
            </div>
         
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Stock Levels Overview</h3>
                        <select class="filter-select">
                            <option>All Products</option>
                            <option>Low Stock</option>
                            <option>Out of Stock</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Top Products by Stock</h3>
                    </div>
                    <ul class="top-products">
                        <?php
                        $productsResult->data_seek(0);
                        $rank = 1;
                        while($row = $productsResult->fetch_assoc()):
                            if ($rank > 5) break;
                        ?>
                        <li>
                            <div style="display: flex; align-items: center;">
                                <div class="product-rank <?= $rank <= 3 ? ($rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : 'bronze')) : '' ?>"><?= $rank ?></div>
                    <div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($row['productName']) ?></div>
                                    <div style="font-size: 12px; color: #b0b3ba;"><?= $row['stockQuantity'] ?> units in stock</div>
                                </div>
                            </div>
                            <div style="font-weight: 600; color: #5c9eff;">₱<?= number_format($row['price'], 2) ?></div>
                        </li>
                        <?php 
                        $rank++;
                        endwhile; 
                        ?>
                    </ul>
                </div>
            </div>

            <div class="inventory-section">
                <div class="section-header">
                    <h2>Product Inventory</h2>
                    <button class="btn btn-primary" onclick="window.location.href='products-admin.php'">+ Manage Products</button>
                </div>

                <div class="search-filter">
                    <input type="text" class="search-box" placeholder="Search products..." id="searchBox">
                    <select class="filter-select" id="categoryFilter">
                        <option>All Categories</option>
                        <option>Engine Parts</option>
                        <option>Brake System</option>
                        <option>Electrical</option>
                        <option>Fluids & Oils</option>
                    </select>
                    <select class="filter-select" id="stockFilter">
                        <option>All Stock Status</option>
                        <option>In Stock</option>
                        <option>Low Stock</option>
                        <option>Out of Stock</option>
                    </select>
                    </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Stock Level</th>
                                <th>Unit Price</th>
                                <th>Total Value</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $productsResult->data_seek(0);
                            while($row = $productsResult->fetch_assoc()): 
                                $stockLevel = $row['stockQuantity'];
                                $maxStock = 100; // You can adjust this based on your business logic
                                $stockPercentage = ($stockLevel / $maxStock) * 100;
                                
                                $status = 'in-stock';
                                $statusText = 'In Stock';
                                $stockFillClass = '';
                                
                                if ($stockLevel == 0) {
                                    $status = 'out-of-stock';
                                    $statusText = 'Out of Stock';
                                    $stockFillClass = 'critical';
                                } elseif ($stockLevel <= 3) {
                                    $status = 'low-stock';
                                    $statusText = 'Low Stock';
                                    $stockFillClass = 'low';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div class="product-img">
                                            <?php if (!empty($row['productsImg'])): ?>
                                                <img src="<?= htmlspecialchars($row['productsImg']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;" alt="Product">
                <?php else: ?>
                                                📦
                <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($row['productName']) ?></div>
                                            <div style="font-size: 12px; color: #b0b3ba;">Product ID: <?= $row['productID'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>P-<?= $row['productID'] ?></td>
                                <td>Auto Parts</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-weight: 600; color: <?= $stockLevel == 0 ? '#ff6b6b' : ($stockLevel <= 3 ? '#ffa726' : '#4caf50') ?>;"><?= $stockLevel ?></span>
                                        <span style="color: #b0b3ba;">/ <?= $maxStock ?></span>
                                    </div>
                                    <div class="stock-bar">
                                        <div class="stock-fill <?= $stockFillClass ?>" style="width: <?= min($stockPercentage, 100) ?>%;"></div>
                                    </div>
                                </td>
                                <td>₱<?= number_format($row['price'], 2) ?></td>
                                <td>₱<?= number_format($stockLevel * $row['price'], 2) ?></td>
                                <td><span class="badge <?= $status ?>"><?= $statusText ?></span></td>
                                <td>
                                    <button class="action-btn" onclick="window.location.href='products-admin.php'">📝 Edit</button>
                                    <button class="action-btn" onclick="window.location.href='products-admin.php'">📦 Restock</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Chart.js implementation
    const ctx = document.getElementById('stockChart').getContext('2d');
    const stockChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php
                $labels = [];
                $data = [];
                    $productsResult->data_seek(0);
                    while($row = $productsResult->fetch_assoc()) {
                        $labels[] = "'" . addslashes($row['productName']) . "'";
                    $data[] = $row['stockQuantity'];
                }
                echo implode(",", $labels);
                ?>
            ],
            datasets: [{
                label: 'Stock Quantity',
                data: [<?php echo implode(",", $data); ?>],
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        if (value === 0) return 'rgba(255, 107, 107, 0.6)';
                        if (value <= 3) return 'rgba(255, 167, 38, 0.6)';
                        return 'rgba(76, 175, 80, 0.6)';
                    },
                    borderColor: function(context) {
                        const value = context.parsed.y;
                        if (value === 0) return 'rgba(255, 107, 107, 1)';
                        if (value <= 3) return 'rgba(255, 167, 38, 1)';
                        return 'rgba(76, 175, 80, 1)';
                    },
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
                maintainAspectRatio: false,
            plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const value = context.parsed.y;
                                if (value === 0) return 'Status: Out of Stock';
                                if (value <= 3) return 'Status: Low Stock';
                                return 'Status: In Stock';
                            }
                        }
                    }
            },
            scales: {
                    y: { 
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Stock Quantity'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Products'
                        }
                    }
                }
            }
        });

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Stock filter functionality
        document.getElementById('stockFilter').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusBadge = row.querySelector('.badge');
                if (!statusBadge) return;
                
                const status = statusBadge.textContent.toLowerCase();
                let show = true;
                
                if (filter === 'In Stock' && !status.includes('in stock')) show = false;
                if (filter === 'Low Stock' && !status.includes('low stock')) show = false;
                if (filter === 'Out of Stock' && !status.includes('out of stock')) show = false;
                
                row.style.display = show ? '' : 'none';
            });
    });
    </script>
</body>
</html>