<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

$name = "";
$age = "";
$sex = "";
$barangay = "";
$address = "";
$contact = "";
$birthday = "";

$errorMessage = "";
$successMessage = "";

// Barangay options
$barangayOptions = [
    "BF Homes", "Don Bosco", "Marcelo Green", "Merville",
    "Moonwalk", "San Antonio", "San Martin de Porres", "Sun Valley"
];

// Add this function after the database connection code
function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    $age = $birth->diff($today);
    return $age->y;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $birthday = $_POST["birthday"];
    $sex = $_POST["sex"];
    $barangay = $_POST["barangay"];
    $address = $_POST["address"];
    $contact = $_POST["contact"];

    // Calculate age automatically from birthday
    $age = calculateAge($birthday);

    do {
        if (empty($name) || empty($birthday) || empty($sex) || empty($barangay) || empty($address) || empty($contact)) {
            $errorMessage = "All fields are required";
            break;
        }

        // Validate birthday
        $birthDate = new DateTime($birthday);
        $today = new DateTime();
        if ($birthDate > $today) {
            $errorMessage = "Birthday cannot be in the future";
            break;
        }

        // Use prepared statement for security
        $stmt = $conn->prepare("INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $name, $age, $sex, $barangay, $address, $contact, $birthday);

        if (!$stmt->execute()) {
            $errorMessage = "Invalid query: " . $conn->error;
            break;
        }

        $successMessage = "Resident added successfully!";
        // Clear form fields after success
        $name = $age = $sex = $barangay = $address = $contact = $birthday = "";

        $stmt->close();

        header("Location: ../main.php");
        exit;

    } while (false);
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
        body {
           background: rgba(255, 255, 255, 1)
        }
        /* Enhanced Form Controls */
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control:hover, .form-select:hover {
            border-color: #93c5fd;
        }

        /* Gender Select Special Styling */
        select[name="sex"] {
            border-color: #4f46e5;
        }

        select[name="sex"]:focus {
            border-color: #4338ca;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Container Border */
        .container {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            /* Added styles for container size */
            max-width: 600px !important;  /* Reduced from 800px */
            margin: 1rem auto;
            padding: 1.25rem;
        }

        /* Form Controls Size */
        .form-control, .form-select {
            height: 35px;  /* Reduced from 38px */
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Column Sizes */
        .col-sm-6 {
            max-width: 250px;  /* Reduced from 300px */
        }

        /* Form Groups Spacing */
        .row.mb-3 {
            margin-bottom: 0.5rem !important;
            padding: 4px;
        }

        /* Labels */
        .form-label, .col-form-label {
            font-size: 0.875rem;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        /* Header */
        h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        /* Button Sizes */
        .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Alert Boxes */
        .alert {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }

        /* Button Borders */
        .btn-primary {
            border: 2px solid transparent;
        }

        .btn-primary:hover {
            border-color: #1d4ed8;
        }

        .btn-outline-secondary {
            border: 2px solid #64748b;
        }

        .btn-outline-secondary:hover {
            border-color: #475569;
        }

        /* Alert Borders */
        .alert {
            border-width: 2px;
            border-left-width: 4px;
        }

        .alert-warning {
            border-left-color: #f59e0b;
        }

        .alert-success {
            border-left-color: #10b981;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
        }
        .btn-primary {
            background: #2563eb;
            border: none;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-close {
            outline: none;
        }
        .alert {
            margin-top: 10px;
        }

        select[name="sex"] {
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%232563eb'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-position: right 0.75rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .gender-stats {
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
<div class="container my-5">
    <h2>New</h2>

    <?php if (!empty($errorMessage)): ?>
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong><?= htmlspecialchars($errorMessage) ?></strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
    <?php endif; ?>



    <form method="post" class="modal-content">
        <div class="row mb-3">
            <label for="name" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <label for="birthday" class="col-sm-2 col-form-label">Birthday</label>
            <div class="col-sm-6">
                <input type="date" class="form-control" name="birthday" id="birthday" value="<?= htmlspecialchars($birthday) ?>" onchange="calculateAge(this.value)">
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-2 col-form-label">Age</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="age" readonly>
            </div>
        </div>
        <div class="row mb-3">
            <label for="sex" class="col-sm-2 col-form-label">Sex/Gender</label>
            <div class="col-sm-6">
                <select class="form-select" name="sex">
                    <option value="">Select Sex/Gender</option>
                    <option value="Male" <?= $sex == "Male" ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $sex == "Female" ? 'selected' : '' ?>>Female</option>
                    <option value="LGBTQ+" <?= $sex == "LGBTQ+" ? 'selected' : '' ?>>LGBTQ+</option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <label for="barangay" class="col-sm-2 col-form-label">Barangay</label>
            <div class="col-sm-6">
                <select class="form-select" name="barangay">
                    <option value="">Select Barangay</option>
                    <?php foreach ($barangayOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= $barangay == $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <label for="address" class="col-sm-2 col-form-label">Address</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($address) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <label for="contact" class="col-sm-2 col-form-label">Contact</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($contact) ?>">
            </div>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class='alert alert-success alert-dismissible fade show' role='alert'>
                <strong><?= htmlspecialchars($successMessage) ?></strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-sm-10 d-flex gap-2">
                <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                <a class="btn btn-outline-secondary" href="../main.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script>
function calculateAge(birthday) {
    const birthDate = new Date(birthday);
    const today = new Date();
    
    if (birthday && birthDate <= today) {
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        document.getElementById('age').value = age + ' years old';
    } else {
        document.getElementById('age').value = '';
    }
}

// Calculate age on page load if birthday is set
window.onload = function() {
    const birthday = document.getElementById('birthday').value;
    if (birthday) {
        calculateAge(birthday);
    }
}

// Add the gender counter function
function updateGenderStats() {
    fetch('../api/get_gender_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('male-count').textContent = data.Male || 0;
            document.getElementById('female-count').textContent = data.Female || 0;
            document.getElementById('lgbtq-count').textContent = data.LGBTQ || 0;
        });
}

// Call the function when page loads
document.addEventListener('DOMContentLoaded', updateGenderStats);
</script>
</body>
</html>
