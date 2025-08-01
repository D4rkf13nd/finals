<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No ID specified.");
}

// Fetch existing data
$sql = "SELECT * FROM pop_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update'])) {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $sex = $_POST['sex'];
        $contact = $_POST['contact'];
        $birthday = $_POST['birthday'];
        
        // Calculate age from birthday
        if (!empty($birthday)) {
            $birthDate = new DateTime($birthday);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        } else {
            $age = $_POST['age']; // fallback to posted age
        }

        $update = "UPDATE pop_data SET name=?, address=?, age=?, sex=?, contact=?, birthday=? WHERE id=?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("ssisssi", $name, $address, $age, $sex, $contact, $birthday, $id);
        if ($stmt->execute()) {
            header("Location: ../main.php");
            exit;
        } else {
            $error = "Error updating record.";
        }
    }
    if (isset($_POST['delete'])) {
        $delete = "DELETE FROM pop_data WHERE id=?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: ../main.php");
            exit;
        } else {
            $error = "Error deleting record.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Resident</title>
    <link rel="stylesheet" href="../frontend/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h2>Edit Resident</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($row['address']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Age</label>
            <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($row['age']) ?>" readonly style="background-color: #f8f9fa;">
            <small class="text-muted">Age will be calculated from birthday</small>
        </div>
        <div class="mb-3">
            <label>Sex</label>
            <select name="sex" class="form-control" required>
                <option value="Male" <?= $row['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $row['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Contact</label>
            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($row['contact']) ?>">
        </div>
        <div class="mb-3">
            <label>Birthday</label>
            <input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($row['birthday']) ?>" onchange="calculateAge()">
        </div>
        <button type="submit" name="update" class="btn btn-primary">Update</button>
        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this resident?');">Delete</button>
        <a href="../main.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
function calculateAge() {
    const birthdayInput = document.querySelector('input[name="birthday"]');
    const ageInput = document.querySelector('input[name="age"]');
    
    if (birthdayInput.value) {
        const birthDate = new Date(birthdayInput.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        ageInput.value = age;
    } else {
        ageInput.value = '';
    }
}
</script>
</body>
</html>