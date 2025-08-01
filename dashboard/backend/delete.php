<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No ID specified.");
}

$sql = "DELETE FROM pop_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../main.php");
    exit;
} else {
    echo "Error deleting record.";
}
?>