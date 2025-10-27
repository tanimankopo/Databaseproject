<?php
session_start();

// ✅ Protect page: Allows Cashier (submission) and Sales (approval/viewing)
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier" && $_SESSION['role'] !== "sales")) {
    header("Location: login.php");
    exit();
}

include "db.php";

// Include FPDF library (download from http://www.fpdf.org/ and place in a folder, e.g., 'fpdf/')
// Ensure this path is correct for your setup.
require('fpdf/fpdf.php');

// --- 1. Check for Sale ID ---
if (!isset($_GET['saleID']) || empty($_GET['saleID'])) {
    die("Error: Sale ID not specified.");
}

$saleID = intval($_GET['saleID']);

// --- 2. Retrieve Sale Header Data (Sale, Customer, Cashier) ---
$sql_header = "
SELECT 
    s.saleID, 
    s.customerName, 
    s.totalAmount, 
    s.saleDate,
    uc.username AS cashierName
FROM sales s
LEFT JOIN usermanagement uc ON s.cashierID = uc.userID
WHERE s.saleID = ?
";

$stmt_header = $conn->prepare($sql_header);
$stmt_header->bind_param("i", $saleID);
$stmt_header->execute();
$result_header = $stmt_header->get_result();

if ($result_header->num_rows === 0) {
    die("Error: Sale record not found.");
}
$sale = $result_header->fetch_assoc();
$stmt_header->close();

// --- 3. Retrieve Sale Items Data ---
$sql_items = "
SELECT 
    si.quantity, 
    si.unitPrice, 
    si.lineTotal, 
    p.productName
FROM sale_items si
JOIN products p ON si.productID = p.productID
WHERE si.saleID = ?
";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $saleID);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

// --- 4. Generate PDF Receipt ---
$pdf = new FPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, '1-GARAGE Official Receipt', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');
$pdf->Ln(10);

// Receipt Details
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Sale ID:', 0, 0);
$pdf->Cell(0, 8, $sale['saleID'], 0, 1);
$pdf->Cell(50, 8, 'Customer:', 0, 0);
$pdf->Cell(0, 8, htmlspecialchars($sale['customerName']), 0, 1); // Display buyer info
$pdf->Cell(50, 8, 'Cashier:', 0, 0);
$pdf->Cell(0, 8, htmlspecialchars($sale['cashierName']), 0, 1);
$pdf->Cell(50, 8, 'Date:', 0, 0);
$pdf->Cell(0, 8, date('Y-m-d H:i:s', strtotime($sale['saleDate'])), 0, 1);
$pdf->Ln(5);

// --- Items Table Header ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(80, 7, 'Item Name', 1, 0, 'L', true);
$pdf->Cell(30, 7, 'Unit Price', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Qty', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'Subtotal', 1, 1, 'R', true);

// --- Items Table Rows ---
$pdf->SetFont('Arial', '', 11);
while ($item = $items_result->fetch_assoc()) {
    $pdf->Cell(80, 6, $item['productName'], 1, 0, 'L');
    $pdf->Cell(30, 6, number_format($item['unitPrice'], 2), 1, 0, 'R');
    $pdf->Cell(30, 6, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(50, 6, number_format($item['lineTotal'], 2), 1, 1, 'R');
}
$stmt_items->close();

// --- Total Footer ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(140, 10, 'TOTAL AMOUNT:', 1, 0, 'R', true);
$pdf->Cell(50, 10, '₱' . number_format($sale['totalAmount'], 2), 1, 1, 'R', true);


// Output PDF to browser
$pdf->Output('I', 'receipt_' . $saleID . '.pdf');
exit();

?>