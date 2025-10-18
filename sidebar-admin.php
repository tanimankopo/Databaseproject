<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="dashboard.css">
    <title></title>
</head>
<body>
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2 class="logo">1-GARAGE</h2>
        <ul class="nav-links">
            <li><a href="dashboard-admin.php" <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard-admin.php') echo 'class="active"'; ?>>🏠 Dashboard</a></li>
            <li><a href="usermanagement.php" <?php if(basename($_SERVER['PHP_SELF']) == 'usermanagement.php') echo 'class="active"'; ?>>🧾 User Management</a></li>
            <li><a href="products-admin.php" <?php if(basename($_SERVER['PHP_SELF']) == 'products-admin.php') echo 'class="active"'; ?>>📦 Products</a></li>
            <li><a href="supplier-admin.php" <?php if(basename($_SERVER['PHP_SELF']) == 'supplier-admin.php') echo 'class="active"'; ?>>🏭 Suppliers</a></li>
            <li><a href="clients-admin.php" <?php if(basename($_SERVER['PHP_SELF']) == 'clients-admin.php') echo 'class="active"'; ?>>👥 Clients</a></li>
            <li><a href="sales-admin.php" <?php if(basename($_SERVER['PHP_SELF']) == 'sales-admin.php') echo 'class="active"'; ?>>💰 Sales</a></li>
            <li><a href="about.php" <?php if(basename($_SERVER['PHP_SELF']) == 'about.php') echo 'class="active"'; ?>> 🏛️ About</a></li>
        </ul>
        <div class="sidebar-footer">
            <form action="logout.php" method="post">
                <button type="submit">🚪 Log Out</button>
            </form>
        </div>
    </aside>
    
    
</body>
</html>