<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";

$conn = new mysqli($servername, $username, $password, $dbname);

$id = "";
$name = "";
$age = "";
$sex = "";
$barangay = "";
$address = "";
$contact = "";
$birthday = "";

$errorMessage = "";
$successMessage = "";

// Update the barangay options array to match your database
$barangayOptions = [
    "BF Homes", "Don Bosco", "Marcelo Green", "Merville",
    "Moonwalk", "San Antonio", "San Martin de Porres", "Sun Valley"
];

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Validate and sanitize the ID
    if (!isset($_GET["id"]) || !filter_var($_GET["id"], FILTER_VALIDATE_INT)) {
        header("location: ../main.php");
        exit;
    }

    $id = filter_var($_GET["id"], FILTER_SANITIZE_NUMBER_INT);
    
    // Prepare and execute the select query
    $sql = "SELECT * FROM pop_data WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        header("location: ../main.php");
        exit;
    }

    // Assign values from database
    $name = $row["name"];
    $age = $row["age"];
    $sex = strtolower($row["sex"]); // Normalize sex value
    $barangay = $row["barangay"];
    $address = $row["address"];
    $contact = $row["contact"];
    $birthday = $row["birthday"];
    
    $stmt->close();

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize POST data
    $id = filter_var($_POST["id"], FILTER_SANITIZE_NUMBER_INT);
    $name = filter_var($_POST["name"], FILTER_SANITIZE_STRING);
    $age = filter_var($_POST["age"], FILTER_SANITIZE_NUMBER_INT);
    $sex = strtolower(filter_var($_POST["sex"], FILTER_SANITIZE_STRING));
    $barangay = filter_var($_POST["barangay"], FILTER_SANITIZE_STRING);
    $address = filter_var($_POST["address"], FILTER_SANITIZE_STRING);
    $contact = filter_var($_POST["contact"], FILTER_SANITIZE_STRING);
    $birthday = filter_var($_POST["birthday"], FILTER_SANITIZE_STRING);

    do {
        // Validate required fields
        if (empty($name) || empty($age) || empty($sex) || empty($barangay) || 
            empty($address) || empty($contact) || empty($birthday)) {
            $errorMessage = "All fields are required";
            break;
        }

        // Validate age range
        if ($age < 0 || $age > 120) {
            $errorMessage = "Invalid age range";
            break;
        }

        // Validate barangay
        if (!in_array($barangay, $barangayOptions)) {
            $errorMessage = "Invalid barangay selected";
            break;
        }

        // Update the record
        $sql = "UPDATE pop_data SET 
                name = ?, age = ?, sex = ?, barangay = ?, 
                address = ?, contact = ?, birthday = ? 
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssi", 
            $name, $age, $sex, $barangay, 
            $address, $contact, $birthday, $id
        );

        if (!$stmt->execute()) {
            $errorMessage = "Update failed: " . $conn->error;
            break;
        }

        $successMessage = "Resident updated successfully";
        $stmt->close();

        // Redirect after successful update
        header("location: ../main.php");
        exit;

    } while (false);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resident</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    body {
        background: rgba(255, 255, 255, 1);
    }

    /* Enhanced Form Controls */
    .form-control, .form-select {
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        height: 35px;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
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
        cursor: pointer;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%232563eb'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-position: right 0.75rem center;
        background-size: 1em;
        padding-right: 2.5rem;
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
        max-width: 600px !important;
        margin: 1rem auto;
        padding: 1.25rem;
    }

    /* Column Sizes */
    .col-sm-6 {
        max-width: 250px;
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
        font-weight: 500;
        color: #374151;
    }

    /* Header */
    h2 {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    /* Buttons */
    .btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: #2563eb;
        border: 2px solid transparent;
    }

    .btn-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .btn-secondary {
        border: 2px solid #64748b;
    }

    .btn-secondary:hover {
        border-color: #475569;
    }

    /* Alert Boxes */
    .alert {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        margin: 0.75rem 0;
        border-width: 2px;
        border-left-width: 4px;
    }

    .alert-warning {
        border-left-color: #f59e0b;
    }

    .alert-success {
        border-left-color: #10b981;
    }

    .btn-close {
        outline: none;
    }

    /* Stats Cards */
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
    <h2>Edit</h2>

    <?php if (!empty($errorMessage)): ?>
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong><?= htmlspecialchars($errorMessage) ?></strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        <input type="hidden" name="age" id="age" value="<?= htmlspecialchars($age) ?>">
        <div class="row mb-3">
            <label for="name" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <label for="sex" class="col-sm-2 col-form-label">Sex/Gender</label>
            <div class="col-sm-6">
                <select class="form-select" name="sex">
                    <option value="male" <?= $sex == "male" ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $sex == "female" ? 'selected' : '' ?>>Female</option>
                    <option value="lgbtq+" <?= $sex == "lgbtq+" ? 'selected' : '' ?>>LGBTQ+</option>
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
        <div class="row mb-3">
            <label for="birthday" class="col-sm-2 col-form-label">Birthday</label>
            <div class="col-sm-6">
                <input type="date" class="form-control" name="birthday" id="birthday" 
                       value="<?= htmlspecialchars($birthday) ?>" 
                       onchange="calculateAge(this.value)">
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-2 col-form-label">Age</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="age-display" readonly>
            </div>
        </div>
        <?php if (!empty($successMessage)): ?>
            <div class='alert alert-success alert-dismissible fade show' role='alert'>
                <strong><?= htmlspecialchars($successMessage) ?></strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col-sm-10">
                <button type="submit" class="btn btn-primary" name="submit">Update</button>
                <a class="btn btn-secondary" href="../main.php" role="button">Cancel</a>
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
        
        // Update both display and hidden age fields
        document.getElementById('age-display').value = age + ' years old';
        document.getElementById('age').value = age; // Hidden field for form submission
    } else {
        document.getElementById('age-display').value = '';
        document.getElementById('age').value = '';
    }
}

// Calculate age on page load if birthday is set
document.addEventListener('DOMContentLoaded', function() {
    const birthday = document.getElementById('birthday').value;
    if (birthday) {
        calculateAge(birthday);
    }
});
</script>
</body>
</html>