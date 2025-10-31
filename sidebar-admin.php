<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- External CSS -->
    <link rel="stylesheet" type="text/css" href="dashboard.css">
</head>
<body>

    <aside class="sidebar">
        <h2 class="logo">1-GARAGE</h2>

        <ul class="nav-links">
            <li>
                <a href="dashboard-admin.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard-admin.php') ? 'active' : ''; ?>">
                   🏠 Dashboard
                </a>
            </li>
            <li>
                <a href="usermanagement.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) === 'usermanagement.php') ? 'active' : ''; ?>">
                   🧾 User Management
                </a>
            </li>
            <li>
                <a href="products-admin.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) === 'products-admin.php') ? 'active' : ''; ?>">
                   📦 Products
                </a>
            </li>
            <li>
                <a href="supplier-admin.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) === 'supplier-admin.php') ? 'active' : ''; ?>">
                   🏭 Suppliers
                </a>
            </li>
            <li>
                <a href="clients-admin.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) === 'clients-admin.php') ? 'active' : ''; ?>">
                   👥 Clients
                </a>
            </li>

            <!-- Sales Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    💰 Sales ▾
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="sales-admin.php" 
                           class="<?php echo (basename($_SERVER['PHP_SELF']) === 'sales-product.php') ? 'active' : ''; ?>">
                           📊 Sales Product
                        </a>
                    </li>
                    <li>
                        <a href="sales-admin.php" 
                           class="<?php echo (basename($_SERVER['PHP_SELF']) === 'sales-records.php') ? 'active' : ''; ?>">
                           🧾 Sales Records
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="about.php" 
                   class="<?php echo (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : ''; ?>">
                   🏛️ About
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <form action="logout.php" method="post">
                <button type="submit">🚪 Log Out</button>
            </form>
        </div>
    </aside>

    <!-- Dropdown JS -->
   <script>
document.addEventListener('DOMContentLoaded', () => {
    // Toggle dropdown open/close on click
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', e => {
            e.preventDefault();

            const parentDropdown = toggle.parentElement;
            const isOpen = parentDropdown.classList.contains('open');

            // Close other dropdowns
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));

            // Toggle the clicked one
            if (!isOpen) parentDropdown.classList.add('open');
        });
    });

    // ❌ Removed the auto-open feature so dropdowns open only when clicked
});
</script>

</body>
</html>
