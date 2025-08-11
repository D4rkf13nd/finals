<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete all records from the pop_data table
$sql = "DELETE FROM pop_data";
if ($conn->query($sql) === TRUE) {
    header("Location: ../main.php?section=setting&deleted=1");
    exit;
} else {
    echo "Error deleting data: " . $conn->error;
}
$conn->close();
?>