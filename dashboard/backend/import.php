<?php
require_once "db.php";
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["import_file"])) {
    $fileName = $_FILES["import_file"]["name"];
    $fileTmp = $_FILES["import_file"]["tmp_name"];
    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

    if ($fileType == "csv") {
        // Handle CSV import
        $handle = fopen($fileTmp, "r");
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row == 0) { $row++; continue; } // skip header
            // [name, age, sex, barangay, address, contact, birthday]
            $stmt = $conn->prepare("INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
            $stmt->execute();
            $stmt->close();
            $row++;
        }
        fclose($handle);
        $successMessage = "CSV imported successfully!";
    } else {
        $errorMessage = "Only CSV files are supported in this demo.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Import Residents</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f8fafc;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 40px 30px 30px 30px;
            max-width: 600px;
        }
        h2 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-label {
            font-weight: 500;
            color: #374151;
        }
        .btn-warning {
            background: #f59e42;
            border: none;
        }
        .btn-warning:hover {
            background: #e07c1f;
        }
        .btn-secondary {
            margin-left: 10px;
        }
        .alert {
            margin-top: 10px;
        }
        pre {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<div class="container my-5">
    <h2>Import Residents (CSV)</h2>
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="import_file" class="form-label">Choose CSV file</label>
            <input type="file" class="form-control" name="import_file" id="import_file" accept=".csv" required>
        </div>
        <button type="submit" class="btn btn-warning">Import</button>
        <a href="../main.php" class="btn btn-secondary">Back</a>
    </form>
    <div class="mt-3">
        <strong>CSV Format:</strong>
        <pre>name,age,sex,barangay,address,contact,birthday</pre>
    </div>
</div>
</body>
</html>