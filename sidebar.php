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
        <h2 class="logo">Inventory</h2>
        <ul class="nav-links">
            <li><a href="dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'class="active"'; ?>>🏠 Dashboard</a></li>
            <li><a href="products.php"<?php if(basename($_SERVER['PHP_SELF']) == 'products.php') echo 'class="active"'; ?>>📦 Products</a></li>
            <li><a href="supplier.php"<?php if(basename($_SERVER['PHP_SELF']) == 'supplier.php') echo 'class="active"'; ?>>🏭 Suppliers</a></li>
            <li><a href="clients.php"<?php if(basename($_SERVER['PHP_SELF']) == 'clients.php') echo 'class="active"'; ?>>👥 Clients</a></li>
            <li><a href="sales.php"<?php if(basename($_SERVER['PHP_SELF']) == 'sales.php') echo 'class="active"'; ?>>💰 Sales</a></li>

           
        </ul>
        <div class="sidebar-footer">
            <form action="logout.php" method="post">
                <button type="submit">🚪 Log Out</button>
            </form>
        </div>
    </aside>



</body>
</html>