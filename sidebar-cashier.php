
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Sidebar</title>
    <link rel="stylesheet" href="css/cashier.css"> <!-- Link to the CSS file -->
</head>
<body>
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2 class="logo">1-GARAGE</h2>
        <ul class="nav-links">
            <li><a href="dashboard-cashier.php" <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard-cashier.php') echo 'class="active"'; ?>>🏠 Dashboard</a></li>
            <li><a href="cashier-items.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cashier-items.php') echo 'class="active"'; ?>>📦 Products</a></li>
            <li><a href="cashier-payments.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cashier-payments.php') echo 'class="active"'; ?>>💳 Paid Records</a></li>
            <li><a href="cashier-messages.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cashier-messages.php') echo 'class="active"'; ?>>💬 Messages</a></li>
            
        </ul>
        <div class="sidebar-footer">
            <form action="logout.php" method="post">
                <button type="submit">🚪 Log Out</button>
            </form>
        </div>
    </aside> 
</body>
</html>