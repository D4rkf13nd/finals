<?php
session_start();
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
} elseif (isset($_POST['import'])) {
    $response = ['success' => false, 'message' => ''];
    
    if ($_FILES['import_file']['error'] == 0) {
        $fileName = $_FILES['import_file']['tmp_name'];
        
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $stmt = $conn->prepare("INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
                $stmt->execute();
            }
            
            fclose($handle);
            $response['success'] = true;
            $response['message'] = "Data imported successfully!";
        }
    } else {
        $response['message'] = "Error uploading file.";
    }
    
    // Redirect back to user.php with status
    $_SESSION['import_status'] = $response;
    header("Location: ../user.php");
    exit;
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
        .progress-container {
            display: none;
            margin-top: 20px;
        }

        .progress {
            height: 25px;
            background-color: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #048315ff;
            transition: width 0.3s ease;
        }

        #importStatus {
            margin-top: 10px;
            text-align: center;
            color: #666;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
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
    <form method="post" enctype="multipart/form-data" id="importForm">
        <div class="mb-3">
            <label for="import_file" class="form-label">Choose CSV file</label>
            <input type="file" class="form-control" name="import_file" id="import_file" accept=".csv" required>
        </div>
        <button type="submit" class="btn btn-warning" id="importBtn">Import</button>
        <a href="../main.php" class="btn btn-secondary">Back</a>
    </form>

    <!-- Add Progress Bar -->
    <div class="progress-container" id="progressContainer">
        <div class="progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 aria-valuenow="0" 
                 aria-valuemin="0" 
                 aria-valuemax="100">0%</div>
        </div>
        <div id="importStatus">Preparing to import...</div>
    </div>

    <div class="mt-3">
        <strong>CSV Format:</strong>
        <pre>name,age,sex,barangay,address,contact,birthday</pre>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay"></div>

<!-- Add this before closing body tag -->
<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = progressContainer.querySelector('.progress-bar');
    const statusText = document.getElementById('importStatus');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const importBtn = document.getElementById('importBtn');

    // Show progress container and overlay
    progressContainer.style.display = 'block';
    loadingOverlay.style.display = 'block';
    importBtn.disabled = true;

    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 100) progress = 100;
        
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        progressBar.textContent = Math.round(progress) + '%';
        
        // Update status text based on progress
        if (progress < 30) {
            statusText.textContent = 'Reading CSV file...';
        } else if (progress < 60) {
            statusText.textContent = 'Validating data...';
        } else if (progress < 90) {
            statusText.textContent = 'Importing records...';
        } else if (progress === 100) {
            statusText.textContent = 'Import complete!';
            clearInterval(interval);
        }
    }, 500);
});
</script>
</body>
</html>