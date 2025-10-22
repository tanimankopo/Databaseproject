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
    <title>Available Items - Cashier</title>
    <link rel="stylesheet" href="css/cashier.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="main-content">
        <div class="topbar">
            <h1>Available Items</h1>
        </div>

        <!-- Searchable Available Items -->
        <section>
            <h2>Available Items</h2>
            <input type="text" id="searchBox" placeholder="Search item...">
            <table id="itemTable" border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items = $conn->query("SELECT productName, price, stockQuantity FROM products ORDER BY productName ASC");
                    if ($items && $items->num_rows > 0) {
                        while($row = $items->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['productName']) . "</td>";
                            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                            echo "<td>" . $row['stockQuantity'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No products found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </div>

    <script>
    // 🔍 Search filter for available items
    $(document).ready(function(){
        $("#searchBox").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#itemTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    });
    </script>

</body>
</html>