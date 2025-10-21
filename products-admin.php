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

    header("Location: products-admin.php");
    exit();
}

// ✅ Delete product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_product'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM products WHERE productID = ?");
    $stmt->bind_param("i", $deleteID);

    if ($stmt->execute()) {
        echo "<script>alert('🗑️ Product deleted successfully!'); window.location='products-admin.php';</script>";
    } else {
        echo "<script>alert('❌ Error deleting product.'); window.location='products-admin.php';</script>";
    }

    $stmt->close();
}

// ✅ Update product
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
        echo "<script>alert('❌ Product ID does not exist!'); window.location='products-admin.php';</script>";
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
        echo "<script>alert('✅ Product updated successfully!'); window.location='products-admin.php';</script>";
    } else {
        echo "<script>alert('❌ Error updating product.'); window.location='products-admin.php';</script>";
    }

    $stmt->close();
}

// ✅ Get statistics
$totalProductsResult = $conn->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $totalProductsResult->fetch_assoc()['total'];

$categoriesResult = $conn->query("SELECT COUNT(DISTINCT category) as categories FROM products");
$totalCategories = $categoriesResult->fetch_assoc()['categories'];

$avgPriceResult = $conn->query("SELECT AVG(price) as avgPrice FROM products");
$avgPrice = $avgPriceResult->fetch_assoc()['avgPrice'];

$lowStockResult = $conn->query("SELECT COUNT(*) as lowStock FROM products WHERE stockQuantity <= 3");
$lowStockCount = $lowStockResult->fetch_assoc()['lowStock'];

