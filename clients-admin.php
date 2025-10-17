<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Insert Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_client'])) {
    $clientName    = $_POST['clientName'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO clientinfo (clientName, contactNumber, email, address) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $clientName, $contactNumber, $email, $address);
    $stmt->execute();
    $stmt->close();

    header("Location: clients.php");
    exit();
}

// ✅ Delete Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_client'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM clientinfo WHERE clientID = ?");
    $stmt->bind_param("i", $deleteID);
    $stmt->execute();
    $stmt->close();

    header("Location: clients.php");
    exit();
}

// ✅ Update Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_client'])) {
    $updateID      = intval($_POST['updateID']);
    $clientName    = $_POST['updateName'];
    $contactNumber = $_POST['updateNumber'];
    $email         = $_POST['updateEmail'];
    $address       = $_POST['updateAddress'];

    $stmt = $conn->prepare("UPDATE clientinfo 
                            SET clientName=?, contactNumber=?, email=?, address=? 
                            WHERE clientID=?");
    $stmt->bind_param("ssssi", $clientName, $contactNumber, $email, $address, $updateID);
    $stmt->execute();
    $stmt->close();

    header("Location: clients.php");
    exit();
}

// ✅ Fetch clients
$result = $conn->query("SELECT * FROM clientinfo ORDER BY clientID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <link rel="stylesheet" href="css/client.css">
</head>
<body>

    <!-- Sidebar -->
    <?php
            include("sidebar-admin.php")
    ?>
    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <h1>👥 Clients</h1>
            <div class="settings-menu">
                <button class="settings-btn">&#9776;</button>
                <div class="settings-dropdown">
                    <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add Client</button>
                    <button class="delete-btn" onclick="document.getElementById('deleteModal').style.display='flex'">🗑 Delete Client</button>
                    <button class="update-btn" onclick="document.getElementById('updateModal').style.display='flex'">✏ Update Client</button>
                </div>
            </div>
        </header>

        <!-- Client Table -->
        <table class="supplier-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Contact Number</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Registered Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['clientID']; ?>:</td>
                    <td><?= htmlspecialchars($row['clientName']); ?></td>
                    <td><?= htmlspecialchars($row['contactNumber']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['address']); ?></td>
                    <td><?= $row['registeredDate']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Client Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h3>Add New Client</h3>
            <form method="POST" action="clients.php">
                <label>Client Name:</label>
                <input type="text" name="clientName" required><br>

                <label>Contact Number:</label>
                <input type="text" name="contactNumber"><br>

                <label>Email:</label>
                <input type="email" name="email"><br>

                <label>Address:</label>
                <input type="text" name="address"><br>

                <button type="submit" name="add_client">Save</button>
                <button type="button" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Client Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3>Delete Client</h3>
            <form method="POST" action="clients.php">
                <label>Enter Client ID to Delete:</label>
                <input type="number" name="deleteID" required><br>
                <button type="submit" name="delete_client">Delete</button>
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Update Client Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <h3>Update Client</h3>
            <form method="POST" action="clients.php">
                <label>Client ID (to update):</label>
                <input type="number" name="updateID" required><br>

                <label>New Client Name:</label>
                <input type="text" name="updateName"><br>

                <label>New Contact Number:</label>
                <input type="text" name="updateNumber"><br>

                <label>New Email:</label>
                <input type="email" name="updateEmail"><br>

                <label>New Address:</label>
                <input type="text" name="updateAddress"><br>

                <button type="submit" name="update_client">Update</button>
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
