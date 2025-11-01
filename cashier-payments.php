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
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            background: #1b0c0cff;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            color: white;
        }
        th {
            background-color: #0c0303ff;
        }
        button.view {
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        button.view:hover {
            background-color: #1976D2;
        }
        form {
            display: inline;
        }
        h2 {
            color: white;
        }
    </style>
</head>
<body>

    <div class="main-content">
        <div class="topbar">
            <h1>Paid Records</h1>
        </div>

        <!-- Paid Records -->
        <section>
            <h2>Paid Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment Type</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ✅ Fetch all approved sales
                    $records = $conn->query("SELECT * FROM sales WHERE status='approved' ORDER BY saleDate DESC");

                    if ($records && $records->num_rows > 0) {
                        while ($r = $records->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $r['saleID'] . "</td>";
                            echo "<td>" . htmlspecialchars($r['customerName']) . "</td>";
                            echo "<td>₱" . number_format($r['totalAmount'], 2) . "</td>";

                            // ✅ Payment Type is always Cash
                            echo "<td>Cash</td>";

                            // ✅ Show sale date
                            echo "<td>" . $r['saleDate'] . "</td>";

                            // ✅ View Receipt Button
                            echo "<td>
                                    <form method='GET' action='generate-receipt.php' target='_blank'>
                                        <input type='hidden' name='saleID' value='" . $r['saleID'] . "'>
                                        <button type='submit' class='view'>📄 View Receipt</button>
                                    </form>
                                  </td>";

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No paid records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </div>

</body>
</html>
