<?php

session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $birthday = $_POST['birthday'] ?? '';

    $sql = "INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday, added_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissssis", $name, $age, $sex, $barangay, $address, $contact, $birthday, $_SESSION["user_id"]);
    
    if ($stmt->execute()) {
        header("Location: user.php?success=1");
        exit;
    } else {
        $error = "Error adding record";
    }
}

$barangays = [
    "BF Homes", "Don Bosco", "Marcelo Green", "Merville",
    "Moonwalk", "San Antonio", "San Martin de Porres", "Sun Valley"
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Resident</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Add New Resident</h2>
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="number" class="form-control" id="age" name="age" required>
            </div>
            <div class="mb-3">
                <label for="sex" class="form-label">Sex</label>
                <select class="form-select" id="sex" name="sex" required>
                    <option value="">Select Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="barangay" class="form-label">Barangay</label>
                <select class="form-select" id="barangay" name="barangay" required>
                    <option value="">Select Barangay</option>
                    <?php foreach ($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" required>
            </div>
            <div class="mb-3">
                <label for="contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="contact" name="contact" required>
            </div>
            <div class="mb-3">
                <label for="birthday" class="form-label">Birthday</label>
                <input type="date" class="form-control" id="birthday" name="birthday" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="user.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>