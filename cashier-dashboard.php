<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) {
    header("Location: login.php");
    exit();
}

include "db.php";
include "sidebar-cashier.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="css/cashier.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="main-content">
        <div class="topbar">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</h1>
            <div class="settings-menu">
                <button class="settings-btn" onclick="toggleSettings()">⚙️</button>
                <div class="settings-dropdown">
                    <button class="add-btn" onclick="window.location.href='cashier-items.php'">📦 View Items</button>
                    <button onclick="window.location.href='cashier-payments.php'">💳 View Payments</button>
                    <button onclick="window.location.href='cashier-messages.php'">💬 Send Message</button>
                    <button onclick="window.location.href='cashier-receipts.php'">🧾 Generate Receipt</button>
                </div>
            </div>
        </div>

        <section>
            <h2>Dashboard Overview</h2>
            
        </section>
    </div>

    <script>
        function toggleSettings() {
            document.querySelector('.settings-menu').classList.toggle('show');
        }
    </script>

</body>
</html>