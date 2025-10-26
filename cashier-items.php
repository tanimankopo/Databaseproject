<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) {
    header("Location: login.php");
    exit();
}

include "db.php";
include "sidebar-cashier.php";

// Initialize cart session if not exists
if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

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

    // Return JSON for AJAX
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
    echo json_encode(['status'=>'updated']);
    exit();
}
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
                $items = $conn->query("SELECT * FROM products ORDER BY productName ASC");
                if($items && $items->num_rows > 0){
                    while($row = $items->fetch_assoc()){
                        echo "<tr>";
                        echo "<td>".$row['productID']."</td>";
                        echo "<td>";
                        if(!empty($row['productsImg'])) echo "<img src='".htmlspecialchars($row['productsImg'])."' style='width:60px; height:60px; object-fit:cover; border-radius:8px;'>";
                        else echo "<span style='color:#999;'>No Image</span>";
                        echo "</td>";
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
    $("#openCartBtn").click(function(){
        loadCart();
        modal.show();
    });
    $(".close").click(function(){ modal.hide(); });
    $(window).click(function(e){ if(e.target.id=="cartModal") modal.hide(); });

    // Load cart content via AJAX
    function loadCart(){
        $.get('get_cart.php', function(data){
            $('#cartContent').html(data);
        });
    }

    // Update cart badge
    function updateBadge(){
        $.get('get_count.php', function(count){
            $('#cartCountBadge').text(count);
        });
    }

    // Add to cart via AJAX
    $(".add-to-cart-btn").click(function(){
        let productID = $(this).data("id");
        let quantity = $(this).siblings(".cart-qty").val();
        $.post("cashier-items.php", { add_to_cart: 1, productID: productID, quantity: quantity }, function(){
            loadCart();
            updateBadge();
            
        });
    });

    // Update quantity via AJAX
      // Update quantity via AJAX
$(document).on('submit', '.update_qty_form', function(e){
    e.preventDefault();
    // $(this).serialize() sends all form fields, including the new hidden 'update_qty' field.
    $.post('cashier-items.php', $(this).serialize(), function(response){
        // You've correctly called loadCart() here to refresh the modal content
        loadCart(); 
        updateBadge();
    });
});

// Remove from cart via AJAX
// This section is also correct, assuming the class remove_cart_form is in get_cart.php
$(document).on('submit', '.remove_cart_form', function(e){
    e.preventDefault();
    // $(this).serialize() sends all form fields, including the new hidden 'remove_cart' field.
    $.post('cashier-items.php', $(this).serialize(), function(response){
        loadCart();
        updateBadge();
    });
});
});
</script>
</body>
</html>
