<?php
session_start();

// Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php'; // Include your database connection
// Fetch some dynamic stats for the about page (e.g., user count, product count)
// Fetch some dynamic stats for the about page (e.g., user count, product count)
// Fetch some dynamic stats for the about page (e.g., user count, product count)
// Fetch some dynamic stats for the about page (e.g., user count, product count)
// Fetch some dynamic stats for the about page (e.g., user count, product count)
// Fetch some dynamic stats for the about page (e.g., user count, product count)
// Fetch some dynamic stats for the about page (e.g., user count, product count)
$userCount = $conn->query("SELECT COUNT(*) as count FROM usermanagement")->fetch_assoc()['count'];
$productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Inventory System</title>
    <link rel="stylesheet" href="css/about.css"> <!-- Link to the new CSS -->
</head>
<body>
    <!-- Sidebar (matches existing design) -->
      <!-- Sidebar (matches existing design) -->
        <!-- Sidebar (matches existing design) -->
    
    <?php
        include ('sidebar-admin.php');
    ?>

    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <h1>About Inventory System</h1>
        </header>

        <!-- About Card (like dashboard/product for readability and interactivity) -->
        <div class="about-card">
            <h2>Introduction</h2>
            <p>Welcome to the 1Garage Inventory Management System, a comprehensive tool designed to streamline inventory operations for automotive businesses. This system helps track products, manage suppliers, handle clients, and monitor sales efficiently.</p>

            <h2>Key Features</h2>
            <ul>
                <li>🧾 User Management: Secure role-based access for admins, sales, and accountants.</li>
                <li>📦 Product Tracking: Monitor stock levels, add/update products, and set alerts for low inventory.</li>
                <li>🏭 Supplier Management: Maintain supplier details and integrate with product orders.</li>
                <li>👥 Client Management: Store client information and link to sales records.</li>
                <li>💰 Sales Monitoring: Track sales, calculate totals, and generate reports.</li>
                <li>🔒 Security: Includes password reset via email and session-based protection.</li>
            </ul>

            <h2>Benefits</h2>
            <p>This system enhances efficiency by reducing manual errors, providing real-time insights, and ensuring secure data handling. With features like email notifications and user roles, it's built for scalability and ease of use.</p>

            <h2>Statistics</h2>
            <p>Current Users: <?php echo $userCount; ?></p>
            <p>Total Products: <?php echo $productCount; ?></p>

            <h2>Contact Us</h2>
            <p>For support, email us at support@1garage.com or visit our website.</p>
        </div>
    </div>

    <script>
        // Simple script for sidebar toggle (like other pages)
        document.querySelector('.hamburger').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>