<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized access']));
}

require_once "db.php";

$sql = "SELECT * FROM pop_data ORDER BY id ASC";
$result = $conn->query($sql);

if ($result) {
    $filename = "population_data_" . date('Y-m-d_His') . ".csv";
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create file pointer for output
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers - removed 'ID'
    fputcsv($output, ['Name', 'Age', 'Sex', 'Barangay', 'Address', 'Contact', 'Birthday']);
    
    // Add data rows - removed $row['id']
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['age'],
            $row['sex'],
            $row['barangay'],
            $row['address'],
            $row['contact'],
            $row['birthday']
        ]);
    }
    
    fclose($output);
} else {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Failed to fetch data']));
}