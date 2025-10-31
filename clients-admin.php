<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Add Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_client'])) {
    $clientName    = $_POST['clientName'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO clientinfo (clientName, contactNumber, email, address, registeredDate)
                            VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $clientName, $contactNumber, $email, $address);
    $stmt->execute();
    $stmt->close();

    header("Location: clients-admin.php");
    exit();
}

// ✅ Delete Client (AJAX)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_client'])) {
    $deleteID = intval($_POST['deleteID']);
    $stmt = $conn->prepare("DELETE FROM clientinfo WHERE clientID = ?");
    $stmt->bind_param("i", $deleteID);

    echo $stmt->execute() ? "success" : "error";
    $stmt->close();
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

    if ($stmt->execute()) {
        echo "<script>alert('✅ Client updated successfully!'); window.location='clients-admin.php';</script>";
    } else {
        echo "<script>alert('❌ Error updating client.'); window.location='clients-admin.php';</script>";
    }

    $stmt->close();
    exit();
}

// ✅ Fetch clients
$result = $conn->query("SELECT * FROM clientinfo ORDER BY clientID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients (Admin)</title>
    <link rel="stylesheet" href="css/client.css">
</head>

<script>
function confirmDelete(clientID, event) {
    if (!confirm("Are you sure you want to delete this client?")) return;

    fetch('clients-admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            delete_client: '1',
            deleteID: clientID
        })
    })
    .then(r => r.text())
    .then(data => {
        if (data.trim() === "success") {
            alert("🗑️ Client deleted successfully!");
            const row = event.target.closest('tr');
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else alert("❌ Error deleting client.");
    })
    .catch(() => alert("⚠️ Error connecting to server."));
}

function openUpdateModal(id, name, number, email, address) {
    document.getElementById('updateModal').style.display = 'flex';
    document.getElementById('updateID').value = id;
    document.getElementById('updateName').value = name;
    document.getElementById('updateNumber').value = number;
    document.getElementById('updateEmail').value = email;
}
</script>

<body>

<?php include("sidebar-admin.php"); ?>

<div class="main-content">
    <header class="topbar">
        <h1>👥 Client Management</h1>
    </header>

    <table class="supplier-table">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                
                <th>Registered Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['clientName']); ?></td>
                <td><?= htmlspecialchars($row['contactNumber']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['registeredDate']); ?></td>
                <td>
                    <button class="update-btn"
                        onclick="openUpdateModal(
                            '<?= $row['clientID']; ?>',
                            '<?= htmlspecialchars($row['clientName']); ?>',
                            '<?= htmlspecialchars($row['contactNumber']); ?>',
                            '<?= htmlspecialchars($row['email']); ?>',
                            
                        )">Update</button>

                    <button class="delete-btn" onclick="confirmDelete(<?= $row['clientID']; ?>, event)">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Client Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add Client</h3>
        <form method="POST" action="clients-admin.php">
            <label>Name:</label>
            <input type="text" name="clientName" required><br>

            <label>Contact:</label>
            <input type="text" name="contactNumber"><br>

            <label>Email:</label>
            <input type="email" name="email"><br>

            <label>Address:</label>
            <input type="text" name="address"><br>

            <button type="submit" name="add_client">Save</button>
            <button type="button" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

<!-- Update Modal -->
<div class="modal" id="updateModal">
    <div class="modal-content">
        <h3>Update Client</h3>
        <form method="POST" action="clients-admin.php">
            <input type="hidden" name="updateID" id="updateID">
            <label>Name:</label>
            <input type="text" name="updateName" id="updateName" required><br>

            <label>Contact:</label>
            <input type="text" name="updateNumber" id="updateNumber"><br>

            <label>Email:</label>
            <input type="email" name="updateEmail" id="updateEmail"><br>

            <label>Address:</label>
            <input type="text" name="updateAddress" id="updateAddress"><br>

            <button type="submit" name="update_client">Update</button>
            <button type="button" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

</body>
</html>
