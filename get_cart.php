<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== "Cashier") exit();

include "db.php"; // $conn should be defined
?>

<?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Actions</th>
        </tr>
        <?php 
        $total = 0;
        foreach($_SESSION['cart'] as $item):
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
        ?>
        <tr>
            <td><?=htmlspecialchars($item['productName'])?></td>
            <td>₱<?=number_format($item['price'],2)?></td>
            <td>
                <form class="update_qty_form" method="POST" style="display:inline;"> 
                    <input type="hidden" name="update_qty" value="1"> 
                    <input type="hidden" name="updateID" value="<?=$item['productID']?>">
                    <input type="number" name="newQty" value="<?=$item['quantity']?>" min="1" max="<?=$item['stockLeft']?>" style="width:60px;">
                    <button type="submit">Update</button>
                </form>
            </td>
            <td>₱<?=number_format($subtotal,2)?></td>
            <td>
                <form class="remove_cart_form" method="POST" style="display:inline;"> 
                    <input type="hidden" name="remove_cart" value="1"> 
                    <input type="hidden" name="removeID" value="<?=$item['productID']?>"> 
                    <button type="submit">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3"><strong>Total:</strong></td>
            <td colspan="2"><strong>₱<?=number_format($total,2)?></strong></td>
        </tr>
    </table>
    <br>

    <!-- Submit Sale & Generate PDF -->
    <form id="submitSaleForm">
        <input type="hidden" name="totalAmount" value="<?=$total?>">
        <input type="hidden" name="cashier" value="<?=htmlspecialchars($_SESSION['username'])?>">
        <input type="hidden" name="paymentType" value="cash">

        <!-- Buyer Name -->
        <label>Buyer Name:</label>
        <input type="text" name="customerName" placeholder="Enter buyer's name" required style="margin-bottom:10px; padding:5px; width:200px;"><br>

        <!-- Sales Account -->
        <label>Send To (Sales Account):</label>
        <select name="salesAccount" required>
            <?php
            $sales = $conn->query("SELECT username FROM usermanagement WHERE LOWER(role)='sales'");
            if($sales && $sales->num_rows > 0){
                while($s = $sales->fetch_assoc()){
                    echo "<option value='".htmlspecialchars($s['username'])."'>".htmlspecialchars($s['username'])."</option>";
                }
            } else {
                echo "<option disabled>No sales accounts found</option>";
            }
            ?>
        </select>

        <br><br>
        <button type="submit">Submit Sale & Generate PDF Receipt</button>
    </form>

<?php else: ?>
    <p>Cart is empty.</p>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$("#submitSaleForm").submit(function(e){
    e.preventDefault();

    // Ensure cart is not empty
    if(<?=isset($_SESSION['cart']) && count($_SESSION['cart'])?> <= 0){
        alert("❌ Cart is empty.");
        return;
    }

    $.post('cashier-items.php', $(this).serialize()+'&submit_sale=1', function(res){
        let data;
        try { data = JSON.parse(res); } 
        catch(err) { alert("❌ Invalid response from server."); return; }

        if(data.status=='submitted'){
            alert("✅ Sale submitted. Sale ID: "+data.saleID);
            loadCart();
            updateBadge();
            // Open PDF receipt
            window.open('generate-receipt.php?saleID='+data.saleID,'_blank');
        } else if(data.status=='empty'){
            alert("❌ Cart is empty.");
        } else {
            alert("❌ "+(data.message || "Something went wrong."));
        }
    });
});

// Optional: functions to refresh cart and badge
function loadCart(){ $.get('get_cart.php', function(data){ $('#cartContent').html(data); }); }
function updateBadge(){ $.get('get_count.php', function(count){ $('#cartCountBadge').text(count); }); }
</script>
