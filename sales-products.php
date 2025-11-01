<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Add Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_product'])) {
    $name = $_POST['productName'];
    $cat = $_POST['category'];
    $price = $_POST['price'];
    $qty = $_POST['stockQuantity'];
    $supplier = $_POST['supplierID'];
    $imgPath = "";

    if (isset($_FILES['productsImg']) && $_FILES['productsImg']['error'] == 0) {
        $target = "uploads/";
        if (!is_dir($target)) mkdir($target, 0777, true);
        $imgPath = $target . basename($_FILES['productsImg']['name']);
        move_uploaded_file($_FILES['productsImg']['tmp_name'], $imgPath);
    }

    $stmt = $conn->prepare("INSERT INTO products (productsImg, productName, category, price, stockQuantity, supplierID, dateAdded)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssdis", $imgPath, $name, $cat, $price, $qty, $supplier);
    $stmt->execute();
    header("Location: sales-products.php");
    exit();
}

// ✅ Delete Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {
    $id = intval($_POST['deleteID']);
    $stmt = $conn->prepare("DELETE FROM products WHERE productID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: sales-products.php");
    exit();
}

// ✅ Update Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_product'])) {
    $id = intval($_POST['updateID']);
    $name = $_POST['updateName'];
    $cat = $_POST['updateCategory'];
    $price = $_POST['updatePrice'];
    $qty = $_POST['updateStock'];
    $supplier = $_POST['updateSupplier'];

    $imgPath = "";
    if (isset($_FILES['updateImg']) && $_FILES['updateImg']['error'] == 0) {
        $target = "uploads/";
        if (!is_dir($target)) mkdir($target, 0777, true);
        $imgPath = $target . basename($_FILES['updateImg']['name']);
        move_uploaded_file($_FILES['updateImg']['tmp_name'], $imgPath);
    }

    if (!empty($imgPath)) {
        $stmt = $conn->prepare("UPDATE products SET productsImg=?, productName=?, category=?, price=?, stockQuantity=?, supplierID=? WHERE productID=?");
        $stmt->bind_param("sssdisi", $imgPath, $name, $cat, $price, $qty, $supplier, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET productName=?, category=?, price=?, stockQuantity=?, supplierID=? WHERE productID=?");
        $stmt->bind_param("ssdisi", $name, $cat, $price, $qty, $supplier, $id);
    }
    $stmt->execute();
    header("Location: sales-products.php");
    exit();
}

// ✅ Pagination & filters
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where = "WHERE 1";
$params = [];
$types = "";

if (!empty($category) && $category !== "All Items") {
    $where .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
if (!empty($search)) {
    $where .= " AND productName LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$sql = "SELECT * FROM products $where ORDER BY category ASC, dateAdded DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$countQuery = "SELECT COUNT(*) as total FROM products $where";
$countStmt = $conn->prepare($countQuery);
if ($types !== "ii") {
    $bindTypes = substr($types, 0, -2);
    $countStmt->bind_param($bindTypes, ...array_slice($params, 0, -2));
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// ✅ Category list (with "All Items")
$categories = [
    "All Items",
    "Engine & Transmission",
    "Braking System",
    "Suspension & Steering",
    "Electrical & Lighting",
    "Tires & Wheels"
];
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Products</title>
    <link rel="stylesheet" type="text/css" href="css/products.css">
</head>
<body>
    
    <?php 
    include("sales-sidebar.php")
     ?>

    <div class="main-content">
        <header class="topbar">
            <h1>📦 Product Management</h1>
            <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add Product</button>
        </header>

        <section>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">

                    <!-- CATEGORY FILTER (auto-submit on change) -->
                    <form method="GET" style="margin: 0;">
                        <select name="category"
                                onchange="this.form.submit()"
                                style="padding:8px; border:1px solid #ccc; border-radius:5px;">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"
                                    <?= ($cat === $category) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- preserve search if user has typed something -->
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                    </form>

                    <!-- SEARCH BAR (for specific product name) -->
                    <form method="GET" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                        <!-- preserve selected category -->
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">

                        <input type="text" name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="🔍 Search by product name..."
                            style="padding:8px; width:300px; border:1px solid #ccc; border-radius:5px;">

                        <button type="submit"
                            style="padding:8px 15px; border:none; background:#007bff; color:white; border-radius:5px; cursor:pointer;">
                            🔎 FIND
                        </button>
                    </form>

                </div>
                    
                                  
        </section>

        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Supplier ID</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['productID']; ?></td>
                            <td>
                                <?php if(!empty($row['productsImg'])): ?>
                                    <img src="<?= $row['productsImg']; ?>" style="width:60px; height:60px; object-fit:cover;">
                                <?php else: ?>
                                    <span>No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['productName']); ?></td>
                            <td>₱<?= number_format($row['price'], 2); ?></td>
                            <td><?= $row['stockQuantity']; ?></td>
                            <td><?= $row['supplierID']; ?></td>
                            <td><?= $row['dateAdded']; ?></td>
                            <td>
                                <button type="button"
                                    onclick="openUpdateModal('<?= $row['productID']; ?>','<?= htmlspecialchars($row['productName']); ?>','<?= htmlspecialchars($row['category']); ?>','<?= $row['price']; ?>','<?= $row['stockQuantity']; ?>','<?= $row['supplierID']; ?>')">✏ Update</button>
                                
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top:10px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>">&lt; Previous</a>
            <?php endif; ?>

            Page <?= $page ?> of <?= $totalPages ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>">Next &gt;</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h3>Add Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="productsImg" accept="image/*" required><br>
                <input type="text" name="productName" placeholder="Name" required><br>
                <select name="category" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach (array_slice($categories, 1) as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="number" step="0.01" name="price" placeholder="Price" required><br>
                <input type="number" name="stockQuantity" placeholder="Quantity" required><br>
                <input type="number" name="supplierID" placeholder="Supplier ID"><br>
                <button type="submit" name="add_product">Save</button>
                <button type="button" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Update Product Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <h3>Update Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="updateID" id="updateID">
                <input type="file" name="updateImg" accept="image/*"><br>
                <input type="text" name="updateName" id="updateName" placeholder="Name"><br>
                <select name="updateCategory" id="updateCategory">
                    <option value="">-- Select Category --</option>
                    <?php foreach (array_slice($categories, 1) as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="number" step="0.01" name="updatePrice" id="updatePrice" placeholder="Price"><br>
                <input type="number" name="updateStock" id="updateStock" placeholder="Quantity"><br>
                <input type="number" name="updateSupplier" id="updateSupplier" placeholder="Supplier ID"><br>
                <button type="submit" name="update_product">Update</button>
                <button type="button" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
    function openUpdateModal(id, name, cat, price, qty, sup) {
        document.getElementById('updateModal').style.display = 'flex';
        document.getElementById('updateID').value = id;
        document.getElementById('updateName').value = name;
        document.getElementById('updateCategory').value = cat;
        document.getElementById('updatePrice').value = price;
        document.getElementById('updateStock').value = qty;
        document.getElementById('updateSupplier').value = sup;
    }
    </script>
</body>
</html>
