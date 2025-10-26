// get_cart.php

<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) exit();
?>

<?php if(count($_SESSION['cart']) > 0): ?>
    <table>
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
    <form action="generate-receipt.php" method="POST" target="_blank">
        <input type="hidden" name="totalAmount" value="<?=$total?>">
        <label>Customer Name:</label>
        <input type="text" name="customerName" required>
        <input type="hidden" name="paymentType" value="cash">
        <button type="submit">Generate PDF Receipt</button>
    </form>
<?php else: ?>
    <p>Cart is empty.</p>
<?php endif; ?>