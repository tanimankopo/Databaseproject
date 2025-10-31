<?php
session_start();

// ✅ Protect page (Only Sales Account)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'sales') {
    header("Location: login.php");
    exit();
}

include "db.php";

// ---------------------
// Handle AJAX Actions
// ---------------------
if (isset($_POST['ajax_action'])) {
    $action = $_POST['ajax_action'];
    $saleID = intval($_POST['saleID']);

    // --- Fetch sale items for confirmation modal ---
    if ($action === 'get_items') {
        $stmt = $conn->prepare("
            SELECT p.productName, si.quantity, si.unitPrice 
            FROM sale_items si
            JOIN products p ON si.productID = p.productID
            WHERE si.saleID = ?
        ");
        $stmt->bind_param("i", $saleID);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode(["success" => true, "items" => $items]);
        exit;
    }

    // --- Approve ---
    if ($action === 'approve_confirmed') {
        $check = $conn->prepare("SELECT status FROM sales WHERE saleID = ?");
        $check->bind_param("i", $saleID);
        $check->execute();
        $check->bind_result($currentStatus);
        $check->fetch();
        $check->close();

        if ($currentStatus === 'approved' || $currentStatus === 'rejected') {
            echo json_encode(["success" => false, "error" => "This sale has already been processed."]);
            exit;
        }

        $conn->begin_transaction();
        try {
            // 1️⃣ Deduct stock per item
            $itemsQuery = $conn->prepare("SELECT productID, quantity FROM sale_items WHERE saleID = ?");
            $itemsQuery->bind_param("i", $saleID);
            $itemsQuery->execute();
            $itemsResult = $itemsQuery->get_result();

            while ($item = $itemsResult->fetch_assoc()) {
                $productID = $item['productID'];
                $quantity  = $item['quantity'];

                $updateStock = $conn->prepare("
                    UPDATE products 
                    SET stockQuantity = GREATEST(stockQuantity - ?, 0)
                    WHERE productID = ?
                ");
                $updateStock->bind_param("ii", $quantity, $productID);
                $updateStock->execute();
                $updateStock->close();
            }
            $itemsQuery->close();

            // 2️⃣ Update sale status
            $updateStatus = $conn->prepare("UPDATE sales SET status = 'approved' WHERE saleID = ?");
            $updateStatus->bind_param("i", $saleID);
            $updateStatus->execute();
            $updateStatus->close();

            $conn->commit();
            echo json_encode(["success" => true, "status" => "approved"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
        }
        exit;
    }

    // --- Reject ---
    if ($action === 'reject') {
        $check = $conn->prepare("SELECT status FROM sales WHERE saleID = ?");
        $check->bind_param("i", $saleID);
        $check->execute();
        $check->bind_result($currentStatus);
        $check->fetch();
        $check->close();

        if ($currentStatus === 'approved' || $currentStatus === 'rejected') {
            echo json_encode(["success" => false, "error" => "This sale has already been processed."]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE sales SET status = 'rejected' WHERE saleID = ?");
        $stmt->bind_param("i", $saleID);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["success" => true, "status" => "rejected"]);
        exit;
    }

    // --- Delete ---
    if ($action === 'delete') {
        $delItems = $conn->prepare("DELETE FROM sale_items WHERE saleID = ?");
        $delItems->bind_param("i", $saleID);
        $delItems->execute();
        $delItems->close();

        $delSale = $conn->prepare("DELETE FROM sales WHERE saleID = ?");
        $delSale->bind_param("i", $saleID);
        $delSale->execute();
        $delSale->close();

        echo json_encode(["success" => true, "deleted" => true]);
        exit;
    }
}

// ---------------------
// Fetch all sales for this account
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
WHERE us.username = ?
ORDER BY s.saleDate DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $currentSales);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/sales-approve.css">
<meta charset="UTF-8">
<title>Sales Approval</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f7f7f7;
    margin: 20px;
}
h1 { color: #333; }
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
th { background: #f2f2f2; }
button {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
button[name="approve"], .approve-btn { background-color: #4CAF50; color: white; }
button[name="reject"], .reject-btn { background-color: #f44336; color: white; }
button.view { background-color: #2196F3; color: white; }
button.delete { background-color: #555; color: white; }
.status-approved { color: green; font-weight: bold; }
.status-rejected { color: red; font-weight: bold; }
.status-pending { color: orange; font-weight: bold; }
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    max-height: 80vh;
    overflow-y: auto;
}
</style>
</head>
<body>

<h1>📝 Sales Approval</h1>
<a href="dashboard-sales.php" style="display:inline-block;padding:8px 16px;background-color:#555;color:white;border-radius:4px;text-decoration:none;margin-bottom:15px;">⬅ Back</a>

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
    <tr id="row-<?= $sale['saleID'] ?>">
        <td><?= htmlspecialchars($sale['saleID']) ?></td>
        <td><?= htmlspecialchars($sale['customerName']) ?></td>
        <td><?= htmlspecialchars($sale['cashierName']) ?></td>
        <td>₱<?= number_format($sale['totalAmount'], 2) ?></td>
        <td class="status status-<?= $sale['status'] ?>"><?= htmlspecialchars(ucfirst($sale['status'])) ?></td>
        <td><?= htmlspecialchars($sale['saleDate']) ?></td>
        <td>
            <?php if ($sale['status'] === 'pending'): ?>
                <button class="approve-btn" onclick="showApprovalModal(<?= $sale['saleID'] ?>)">✅ Approve</button>
                <button class="reject-btn" onclick="handleAction(<?= $sale['saleID'] ?>, 'reject')">❌ Reject</button>
            <?php endif; ?>
            <form method="GET" action="generate-receipt.php" target="_blank" style="display:inline;">
                <input type="hidden" name="saleID" value="<?= $sale['saleID'] ?>">
                <button type="submit" class="view">📄 View</button>
            </form>
            <button onclick="handleAction(<?= $sale['saleID'] ?>, 'delete')" class="delete">🗑 Delete</button>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<div class="no-data"><p>No sales records found.</p></div>
<?php endif; ?>

<!-- ✅ Modal for Approve Confirmation -->
<div id="approveModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Sale Approval</h3>
    <p>The following items will be deducted from inventory:</p>
    <table style="width:100%;border-collapse:collapse;" id="itemTable">
      <thead>
        <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
      </thead>
      <tbody></tbody>
    </table>
    <div style="margin-top:15px;text-align:right;">
      <button onclick="confirmApproval()" style="background:green;color:white;">✅ Confirm</button>
      <button onclick="closeModal()" style="background:red;color:white;">❌ Cancel</button>
    </div>
  </div>
</div>

<script>
let selectedSale = null;

function showApprovalModal(saleID) {
    selectedSale = saleID;
    fetch("sales-approval.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `ajax_action=get_items&saleID=${saleID}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector("#itemTable tbody");
            tbody.innerHTML = "";
            data.items.forEach(item => {
                tbody.innerHTML += `<tr>
                    <td>${item.productName}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.unitPrice).toFixed(2)}</td>
                </tr>`;
            });
            document.getElementById("approveModal").style.display = "flex";
        } else {
            alert("Failed to load items.");
        }
    });
}

function closeModal() {
    document.getElementById("approveModal").style.display = "none";
}

function confirmApproval() {
    if (!selectedSale) return;
    fetch("sales-approval.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `ajax_action=approve_confirmed&saleID=${selectedSale}`
    })
    .then(res => res.json())
    .then(data => {
        closeModal();
        if (data.success) {
            const row = document.getElementById(`row-${selectedSale}`);
            const statusCell = row.querySelector(".status");
            statusCell.textContent = "Approved";
            statusCell.className = "status status-approved";
            row.querySelectorAll(".approve-btn,.reject-btn").forEach(b => b.remove());
            alert("✅ Sale approved and inventory updated.");
        } else {
            alert("⚠️ " + data.error);
        }
    });
}

function handleAction(saleID, action) {
    if (action === 'delete' && !confirm(`Are you sure you want to delete Sale #${saleID}?`)) return;
    if (action === 'reject' && !confirm(`Reject Sale #${saleID}?`)) return;

    fetch("sales-approval.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `ajax_action=${action}&saleID=${saleID}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.deleted) {
                document.getElementById(`row-${saleID}`).remove();
                alert(`🗑 Sale #${saleID} deleted.`);
            } else {
                const row = document.getElementById(`row-${saleID}`);
                const statusCell = row.querySelector(".status");
                statusCell.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                statusCell.className = "status status-" + data.status;
                row.querySelectorAll(".approve-btn,.reject-btn").forEach(b => b.remove());
                alert(`Sale #${saleID} ${data.status}.`);
            }
        } else alert("⚠️ " + (data.error || "Something went wrong."));
    });
}
</script>
</body>
</html>
