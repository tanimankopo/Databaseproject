<?php
ob_start();
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) {
    header("Location: login.php");
    exit();
}

// ✅ Check if this is an AJAX request
$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || isset($_POST['add_to_cart']) || isset($_POST['remove_cart']) || isset($_POST['update_qty']) || isset($_POST['submit_sale']);

// Initialize cart session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ✅ Handle AJAX requests
if ($isAjax) {
    include "db.php";

    // 🔹 Add to Cart
    if (isset($_POST['add_to_cart'])) {
        $productID = intval($_POST['productID']);
        $quantity = intval($_POST['quantity']);

        $stmt = $conn->prepare("SELECT productID, productName, price, stockQuantity FROM products WHERE productID = ?");
        $stmt->bind_param("i", $productID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($product = $result->fetch_assoc()) {
            $stock = $product['stockQuantity'];
            $found = false;

            foreach ($_SESSION['cart'] as &$item) {
                if ($item['productID'] == $productID) {
                    $item['quantity'] = min($item['quantity'] + $quantity, $stock);
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found) {
                $_SESSION['cart'][] = [
                    'productID' => $product['productID'],
                    'productName' => $product['productName'],
                    'price' => $product['price'],
                    'quantity' => min($quantity, $stock),
                    'stockQuantity' => $stock
                ];
            }
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'added']);
        exit();
    }

    // 🔹 Remove from Cart
    if (isset($_POST['remove_cart'])) {
        $removeID = intval($_POST['removeID']);
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['productID'] == $removeID) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'removed']);
        exit();
    }

    // 🔹 Update Quantity
    if (isset($_POST['update_qty'])) {
        $updateID = intval($_POST['updateID']);
        $newQty = intval($_POST['newQty']);
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['productID'] == $updateID) {
                $item['quantity'] = min($newQty, $item['stockQuantity']);
                break;
            }
        }
        unset($item);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'updated']);
        exit();
    }

    // 🔹 Submit Sale
if (isset($_POST['submit_sale'])) {
    // 1️⃣ Collect POST data
    $customerName = trim($_POST['customerName']);
    $paymentType  = $_POST['paymentType'];
    $totalAmount  = floatval($_POST['totalAmount']);
    $cashier      = $_POST['cashier'];
    $salesAccount = $_POST['salesAccount'];

    if (count($_SESSION['cart']) > 0) {

        // --- Get cashier ID ---
        $stmt = $conn->prepare("SELECT userID FROM usermanagement WHERE username = ?");
        $stmt->bind_param("s", $cashier);
        $stmt->execute();
        $cashierID = $stmt->get_result()->fetch_assoc()['userID'] ?? 0;
        $stmt->close();

        // --- Get sales account ID ---
        $stmt = $conn->prepare("SELECT userID FROM usermanagement WHERE username = ?");
        $stmt->bind_param("s", $salesAccount);
        $stmt->execute();
        $salesAccountID = $stmt->get_result()->fetch_assoc()['userID'] ?? 0;
        $stmt->close();

        // --- Insert Sale Record (Header) ---
        $status = 'pending';
        $stmt = $conn->prepare(
            "INSERT INTO sales (salesAccountID, cashierID, customerName, totalAmount, saleDate, status) 
             VALUES (?, ?, ?, ?, NOW(), ?)"
        );
        $stmt->bind_param("iisds", $salesAccountID, $cashierID, $customerName, $totalAmount, $status);

        if (!$stmt->execute()) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert sale: ' . $stmt->error]);
            exit();
        }

        $saleID = $stmt->insert_id;
        $stmt->close();

        // --- Insert Sale Items (Details) ---
        $stmt_items = $conn->prepare(
            "INSERT INTO sale_items (saleID, productID, quantity, unitPrice, lineTotal) VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($_SESSION['cart'] as $item) {
            $productID = $item['productID'];
            $quantity  = $item['quantity'];
            $unitPrice = $item['price'];
            $lineTotal = $unitPrice * $quantity;

            $stmt_items->bind_param("iiddi", $saleID, $productID, $quantity, $unitPrice, $lineTotal);
            $stmt_items->execute(); // Let failures pass silently
        }
        $stmt_items->close();

        // --- Clear Cart & Return JSON ---
        $_SESSION['cart'] = [];
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'submitted', 'saleID' => $saleID]);
        exit();

    } else {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'empty']);
        exit();
    }
}

// --- Fallback for invalid AJAX request ---
ob_end_clean();
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Invalid AJAX request']);
exit();

}

// ✅ Non-AJAX: render HTML
include "db.php";
include "sidebar-cashier.php";

$categories = ["All Items", "Engine & Transmission", "Braking System", "Suspension & Steering", "Electrical & Lighting", "Tires & Wheels"];
$selectedCategory = $_GET['category'] ?? 'All Items';
$limit = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

