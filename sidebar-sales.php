<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<body>
    
      <!-- Sidebar -->
    <aside class="sidebar">
        <h2 class="logo">1-GARAGE</h2>
        <ul class="nav-links">
            <li><a href="dashboard-sales.php" <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard-sales.php') echo 'class="active"'; ?>>🏠 Dashboard</a></li>
            <li><a href="products-sales.php"<?php if(basename($_SERVER['PHP_SELF']) == 'products-sales.php') echo 'class="active"'; ?>>📦 Products</a></li>
            <li><a href="supplier-sales.php"<?php if(basename($_SERVER['PHP_SELF']) == 'supplier-sales.php') echo 'class="active"'; ?>>🏭 Suppliers</a></li>
            <li><a href="clients-sales.php"<?php if(basename($_SERVER['PHP_SELF']) == 'clients-sales.php') echo 'class="active"'; ?>>👥 Clients</a></li>
            <li><a href="sales-sales.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales-sales.php') echo 'class="active"'; ?>>💰 Sales</a></li>
            <!-- Messages Section -->
            <li><a href="sales-messages.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales-messages.php') echo 'class="active"'; ?>>💬 Messages</a></li>
        </ul>
        <div class="sidebar-footer">
            <form action="logout.php" method="post">
                <button type="submit">🚪 Log Out</button>
            </form>
        </div>
    </aside>



</body>
</html>