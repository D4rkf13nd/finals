<?php

require_once "../../backend/db.php";

$sql = "SELECT 
    sex as gender,
    COUNT(*) as count 
FROM pop_data 
GROUP BY sex";

$result = $conn->query($sql);
$stats = [];

while ($row = $result->fetch_assoc()) {
    $stats[$row['gender']] = (int)$row['count'];
}

header('Content-Type: application/json');
echo json_encode($stats);