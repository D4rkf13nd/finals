<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

$errorMessage = "";
$successMessage = "";

// Check if ID is provided and valid
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    $errorMessage = "Invalid or missing ID.";
} else {
    // Attempt to delete the record
    $stmt = $conn->prepare("DELETE FROM pop_data WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $successMessage = "Resident deleted successfully!";
    } else {
        $errorMessage = "Error deleting resident: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
    // Redirect after deletion (success or not)
    header("Location: ../main.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Resident</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h2>Delete Resident</h2>
    <?php if (!empty($errorMessage)): ?>
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong><?= htmlspecialchars($errorMessage) ?></strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class='alert alert-success alert-dismissible fade show' role='alert'>
            <strong><?= htmlspecialchars($successMessage) ?></strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
    <?php endif; ?>
    <a class="btn btn-secondary" href="../main.php" role="button">Back to List</a>
</div>
</body>
</html>