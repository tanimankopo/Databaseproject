<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/sidebar.css">
    <title></title>
</head>
<body>
    
      <!-- Sidebar -->
    <aside class="sidebar">
        <h2 class="logo">1-GARAGE</h2>
        <ul class="nav-links">
            <li><a href="sales-dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'sales-dashboard.php') echo 'class="active"'; ?>>🏠 Dashboard</a></li>
            <li><a href="sales-products.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales-products.php') echo 'class="active"'; ?>>📦 Products</a></li>
            <li><a href="sales-supplier.php"<?php if(basename($_SERVER['PHP_SELF']) == 'ales-supplier.php') echo 'class="active"'; ?>>🏭 Suppliers</a></li>
            <li><a href="sales-clients.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales-clients.php') echo 'class="active"'; ?>>👥 Clients</a></li>
            <li><a href="sales-sales.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales-sales.php') echo 'class="active"'; ?>>💰 Sales</a></li>
            <li><a href="sales-approval.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales-approval.php') echo 'class="active"'; ?>>🗂️ Status</a></li>
        </ul>
        <div class="sidebar-footer">
            <form action="logout.php" method="post">
                <button type="submit">🚪 Log Out</button>
            </form>
        </div>
    </aside>

</body>
</html>