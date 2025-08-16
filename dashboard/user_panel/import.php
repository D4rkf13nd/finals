<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"];
    $allowed = ["application/vnd.ms-excel", "text/csv"];
    
    if (in_array($file["type"], $allowed)) {
        try {
            $conn->begin_transaction();
            
            if (!$handle = fopen($file["tmp_name"], "r")) {
                throw new Exception("Failed to open file");
            }
            
            $row = 0;
            $successRows = 0;
            
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                $row++;
                
                try {
                    // Validate row data
                    validateRow($data);
                    
                    // Store trimmed values in variables
                    $name = trim($data[0]);
                    $age = intval($data[1]);
                    $sex = $data[2];
                    $barangay = trim($data[3]);
                    $address = trim($data[4]);
                    $contact = trim($data[5]);
                    $birthday = $data[6];
                    $addedBy = $_SESSION["user_id"];
                    
                    // Prepare statement
                    $sql = "INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday, added_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if (!$stmt) {
                        throw new Exception("Failed to prepare statement: " . $conn->error);
                    }

                    // Bind parameters using variables
                    $stmt->bind_param("sissssis", 
                        $name,
                        $age,
                        $sex,
                        $barangay,
                        $address,
                        $contact,
                        $birthday,
                        $addedBy
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert row $row: " . $stmt->error);
                    }
                    
                    $successRows++;
                    $stmt->close();
                    
                } catch (Exception $e) {
                    error_log("Error at row $row: " . $e->getMessage());
                    continue;
                }
            }
            
            fclose($handle);
            $conn->commit();
            
            $successMessage = "Import completed successfully! Imported $successRows rows.";
            
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            $errorMessage = "Import failed: " . $e->getMessage();
            error_log("Import error: " . $e->getMessage());
        }
    } else {
        $errorMessage = "Invalid file type. Please upload a CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Data</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
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
    <nav class="sidebar">
        <!-- Keep your existing sidebar code -->
    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Import Residents Data</h1>
            <p class="lead">Upload your CSV file to import resident records</p>
        </div>

        <div class="container">
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

            <form method="POST" enctype="multipart/form-data" id="importForm" class="mb-4">
                <div class="mb-3">
                    <label for="file" class="form-label">Choose CSV File</label>
                    <input type="file" class="form-control" id="file" name="file" accept=".csv" required>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary" id="importBtn">Import</button>
                    <a href="user.php" class="btn btn-secondary">Cancel</a>
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

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">CSV Format</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded">
name,age,sex,barangay,address,contact,birthday
John Doe,30,Male,Barangay 1,123 St,09171234567,1993-05-15
Jane Smith,28,Female,Barangay 2,456 St,09176543210,1995-08-25</pre>
                </div>
            </div>
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