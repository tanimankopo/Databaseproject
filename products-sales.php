<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Delete product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM products WHERE productID = ?");
    $stmt->bind_param("i", $deleteID);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit();
}

// ✅ Fetch products (oldest first)
$result = $conn->query("SELECT * FROM products ORDER BY dateAdded ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales - Products</title>
    <link rel="stylesheet" type="text/css" href="css/products.css">
</head>

<script>
function confirmDelete(productID, event) {
    if (!confirm("Are you sure you want to delete this product?")) return;

    fetch('products-sales.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            delete_product: '1',
            deleteID: productID
        })
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "success") {
            alert("🗑️ Product deleted successfully!");
            const row = event.target.closest('tr');
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            alert("❌ Error deleting product.");
        }
    })
    .catch(() => alert("⚠️ Error connecting to server."));
}
</script>

<body>
    <?php include("sidebar-sales.php") ?>

    <div class="main-content">
        <header class="topbar">
            <h1>📦 Product List</h1>
        </header>

        <div style="margin: 15px 0;">
            <input type="text" id="searchInput" placeholder="🔍 Search by name or category..." 
                style="padding:8px; width:300px; border:1px solid #ccc; border-radius:5px;">
        </div>

        <script>
        // ✅ Filter by Name or Category
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll(".products-table tbody tr").forEach(row => {
                let name = row.cells[2].textContent.toLowerCase();
                let category = row.cells[3].textContent.toLowerCase();
                row.style.display = (name.includes(filter) || category.includes(filter)) ? "" : "none";
            });
        });
        </script>

        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Supplier ID</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['productID']; ?></td>
                    <td>
                        <?php if(!empty($row['productsImg'])): ?>
                            <img src="<?= $row['productsImg']; ?>" style="width:60px; height:60px; object-fit:cover;">
                        <?php else: ?>
                            <span style="color:#999;">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['productName']); ?></td>
                    <td><?= htmlspecialchars($row['category']); ?></td>
                    <td>₱<?= number_format($row['price'], 2); ?></td>
                    <td><?= $row['stockQuantity']; ?></td>
                    <td><?= $row['supplierID']; ?></td>
                    <td><?= $row['dateAdded']; ?></td>
                    <td><button class="delete-btn" onclick="confirmDelete(<?= $row['productID']; ?>, event)">🗑 Delete</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
