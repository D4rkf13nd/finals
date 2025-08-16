<?php

session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"];
    $allowed = ["application/vnd.ms-excel", "text/csv"];
    
    if (in_array($file["type"], $allowed)) {
        $handle = fopen($file["tmp_name"], "r");
        $success = 0;
        $failed = 0;
        
        // Skip header row
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $sql = "INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
            
            if ($stmt->execute()) {
                $success++;
            } else {
                $failed++;
            }
        }
        fclose($handle);
        $message = "Imported successfully: $success records. Failed: $failed records.";
    } else {
        $error = "Invalid file type. Please upload a CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Data</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Import Residents Data</h2>
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="mb-3">
            <div class="mb-3">
                <label for="file" class="form-label">Choose CSV File</label>
                <input type="file" class="form-control" id="file" name="file" accept=".csv" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Import</button>
                <a href="user.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <div class="alert alert-info">
            <h5>CSV Format:</h5>
            <p>name,age,sex,barangay,address,contact,birthday</p>
        </div>
    </div>
</body>
</html>