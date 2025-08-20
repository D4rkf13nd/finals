<?php

session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

$error = $success = "";
$resident = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM pop_data WHERE id = ? AND added_by = ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();

    if (!$resident) {
        header("Location: user.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $sex = $_POST['sex'];
    $barangay = trim($_POST['barangay']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);
    $birthday = $_POST['birthday'];

    try {
        $stmt = $conn->prepare("UPDATE pop_data SET name=?, age=?, sex=?, barangay=?, address=?, contact=?, birthday=? WHERE id=? AND added_by=?");
        $stmt->bind_param("sisssssii", $name, $age, $sex, $barangay, $address, $contact, $birthday, $id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Record updated successfully";
            header("Location: user.php");
            exit;
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $error = "Error updating record: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Resident</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Edit Resident</h1>
            <p class="lead">Update resident information</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($resident): ?>
            <form method="post" class="card p-4">
                <input type="hidden" name="id" value="<?= $resident['id'] ?>">
                
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($resident['name']) ?>" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" value="<?= $resident['age'] ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="Male" <?= $resident['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $resident['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="LGBTQ" <?= $resident['sex'] === 'LGBTQ' ? 'selected' : '' ?>>LGBTQ</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birthday" class="form-control" value="<?= $resident['birthday'] ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Barangay</label>
                    <input type="text" name="barangay" class="form-control" value="<?= htmlspecialchars($resident['barangay']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($resident['address']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($resident['contact']) ?>">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="user.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>