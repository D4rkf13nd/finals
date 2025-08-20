<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once "db.php";

try {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="population_data_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create table header
    echo '<table border="1">' . "\n";
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>Age</th>';
    echo '<th>Sex</th>';
    echo '<th>Barangay</th>';
    echo '<th>Address</th>';
    echo '<th>Contact</th>';
    echo '<th>Birthday</th>';
    echo '</tr>' . "\n";

    // Get data from database
    $sql = "SELECT name, age, sex, barangay, address, contact, birthday 
            FROM pop_data 
            ORDER BY name ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error fetching data: " . $conn->error);
    }

    $rowCount = 0;
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['age']) . '</td>';
        echo '<td>' . htmlspecialchars($row['sex']) . '</td>';
        echo '<td>' . htmlspecialchars($row['barangay']) . '</td>';
        echo '<td>' . htmlspecialchars($row['address']) . '</td>';
        echo '<td>' . htmlspecialchars($row['contact']) . '</td>';
        echo '<td>' . htmlspecialchars($row['birthday']) . '</td>';
        echo '</tr>' . "\n";
        $rowCount++;
    }

    echo '</table>';

    // Set session message for toast before sending file
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => "Successfully exported $rowCount records to Excel"
    ];

    $conn->close();
    exit;

} catch (Exception $e) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => "Export failed: " . $e->getMessage()
    ];
    header("Location: ../main.php");
    exit;
}