if ($selectedCategory != 'All Items') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category=?");
    $stmt->bind_param("s", $selectedCategory);
    $stmt->execute();
    $stmt->bind_result($totalProducts);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM products WHERE category=? ORDER BY productName ASC LIMIT ?, ?");
    $stmt->bind_param("sii", $selectedCategory, $offset, $limit);
    $stmt->execute();
    $items = $stmt->get_result();
} else {
    $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    $stmt = $conn->prepare("SELECT * FROM products ORDER BY productName ASC LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $items = $stmt->get_result();
}

$totalPages = ceil($totalProducts / $limit);
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Items - Cashier</title>
    <link rel="stylesheet" href="css/cashier.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .modal {display:none; position:fixed; z-index:999; left:0; top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.4);}
        .modal-content {background:#fff;margin:5% auto;padding:20px;border-radius:10px;width:90%;max-width:900px; position:relative;}
        .close {position:absolute;top:10px;right:20px;font-size:24px;font-weight:bold;cursor:pointer;}
        table {width:100%; border-collapse:collapse; margin-top:10px;}
        th, td {border:1px solid #ccc; padding:8px; text-align:center;}
        #cartCountBadge {position:absolute; top:-8px; right:-8px; background:red; color:white; border-radius:50%; padding:2px 6px; font-size:12px;}
        button {cursor:pointer;}
    </style>
</head>
<body>
<div class="main-content">
    <div class="topbar">
        <h1>📦 Available Items</h1>
        <button id="openCartBtn" style="float:right; position:relative; padding:6px 12px;">
            🛒 View Cart
            <span id="cartCountBadge"><?= count($_SESSION['cart']) ?></span>
        </button>
    </div>

    <section>
        <h2>Product Inventory</h2>
        <input type="text" id="searchBox" placeholder="🔍 Search item..." style="padding:8px; width:300px; border:1px solid #ccc; border-radius:5px; margin-bottom:10px;">

        <div style="margin:10px 0;">
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat) ?>" style="margin-right:10px; <?= $selectedCategory == $cat ? 'font-weight:bold;' : '' ?>"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <table id="itemTable">
            <thead style="background:#f2f2f2;">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Add to Cart</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items && $items->num_rows > 0): ?>
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['productID'] ?></td>
                            <td><?= !empty($row['productsImg']) ? "<img src='".htmlspecialchars($row['productsImg'])."' style='width:60px;height:60px;object-fit:cover;border-radius:8px;'>" : "<span style='color:#999;'>No Image</span>" ?></td>
                            <td><?= htmlspecialchars($row['productName']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>₱<?= number_format($row['price'], 2) ?></td>
                            <td><?= $row['stockQuantity'] ?></td>
                            <td>
                                <input type="number" class="cart-qty" value="1" min="1" max="<?= $row['stockQuantity'] ?>" style="width:60px;">
                                <button type="button" class="add-to-cart-btn" data-id="<?= $row['productID'] ?>">Add</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top:10px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&category=<?= urlencode($selectedCategory) ?>">&lt; Previous</a>
            <?php endif; ?>
            Page <?= $page ?> of <?= $totalPages ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&category=<?= urlencode($selectedCategory) ?>">Next &gt;</a>
            <?php endif; ?>
        </div>
    </section>
</div>

<div id="cartModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>🛒 Cart</h2>
        <div id="cartContent"></div>
    </div>
</div>

<script>
$(document).ready(function(){
    const modal = $("#cartModal");
    $("#openCartBtn").click(function(){ loadCart(); modal.show(); });
    $(".close").click(function(){ modal.hide(); });
    $(window).click(function(e){ if(e.target.id=="cartModal") modal.hide(); });

    function loadCart(){ $.get('get_cart.php', function(data){ $('#cartContent').html(data); }); }
    function updateBadge(){ $.get('get_count.php', function(count){ $('#cartCountBadge').text(count); }); }

    $(".add-to-cart-btn").click(function(){
        const productID = $(this).data("id");
        const quantity = $(this).siblings(".cart-qty").val();
        $.post("cashier-items.php", { add_to_cart: 1, productID, quantity }, function(){
            loadCart();
            updateBadge();
        });
    });

    $(document).on('submit', '.update_qty_form', function(e){
        e.preventDefault();
        $.post('cashier-items.php', $(this).serialize(), function(){ loadCart(); updateBadge(); });
    });

    $(document).on('submit', '.remove_cart_form', function(e){
        e.preventDefault();
        $.post('cashier-items.php', $(this).serialize(), function(){ loadCart(); updateBadge(); });
    });
});
</script>
</body>
</html>