<?php
session_start();

// ✅ Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'sales'){
    header("Location: login.php");
    exit();
}

include "db.php";

// ---------------------
// Handle Approve/Reject
// ---------------------
if(isset($_POST['approve']) || isset($_POST['reject'])){
    $saleID = intval($_POST['saleID']);
    $status = isset($_POST['approve']) ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE sales SET status=? WHERE saleID=?");
    $stmt->bind_param("si", $status, $saleID);
    $stmt->execute();
    $stmt->close();
    header("Location: sales-approval.php");
    exit();
}

// ---------------------
// Fetch pending sales with client and cashier names
// ---------------------
$sql = "
SELECT s.saleID, s.totalAmount, s.status, s.saleDate,
       c.fullname AS clientName,
       u.fullname AS cashierName
FROM sales s
LEFT JOIN usermanagement c ON s.clientID = c.userID
LEFT JOIN usermanagement u ON s.userID = u.userID
WHERE s.status='pending'
ORDER BY s.saleDate DESC
";

$res = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Sales Approval</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f2f2f2; }
        button { padding: 4px 8px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>📝 Pending Sales</h1>

    <?php if($res->num_rows > 0): ?>
    <table>
        <tr>
            <th>Sale ID</th>
            <th>Client</th>
            <th>Cashier</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while($sale = $res->fetch_assoc()): ?>
        <tr>
            <td><?=$sale['saleID']?></td>
            <td><?=htmlspecialchars($sale['clientName'])?></td>
            <td><?=htmlspecialchars($sale['cashierName'])?></td>
            <td>₱<?=number_format($sale['totalAmount'],2)?></td>
            <td><?=htmlspecialchars($sale['status'])?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="saleID" value="<?=$sale['saleID']?>">
                    <button name="approve">✅ Approve</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="saleID" value="<?=$sale['saleID']?>">
                    <button name="reject">❌ Reject</button>
                </form>
                <form method="GET" action="generate-receipt.php" target="_blank" style="display:inline;">
                    <input type="hidden" name="saleID" value="<?=$sale['saleID']?>">
                    <button>📄 View PDF</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p>No pending sales at the moment.</p>
    <?php endif; ?>
</body>
</html>
