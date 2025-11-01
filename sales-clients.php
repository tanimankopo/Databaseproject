<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Fetch clients only (no add, update, delete)
$result = $conn->query("SELECT * FROM clientinfo ORDER BY clientID ASC");
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <link rel="stylesheet" href="css/client.css">
</head>
<body>

<?php include("sidebar-sales.php"); ?>

<div class="main-content">
    <header class="topbar">
        <h1>👥 Clients</h1>
    </header>

    <!-- Clients Table -->
    <table class="supplier-table">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Address</th>
                <th>Registered Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['clientName']); ?></td>
                <td><?= htmlspecialchars($row['contactNumber']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['address']); ?></td>
                <td><?= htmlspecialchars($row['registeredDate']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
