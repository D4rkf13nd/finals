<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized access']));
}

require_once "db.php";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="residents.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Name', 'Age', 'Sex', 'Barangay', 'Address', 'Contact', 'Birthday']);

// Get data and write to CSV
$sql = "SELECT name, age, sex, barangay, address, contact, birthday FROM pop_data";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;