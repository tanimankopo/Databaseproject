<?php
ob_start(); // Capture all output to prevent mixing with JSON

session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) {
    header("Location: login.php");
    exit();
}

// ✅ Check if this is an AJAX request (primary: header; fallback: POST params)
$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
          isset($_POST['add_to_cart']) || isset($_POST['remove_cart']) || isset($_POST['update_qty']) || isset($_POST['submit_sale']);

// Initialize cart session if not exists
if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

// ✅ Handle AJAX requests FIRST (no HTML/sidebar output)
if ($isAjax) {
    include "db.php"; // Include DB only for AJAX (after output buffering)

    // Handle Add to Cart
    if(isset($_POST['add_to_cart'])){
        $productID = intval($_POST['productID']);
        $quantity = intval($_POST['quantity']);

        $stmt = $conn->prepare("SELECT * FROM products WHERE productID = ?");
        $stmt->bind_param("i", $productID);
        $stmt->execute();
        $result = $stmt->get_result();

        if($product = $result->fetch_assoc()){
            $stockLeft = $product['stockQuantity'];

            $found = false;
            foreach($_SESSION['cart'] as &$item){
                if($item['productID'] == $productID){
                    $item['quantity'] = min($item['quantity'] + $quantity, $stockLeft);
                    $found = true;
                    break;
                }
            }
            unset($item);

            if(!$found){
                $_SESSION['cart'][] = [
                    'productID' => $productID,
                    'productName' => $product['productName'],
                    'price' => $product['price'],
                    'quantity' => min($quantity, $stockLeft),
                    'stockLeft' => $stockLeft
                ];
            }
        }

        ob_end_clean(); // Discard buffer
        header('Content-Type: application/json');
        echo json_encode(['status'=>'added']);
        exit();
    }

    // Handle Remove from Cart
    if(isset($_POST['remove_cart'])){
        $removeID = intval($_POST['removeID']);
        foreach($_SESSION['cart'] as $key => $item){
            if($item['productID'] == $removeID){
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status'=>'removed']);
        exit();
    }

    // Handle Update Quantity
    if(isset($_POST['update_qty'])){
        $updateID = intval($_POST['updateID']);
        $newQty = intval($_POST['newQty']);
        foreach($_SESSION['cart'] as &$item){
            if($item['productID'] == $updateID){
                $item['quantity'] = min($newQty, $item['stockLeft']);
                break;
            }
        }
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status'=>'updated']);
        exit();
    }

    // Handle Submit Sale → Pending Approval
    if(isset($_POST['submit_sale'])){
        $customerName = $_POST['customerName'];
        $paymentType = $_POST['paymentType'];
        $totalAmount = $_POST['totalAmount'];
        $cashier = $_POST['cashier'];
        $salesAccount = $_POST['salesAccount'];

        if(count($_SESSION['cart'])>0){
            // Lookup userID for cashier
            $userID = 0;
            $stmt = $conn->prepare("SELECT userID FROM usermanagement WHERE username = ?");
            $stmt->bind_param("s", $cashier);
            $stmt->execute();
            $result = $stmt->get_result();
            if($row = $result->fetch_assoc()){
                $userID = $row['userID'];
            }
            $stmt->close();

            // Insert sale master record (adjusted to match full sales table schema)
            $stmt = $conn->prepare("INSERT INTO sales (clientID, productID, userID, quantity, unitPrice, totalAmount, saleDate, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $status = 'pending';
            $clientID = 0; // Placeholder for customerName
            $productID = 0; // Placeholder for master record
            $quantity = 0; // Placeholder
            $unitPrice = 0; // Placeholder
            $stmt->bind_param("iiiidds", $clientID, $productID, $userID, $quantity, $unitPrice, $totalAmount, $status);
            if(!$stmt->execute()){
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['status'=>'error', 'message'=>'Failed to insert sale: ' . $stmt->error]);
                exit();
            }
            $saleID = $stmt->insert_id;
            $stmt->close();

            // Insert sale items
            $stmt = $conn->prepare("INSERT INTO sale_items (saleID, productID, productName, price, quantity) VALUES (?,?,?,?,?)");
            foreach($_SESSION['cart'] as $item){
                $stmt->bind_param("iisdi", $saleID, $item['productID'], $item['productName'], $item['price'], $item['quantity']);
                $stmt->execute();
            }
            $stmt->close();

            // Clear cart
            $_SESSION['cart'] = [];
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status'=>'submitted', 'saleID'=>$saleID]);
            exit();
        } else {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status'=>'empty']);
            exit();
        }
    }

    // Fallback for unmatched AJAX
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error', 'message'=>'Invalid AJAX request']);
    exit();
}

// ✅ Non-AJAX requests: Include DB and sidebar, then render HTML page
include "db.php";
include "sidebar-cashier.php";

// ✅ Categories and Pagination
$categories = ["All Items", "Engine & Transmission", "Braking System", "Suspension & Steering", "Electrical & Lighting", "Tires & Wheels"];
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'All Items';
$limit = 8;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page-1)*$limit;

// Count total products
if($selectedCategory != 'All Items'){
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

// Output HTML
ob_end_flush(); // Flush buffer for HTML output
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
            <span id="cartCountBadge"><?=count($_SESSION['cart'])?></span>
        </button>
    </div>

    <section>
        <h2>Product Inventory</h2>
        <input type="text" id="searchBox" placeholder="🔍 Search item..." style="padding:8px; width:300px; border:1px solid #ccc; border-radius:5px; margin-bottom:10px;">

        <!-- ✅ Category Filter -->
        <div style="margin:10px 0;">
            <?php foreach($categories as $cat): ?>
                <a href="?category=<?=urlencode($cat)?>" style="margin-right:10px; <?= $selectedCategory==$cat?'font-weight:bold;':'' ?>"><?=htmlspecialchars($cat)?></a>
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
                <?php
                if($items && $items->num_rows>0){
                    while($row=$items->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>".$row['productID']."</td>";
                        echo "<td>".(!empty($row['productsImg'])? "<img src='".htmlspecialchars($row['productsImg'])."' style='width:60px;height:60px;object-fit:cover;border-radius:8px;'>":"<span style='color:#999;'>No Image</span>")."</td>";
                        echo "<td>".htmlspecialchars($row['productName'])."</td>";
                        echo "<td>".htmlspecialchars($row['category'])."</td>";
                        echo "<td>₱".number_format($row['price'],2)."</td>";
                        echo "<td>".$row['stockQuantity']."</td>";
                        echo "<td>
                                <input type='number' class='cart-qty' value='1' min='1' max='".$row['stockQuantity']."' style='width:60px;'>
                                <button type='button' class='add-to-cart-btn' data-id='".$row['productID']."'>Add</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- ✅ Pagination -->
        <div style="margin-top:10px;">
            <?php if($page>1): ?>
                <a href="?page=<?= $page-1 ?>&category=<?=urlencode($selectedCategory)?>">&lt; Previous</a>
            <?php endif; ?>
            Page <?=$page?> of <?=$totalPages?>
            <?php if($page<$totalPages): ?>
                <a href="?page=<?= $page+1 ?>&category=<?=urlencode($selectedCategory)?>">Next &gt;</a>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Cart Modal -->
<div id="cartModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>🛒 Cart</h2>
        <p style="color:#555; font-size:14px;">You can continue adding items from the table. The cart updates automatically.</p>
        <div id="cartContent"></div>
    </div>
</div>

<script>
$(document).ready(function(){
    // 🔍 Search filter
    $("#searchBox").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#itemTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Open cart modal
    var modal = $("#cartModal");
    $("#openCartBtn").click(function(){ loadCart(); modal.show(); });
    $(".close").click(function(){ modal.hide(); });
    $(window).click(function(e){ if(e.target.id=="cartModal") modal.hide(); });

    function loadCart(){ $.get('get_cart.php', function(data){ $('#cartContent').html(data); }); }
    function updateBadge(){ $.get('get_count.php', function(count){ $('#cartCountBadge').text(count); }); }

    $(".add-to-cart-btn").click(function(){
        let productID = $(this).data("id");
        let quantity = $(this).siblings(".cart-qty").val();
        $.post("cashier-items.php", { add_to_cart: 1, productID: productID, quantity: quantity }, function(){
            loadCart();
            updateBadge();
        });
    });

    $(document).on('submit', '.update_qty_form', function(e){ e.preventDefault(); $.post('cashier-items.php', $(this).serialize(), function(){ loadCart(); updateBadge(); }); });
    $(document).on('submit', '.remove_cart_form', function(e){ e.preventDefault(); $.post('cashier-items.php', $(this).serialize(), function(){ loadCart(); updateBadge(); }); });
});
</script>
</body>
</html>