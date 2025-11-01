<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/sidebar.css">
    <title></title>
</head>
<body>
    


    <aside class="sidebar">
        <h2 class="logo">1-GARAGE</h2>
        <ul class="nav-links">
            <li><a href="admin-dashboard.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php.php') echo 'class="active"'; ?>>🏠 Dashboard</a></li>
            <li><a href="usermanagement.php" <?php if(basename($_SERVER['PHP_SELF']) == 'usermanagement.php') echo 'class="active"'; ?>>🧾 User Management</a></li>
            <li><a href="admin-products.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin-products.php') echo 'class="active"'; ?>>📦 Products</a></li>
            <li><a href="admin-supplier.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin-supplier.php') echo 'class="active"'; ?>>🏭 Suppliers</a></li>
            <li><a href="admin-clients.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin-clients.php') echo 'class="active"'; ?>>👥 Clients</a></li>

        <!-- Sales Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle">💰 Sales ▾</a>
            <ul class="dropdown-menu">
                <li><a href="admin-salesrecord.php">📊 Sales Records</a></li>
                <li><a href="salesproduct-admin.php">🛒 Sales Products</a></li>
            </ul>
        </li>


        <li><a href="about.php" <?php if(basename($_SERVER['PHP_SELF']) == 'about.php') echo 'class="active"'; ?>>🏛️ About</a></li>
    </ul>

        <div class="sidebar-footer">
                 <form action="logout.php" method="post">
                  <button type="submit">🚪 Log Out</button>
                </form>
        </div>
    </aside>


    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault(); // prevent page reload
                const parent = toggle.parentElement;
                parent.classList.toggle('open'); // toggle the "open" class
            });
        });
    });
    </script>

    
    
</body>
</html>