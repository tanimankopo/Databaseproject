<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit();
}

include "db.php";

// Example: low stock query
$lowStockResult = $conn->query("SELECT * FROM products WHERE stockQuantity <= 3");
$lowStockCount = $lowStockResult->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard-Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <?php
            include("sidebar-admin.php")
    ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <h1>Welcome, <?php echo $_SESSION['username']; ?> 👋</h1>
        </header>

        <!-- Dashboard Tiles -->
        <section class="tiles">
            <div class="tile">
                <h3>50</h3>
                <p>Total Sales</p>
            </div>
            <div class="tile">
                <h3>50</h3>
                <p>Top Products</p>
            </div>
            <div class="tile">
                <h3>50</h3>
                <p>Incoming shipment</p>
            </div>

            <!-- Low Stock Tile -->
            <div class="tile <?php echo ($lowStockCount > 0) ? 'low-stock' : ''; ?>">
                <h3><?php echo $lowStockCount; ?></h3>
                <p>Stock Alerts</p>

                <?php if($lowStockCount > 0): ?>
                    <div>
                        <?php while($row = $lowStockResult->fetch_assoc()): ?>
                            <div class="low-stock-alert">
                                ⚠️ <?php echo $row['productName']; ?> - <?php echo $row['stockQuantity']; ?> left
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No low stock items</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Chart Section -->
        <section class="chart-section">
            <h2>📊 Stock Levels</h2>
            <canvas id="stockChart"></canvas>
        </section>
    </div>

    <script>
    const ctx = document.getElementById('stockChart').getContext('2d');
    const stockChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php
                $labels = [];
                $data = [];
                $result = $conn->query("SELECT productName, stockQuantity FROM products");
                while($row = $result->fetch_assoc()) {
                    $labels[] = "'" . $row['productName'] . "'";
                    $data[] = $row['stockQuantity'];
                }
                echo implode(",", $labels);
                ?>
            ],
            datasets: [{
                label: 'Stock Quantity',
                data: [<?php echo implode(",", $data); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    </script>
</body>
</html>
