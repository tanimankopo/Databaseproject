<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Insert product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_product'])) {
    $productName   = $_POST['productName'];
    $category      = $_POST['category'];
    $price         = $_POST['price'];
    $stockQuantity = $_POST['stockQuantity'];
    $supplierID    = $_POST['supplierID'];

    // Handle image upload
    $productsImg = "";
    if (isset($_FILES['productsImg']) && $_FILES['productsImg']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $productsImg = $targetDir . basename($_FILES["productsImg"]["name"]);
        move_uploaded_file($_FILES["productsImg"]["tmp_name"], $productsImg);
    }

    $stmt = $conn->prepare("INSERT INTO products (productsImg, productName, category, price, stockQuantity, supplierID, dateAdded) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssdis", $productsImg, $productName, $category, $price, $stockQuantity, $supplierID);
    $stmt->execute();


    header("Location: products.php");
    exit();
}

// ✅ Delete product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM products WHERE productID = ?");
    $stmt->bind_param("i", $deleteID);

    if ($stmt->execute()) {
        echo "<script>alert('🗑️ Product deleted successfully!'); window.location='products.php';</script>";
    } else {
        echo "<script>alert('❌ Error deleting product.'); window.location='products.php';</script>";
    }

    $stmt->close();
}

// ✅ Fetch products (oldest first)
$result = $conn->query("SELECT * FROM products ORDER BY dateAdded ASC");
   

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_product'])) {
    $updateID      = intval($_POST['updateID']);
    $productName   = $_POST['updateName'];
    $category      = $_POST['updateCategory'];
    $price         = $_POST['updatePrice'];
    $stockQuantity = $_POST['updateStock'];
    $supplierID    = $_POST['updateSupplier'];

    // Check if ID exists
    $check = $conn->prepare("SELECT productID FROM products WHERE productID = ?");
    $check->bind_param("i", $updateID);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo "<script>alert('❌ Product ID does not exist!'); window.location='products.php';</script>";
        $check->close();
        exit;
    }
    $check->close();

    // Handle image upload
    $productsImg = "";
    if (isset($_FILES['updateImg']) && $_FILES['updateImg']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $productsImg = $targetDir . basename($_FILES["updateImg"]["name"]);
        move_uploaded_file($_FILES["updateImg"]["tmp_name"], $productsImg);
    }

    if (!empty($productsImg)) {
        $stmt = $conn->prepare("UPDATE products 
                                SET productsImg=?, productName=?, category=?, price=?, stockQuantity=?, supplierID=? 
                                WHERE productID=?");
        $stmt->bind_param("sssdisi", $productsImg, $productName, $category, $price, $stockQuantity, $supplierID, $updateID);
    } else {
        $stmt = $conn->prepare("UPDATE products 
                                SET productName=?, category=?, price=?, stockQuantity=?, supplierID=? 
                                WHERE productID=?");
        $stmt->bind_param("ssdisi", $productName, $category, $price, $stockQuantity, $supplierID, $updateID);
    }

    if ($stmt->execute()) {
        echo "<script>alert('✅ Product updated successfully!'); window.location='products.php';</script>";
    } else {
        echo "<script>alert('❌ Error updating product.'); window.location='products.php';</script>";
    }

    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" type="text/css" href="css/products.css">
    
</head>
<body>

    <?php
            include("sidebar-admin.php")
    ?>

    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <h1>📦 Products</h1>
            <div class="settings-menu">
                <button class="settings-btn">&#9776;</button>
                <div class="settings-dropdown">
                    <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add Product</button>
                    <button class="delete-btn" onclick="document.getElementById('deleteModal').style.display='flex'">🗑 Delete Product</button>
                    <button class="update-btn" onclick="document.getElementById('updateModal').style.display='flex'">✏ Update Product</button>>
                </div>
            </div>
        </header>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>ProductName</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>QTY</th>
                    <th>SupplierID</th>
                    <th>DateAdded</th>
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
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Product Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h3>Add New Product</h3>
            <form method="POST" action="products.php" enctype="multipart/form-data">
                <label>Product Image:</label>
                <input type="file" name="productsImg" accept="image/*" required><br>

                <label>Product Name:</label>
                <input type="text" name="productName" required><br>

                <label>Category:</label>
                <select name="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="Engine & Transmission">Engine & Transmission</option>
                    <option value="Braking System">Braking System</option>
                    <option value="Suspension & Steering">Suspension & Steering</option>
                    <option value="Electrical & Lighting">Electrical & Lighting</option>
                    <option value="Tires & Wheels">Tires & Wheels</option>
                </select><br>

                <label>Price:</label>
                <input type="number" step="0.01" name="price" required><br>

                <label>Stock Quantity:</label>
                <input type="number" name="stockQuantity" required><br>

                <label>Supplier ID:</label>
                <input type="number" name="supplierID"><br>

                <button type="submit" name="add_product">Save</button>
                <button type="button" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3>Delete Product</h3>
            <form method="POST" action="products.php">
                <label>Enter Product ID to Delete:</label>
                <input type="number" name="deleteID" required><br>
                <button type="submit" name="delete_product">Delete</button>
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

            <!-- Update Product Modal -->
            <div class="modal" id="updateModal">
                <div class="modal-content">
                    <h3>Update Product</h3>
                    <form method="POST" action="products.php" enctype="multipart/form-data">
                        <label>Product ID (to update):</label>
                        <input type="number" name="updateID" required><br>

                        <label>New Product Image:</label>
                        <input type="file" name="updateImg" accept="image/*"><br>

                        <label>New Product Name:</label>
                        <input type="text" name="updateName"><br>

                        <label>New Category:</label>
                        <select name="updateCategory">
                            <option value="">-- Select Category --</option>
                            <option value="Engine & Transmission">Engine & Transmission</option>
                            <option value="Braking System">Braking System</option>
                            <option value="Suspension & Steering">Suspension & Steering</option>
                            <option value="Electrical & Lighting">Electrical & Lighting</option>
                            <option value="Tires & Wheels">Tires & Wheels</option>
                        </select><br>

                        <label>New Price:</label>
                        <input type="number" step="0.01" name="updatePrice"><br>

                        <label>New Stock Quantity:</label>
                        <input type="number" name="updateStock"><br>

                        <label>New Supplier ID:</label>
                        <input type="number" name="updateSupplier"><br>

                        <button type="submit" name="update_product">Update</button>
                        <button type="button" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
                    </form>
                </div>
            </div>







    <script>
        // Toggle settings dropdown
        document.querySelector(".settings-btn").addEventListener("click", function() {
            document.querySelector(".settings-menu").classList.toggle("show");
        });
    </script>

</body>
</html>
