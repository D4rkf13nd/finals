<?php
session_start();
require_once "db.php";

// Initialize messages
$successMessage = "";
$errorMessage = "";

// Function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Function to validate data row
function validateRow($data) {
    if (count($data) !== 7) {
        throw new Exception("Invalid number of columns");
    }
    
    // Validate name
    if (empty(trim($data[0]))) {
        throw new Exception("Name cannot be empty");
    }
    
    // Validate age
    if (!is_numeric($data[1]) || $data[1] < 0 || $data[1] > 120) {
        throw new Exception("Invalid age value");
    }
    
    // Validate sex
    if (!in_array($data[2], ['Male', 'Female', 'LGBTQ'])) {
        throw new Exception("Invalid sex value");
    }
    
    // Validate barangay
    if (empty(trim($data[3]))) {
        throw new Exception("Barangay cannot be empty");
    }
    
    // Validate birthday
    if (!validateDate($data[6])) {
        throw new Exception("Invalid date format for birthday");
    }
    
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["import_file"])) {
    $fileName = $_FILES["import_file"]["name"];
    $fileTmp = $_FILES["import_file"]["tmp_name"];
    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
    
    if ($fileType != "csv") {
        $errorMessage = "Only CSV files are allowed.";
    } else {
        try {
            $conn->begin_transaction();
            
            if (!$handle = fopen($fileTmp, "r")) {
                throw new Exception("Failed to open file");
            }
            
            $row = 0;
            $successRows = 0;
            
            // Prepare statement
            $stmt = $conn->prepare("INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                
                // Skip header row
                if ($row === 1) continue;
                
                try {
                    // Validate row data
                    validateRow($data);
                    
                    // Assign trimmed values to variables
                    $name = trim($data[0]);
                    $age = $data[1];
                    $sex = $data[2];
                    $barangay = trim($data[3]);
                    $address = trim($data[4]);
                    $contact = trim($data[5]);
                    $birthday = $data[6];

                    // Bind and execute
                    if (!$stmt->bind_param("sisssss", 
                        $name,      // name
                        $age,       // age
                        $sex,       // sex
                        $barangay,  // barangay
                        $address,   // address
                        $contact,   // contact
                        $birthday   // birthday
                    )) {
                        throw new Exception("Failed to bind parameters at row $row");
                    }
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert row $row: " . $stmt->error);
                    }
                    
                    $successRows++;
                    
                } catch (Exception $e) {
                    error_log("Error at row $row: " . $e->getMessage());
                    continue;
                }
            }
            
            fclose($handle);
            $stmt->close();
            $conn->commit();
            
            $successMessage = "Import completed successfully! Imported $successRows rows.";
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            $errorMessage = "Import failed: " . $e->getMessage();
            error_log("Import error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Residents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 40px 30px;
            max-width: 600px;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .progress {
            height: 25px;
            border-radius: 15px;
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
        <h2 class="text-center mb-4">Import Residents (CSV)</h2>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" id="importForm">
            <div class="mb-3">
                <label for="import_file" class="form-label">Choose CSV file</label>
                <input type="file" class="form-control" name="import_file" id="import_file" accept=".csv" required>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary" id="importBtn">Import</button>
                <a href="../main.php" class="btn btn-secondary">Back</a>
            </div>
        </form>
        
        <div class="progress-container" id="progressContainer">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 0%" 
                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <div id="importStatus" class="text-center mt-2">Preparing to import...</div>
        </div>
        
        <div class="mt-4">
            <h5>CSV Format:</h5>
            <pre class="bg-light p-3 rounded">
name,age,sex,barangay,address,contact,birthday
John Doe,30,Male,Barangay 1,123 St,09171234567,1993-05-15
Jane Smith,28,Female,Barangay 2,456 St,09176543210,1995-08-25
</pre>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay"></div>

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
