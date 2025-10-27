<?php
session_start();

// ✅ Protect page (Only Sales Account)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'sales') {
    header("Location: login.php");
    exit();
}

include "db.php";

// ---------------------
// Handle Approve / Reject
// ---------------------
if (isset($_POST['approve']) || isset($_POST['reject'])) {
 $saleID = intval($_POST['saleID']);
    $status = isset($_POST['approve']) ? 'approved' : 'rejected';

    $stmt = $conn->prepare("UPDATE sales SET status=? WHERE saleID=?");
    $stmt->bind_param("si", $status, $saleID);
    $stmt->execute();
    $stmt->close();

    // After approval/rejection, you should update stock quantity here if status is 'approved'.
    // Stock logic would involve: 
    // 1. SELECT items from sale_items WHERE saleID = ?
    // 2. Loop through results: UPDATE products SET stockQuantity = stockQuantity - item.quantity WHERE productID = ?

    header("Location: sales-approval.php");
    exit();
}

// ---------------------
// Fetch all pending sales assigned to this sales account
// ---------------------
$currentSales = $_SESSION['username'];

$sql = "
SELECT 
    s.saleID, 
    s.customerName, 
    s.totalAmount, 
    s.status, 
    s.saleDate,
    uc.username AS cashierName
FROM sales s
LEFT JOIN usermanagement uc ON s.cashierID = uc.userID
LEFT JOIN usermanagement us ON s.salesAccountID = us.userID 
WHERE s.status = 'pending' AND us.username = ?
ORDER BY s.saleDate DESC
"; // <-- Ensure the closing quote is directly after the semicolon.

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $currentSales);
// ... (rest of the file)
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Sales Approval</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #f2f2f2;
        }
        button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[name="approve"] {
            background-color: #4CAF50;
            color: white;
        }
        button[name="reject"] {
            background-color: #f44336;
            color: white;
        }
        button.view {
            background-color: #2196F3;
            color: white;
        }
        form {
            display: inline;
        }
        .no-data {
            background: white;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>📝 Pending Sales for Approval</h1>

    <?php if ($res->num_rows > 0): ?>
        <table>
            <tr>
                <th>Sale ID</th>
                <th>Customer</th>
                <th>Cashier</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>

            <?php while ($sale = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($sale['saleID']) ?></td>
                    <td><?= htmlspecialchars($sale['customerName']) ?></td>
                    <td><?= htmlspecialchars($sale['cashierName']) ?></td>
                    <td>₱<?= number_format($sale['totalAmount'], 2) ?></td>
                    <td><?= htmlspecialchars($sale['status']) ?></td>
                    <td><?= htmlspecialchars($sale['saleDate']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="saleID" value="<?= $sale['saleID'] ?>">
                            <button type="submit" name="approve">✅ Approve</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="saleID" value="<?= $sale['saleID'] ?>">
                            <button type="submit" name="reject">❌ Reject</button>
                        </form>
                        <form method="GET" action="generate-receipt.php" target="_blank">
                            <input type="hidden" name="saleID" value="<?= $sale['saleID'] ?>">
                            <button type="submit" class="view">📄 View Receipt</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No pending sales for your account right now.</p>
        </div>
    <?php endif; ?>
</body>
</html>