// ✅ Fetch products
$result = $conn->query("SELECT * FROM products ORDER BY dateAdded DESC");
if (!$result) {
    die("❌ Error fetching products: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - 1-GARAGE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1a1d29;
            color: #e4e7eb;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background: #252836;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }

        .logo {
            padding: 0 20px 30px;
            font-size: 20px;
            font-weight: bold;
            color: #fff;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li {
            margin: 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #b0b3ba;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: #2d303e;
            color: #fff;
        }

        .nav-links a.active {
            background: #3b3f51;
            color: #fff;
            border-left: 3px solid #5c9eff;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
        }

        .sidebar-footer button {
            width: 100%;
            padding: 12px;
            background: #ff6b6b;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .sidebar-footer button:hover {
            background: #ff5252;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #5c9eff;
            color: #fff;
        }

        .btn-primary:hover {
            background: #4a8de8;
        }

        .btn-secondary {
            background: #3b3f51;
            color: #e4e7eb;
        }

        .btn-secondary:hover {
            background: #4a5061;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .stat-card h3 {
            color: #b0b3ba;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stat-trend {
            font-size: 12px;
            color: #4caf50;
        }

        .products-section {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 18px;
        }

        .view-toggle {
            display: flex;
            gap: 8px;
        }

        .view-btn {
            padding: 8px 12px;
            background: #2d303e;
            border: none;
            border-radius: 6px;
            color: #b0b3ba;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-btn.active {
            background: #5c9eff;
            color: #fff;
        }

        .search-filter {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        .filter-select {
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
            cursor: pointer;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: #2d303e;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #3b3f51;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: #3b3f51;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            margin-bottom: 15px;
            object-fit: cover;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .product-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .product-sku {
            font-size: 12px;
            color: #b0b3ba;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.in-stock {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .badge.low-stock {
            background: rgba(255, 167, 38, 0.2);
            color: #ffa726;
        }

        .badge.out-of-stock {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .product-details {
            margin: 15px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .detail-label {
            color: #b0b3ba;
        }

        .detail-value {
            font-weight: 600;
        }

        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #5c9eff;
            margin: 15px 0;
        }

        .product-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            background: #3b3f51;
            border: none;
            border-radius: 6px;
            color: #e4e7eb;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #4a5061;
        }

        .stock-indicator {
            width: 100%;
            height: 4px;
            background: #3b3f51;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }

        .stock-fill {
            height: 100%;
            background: #4caf50;
            transition: width 0.3s;
        }

        .stock-fill.low {
            background: #ffa726;
        }

        .stock-fill.critical {
            background: #ff6b6b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #252836;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            font-size: 24px;
        }

        .close-btn {
            background: none;
            border: none;
            color: #b0b3ba;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b0b3ba;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include("sidebar-admin.php"); ?>

        <main class="main-content">
            <div class="header">
                <h1><span>📦</span> Products Management</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary">
                        <span>📥</span> Import
                    </button>
                    <button class="btn btn-secondary">
                        <span>📤</span> Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <span>+</span> Add Product
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="stat-value"><?= $totalProducts ?></div>
                    <div class="stat-trend">Products in inventory</div>
                </div>
                <div class="stat-card">
                    <h3>Categories</h3>
                    <div class="stat-value"><?= $totalCategories ?></div>
                    <div class="stat-trend">Active categories</div>
                </div>
                <div class="stat-card">
                    <h3>Avg. Price</h3>
                    <div class="stat-value">₱<?= number_format($avgPrice, 2) ?></div>
                    <div class="stat-trend">Per product</div>
                </div>
                <div class="stat-card">
                    <h3>Low Stock</h3>
                    <div class="stat-value"><?= $lowStockCount ?></div>
                    <div class="stat-trend">Items need attention</div>
                </div>
            </div>

            <div class="products-section">
                <div class="section-header">
                    <h2>Product Catalog</h2>
                    <div class="view-toggle">
                        <button class="view-btn active">🔲 Grid</button>
                        <button class="view-btn">📋 List</button>
                    </div>
                </div>

                <div class="search-filter">
                    <input type="text" class="search-box" placeholder="Search products by name, SKU, or category..." id="searchBox">
                    <select class="filter-select" id="categoryFilter">
                        <option>All Categories</option>
                        <?php
                        $categoriesResult->data_seek(0);
                        $categories = [];
                        while($row = $categoriesResult->fetch_assoc()) {
                            if (!in_array($row['category'], $categories)) {
                                $categories[] = $row['category'];
                                echo "<option>" . htmlspecialchars($row['category']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <select class="filter-select" id="stockFilter">
                        <option>All Status</option>
                        <option>In Stock</option>
                        <option>Low Stock</option>
                        <option>Out of Stock</option>
                    </select>
                    <select class="filter-select">
                        <option>Sort: Newest</option>
                        <option>Sort: Price Low-High</option>
                        <option>Sort: Price High-Low</option>
                        <option>Sort: Name A-Z</option>
                        <option>Sort: Best Selling</option>
                    </select>
                </div>

                <div class="products-grid">
                    <?php while($row = $result->fetch_assoc()): 
                        $stockLevel = $row['stockQuantity'];
                        $maxStock = 100; // You can adjust this based on your business logic
                        $stockPercentage = ($stockLevel / $maxStock) * 100;
                        
                        $status = 'in-stock';
                        $statusText = 'In Stock';
                        $stockFillClass = '';
                        
                        if ($stockLevel == 0) {
                            $status = 'out-of-stock';
                            $statusText = 'Out of Stock';
                            $stockFillClass = 'critical';
                        } elseif ($stockLevel <= 3) {
                            $status = 'low-stock';
                            $statusText = 'Low Stock';
                            $stockFillClass = 'low';
                        }
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($row['productsImg'])): ?>
                                <img src="<?= htmlspecialchars($row['productsImg']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;" alt="Product">
                        <?php else: ?>
                                📦
                        <?php endif; ?>
                        </div>
                        <div class="product-header">
                            <div>
                                <div class="product-title"><?= htmlspecialchars($row['productName']) ?></div>
                                <div class="product-sku">SKU: P-<?= $row['productID'] ?></div>
                            </div>
                            <span class="badge <?= $status ?>"><?= $statusText ?></span>
                        </div>
                        <div class="product-details">
                            <div class="detail-row">
                                <span class="detail-label">Category:</span>
                                <span class="detail-value"><?= htmlspecialchars($row['category']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Stock:</span>
                                <span class="detail-value" style="color: <?= $stockLevel == 0 ? '#ff6b6b' : ($stockLevel <= 3 ? '#ffa726' : '#4caf50') ?>;"><?= $stockLevel ?> / <?= $maxStock ?> units</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Supplier:</span>
                                <span class="detail-value">ID: <?= $row['supplierID'] ?></span>
                            </div>
                        </div>
                        <div class="stock-indicator">
                            <div class="stock-fill <?= $stockFillClass ?>" style="width: <?= min($stockPercentage, 100) ?>%;"></div>
                        </div>
                        <div class="product-price">₱<?= number_format($row['price'], 2) ?></div>
                        <div class="product-actions">
                            <button class="action-btn" onclick="openUpdateModal(<?= $row['productID'] ?>)">📝 Edit</button>
                            <button class="action-btn" onclick="openDeleteModal(<?= $row['productID'] ?>)">🗑️ Delete</button>
                            <button class="action-btn">👁️ View</button>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="products-admin.php" enctype="multipart/form-data">
                <div class="form-group">
                <label>Product Image:</label>
                    <input type="file" name="productsImg" accept="image/*" class="form-input">
                </div>
                <div class="form-group">
                <label>Product Name:</label>
                    <input type="text" name="productName" class="form-input" required>
                </div>
                <div class="form-group">
                <label>Category:</label>
                    <select name="category" class="form-input" required>
                    <option value="">-- Select Category --</option>
                    <option value="Engine & Transmission">Engine & Transmission</option>
                    <option value="Braking System">Braking System</option>
                    <option value="Suspension & Steering">Suspension & Steering</option>
                    <option value="Electrical & Lighting">Electrical & Lighting</option>
                    <option value="Tires & Wheels">Tires & Wheels</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                <label>Price:</label>
                        <input type="number" step="0.01" name="price" class="form-input" required>
                    </div>
                    <div class="form-group">
                <label>Stock Quantity:</label>
                        <input type="number" name="stockQuantity" class="form-input" required>
        </div>
    </div>
                <div class="form-group">
                    <label>Supplier ID:</label>
                    <input type="number" name="supplierID" class="form-input">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>

            <!-- Update Product Modal -->
            <div class="modal" id="updateModal">
                <div class="modal-content">
            <div class="modal-header">
                <h2>Update Product</h2>
                <button class="close-btn" onclick="closeUpdateModal()">&times;</button>
            </div>
            <form method="POST" action="products-admin.php" enctype="multipart/form-data">
                <div class="form-group">
                        <label>Product ID (to update):</label>
                    <input type="number" name="updateID" id="updateID" class="form-input" required>
                </div>
                <div class="form-group">
                        <label>New Product Image:</label>
                    <input type="file" name="updateImg" accept="image/*" class="form-input">
                </div>
                <div class="form-group">
                        <label>New Product Name:</label>
                    <input type="text" name="updateName" class="form-input">
                </div>
                <div class="form-group">
                        <label>New Category:</label>
                    <select name="updateCategory" class="form-input">
                            <option value="">-- Select Category --</option>
                            <option value="Engine & Transmission">Engine & Transmission</option>
                            <option value="Braking System">Braking System</option>
                            <option value="Suspension & Steering">Suspension & Steering</option>
                            <option value="Electrical & Lighting">Electrical & Lighting</option>
                            <option value="Tires & Wheels">Tires & Wheels</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Price:</label>
                        <input type="number" step="0.01" name="updatePrice" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>New Stock Quantity:</label>
                        <input type="number" name="updateStock" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                        <label>New Supplier ID:</label>
                    <input type="number" name="updateSupplier" class="form-input">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                    <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Product</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <form method="POST" action="products-admin.php">
                <div class="form-group">
                    <label>Enter Product ID to Delete:</label>
                    <input type="number" name="deleteID" id="deleteID" class="form-input" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_product" class="btn btn-primary" style="background: #ff6b6b;">Delete Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openUpdateModal(productID) {
            document.getElementById('updateID').value = productID;
            document.getElementById('updateModal').classList.add('active');
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('active');
        }

        function openDeleteModal(productID) {
            document.getElementById('deleteID').value = productID;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const updateModal = document.getElementById('updateModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == updateModal) {
                closeUpdateModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Stock filter functionality
        document.getElementById('stockFilter').addEventListener('change', function() {
            const filter = this.value;
            const cards = document.querySelectorAll('.product-card');
            
            cards.forEach(card => {
                const badge = card.querySelector('.badge');
                if (!badge) return;
                
                const status = badge.textContent.toLowerCase();
                let show = true;
                
                if (filter === 'In Stock' && !status.includes('in stock')) show = false;
                if (filter === 'Low Stock' && !status.includes('low stock')) show = false;
                if (filter === 'Out of Stock' && !status.includes('out of stock')) show = false;
                
                card.style.display = show ? '' : 'none';
            });
        });

        // Category filter functionality
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const filter = this.value;
            const cards = document.querySelectorAll('.product-card');
            
            cards.forEach(card => {
                const categoryText = card.querySelector('.detail-value');
                if (!categoryText) return;
                
                const category = categoryText.textContent.toLowerCase();
                let show = true;
                
                if (filter !== 'All Categories' && !category.includes(filter.toLowerCase())) {
                    show = false;
                }
                
                card.style.display = show ? '' : 'none';
            });
        });
    </script>
</body>
</html>