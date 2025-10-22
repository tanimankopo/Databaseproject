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
    <title>Paid Records - Cashier</title>
    <link rel="stylesheet" href="css/cashier.css">
</head>
<body>

    <div class="main-content">
        <div class="topbar">
            <h1>Paid Records</h1>
        </div>

        <!-- Paid Records -->
        <section>
            <h2>Paid Records</h2>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment Type</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $records = $conn->query("SELECT * FROM payments ORDER BY dateCreated DESC");
                    if ($records && $records->num_rows > 0) {
                        while($r = $records->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $r['paymentID'] . "</td>";
                            echo "<td>" . htmlspecialchars($r['customerName']) . "</td>";
                            echo "<td>₱" . number_format($r['totalAmount'], 2) . "</td>";
                            echo "<td>" . ucfirst($r['paymentType']) . "</td>";
                            echo "<td>" . $r['dateCreated'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No paid records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </div>

</body>
</html>