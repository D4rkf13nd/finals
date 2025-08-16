<?php

session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="residents_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['Name', 'Age', 'Sex', 'Barangay', 'Address', 'Contact', 'Birthday']);

// Get data added by current user
$sql = "SELECT name, age, sex, barangay, address, contact, birthday 
        FROM pop_data 
        WHERE added_by = ? 
        ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

// Close the output stream
fclose($output);
exit;