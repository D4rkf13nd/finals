<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "db.php";

// Set headers for Excel download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="population_data_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [ 'Name', 'Age', 'Sex', 'Barangay', 'Address', 'Contact', 'Birthday']);

// Get data from database
$sql = "SELECT  name, age, sex, barangay, address, contact, birthday FROM pop_data ORDER BY id";
$result = $conn->query($sql);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

// Close the output stream
fclose($output);
exit;