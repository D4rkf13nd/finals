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

// Barangay options
$barangayOptions = [
    "BF Homes", "Don Bosco", "Marcelo Green", "Merville",
    "Moonwalk", "San Antonio", "San Martin de Porres", "Sun Valley"
];

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
        header("location: /dashboard/main.php");
        exit;
    }

    $id = $_GET["id"];
    $sql = "SELECT * FROM pop_data WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        header("location: /dashboard/main.php");
        exit;
    }

    $name = $row["name"];
    $age = $row["age"];
    $sex = $row["sex"];
    $barangay = $row["barangay"];
    $address = $row["address"];
    $contact = $row["contact"];
    $birthday = $row["birthday"];
    $stmt->close();
} else {
    $id = $_POST["id"];
    $name = $_POST["name"];
    $age = $_POST["age"];
    $sex = $_POST["sex"];
    $barangay = $_POST["barangay"];
    $address = $_POST["address"];
    $contact = $_POST["contact"];
    $birthday = $_POST["birthday"];

    do {
        if (empty($name) || empty($age) || empty($sex) || empty($barangay) || empty($address) || empty($contact) || empty($birthday)) {
            $errorMessage = "All fields are required";
            break;
        }

        $sql = "UPDATE pop_data SET name=?, age=?, sex=?, barangay=?, address=?, contact=?, birthday=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssi", $name, $age, $sex, $barangay, $address, $contact, $birthday, $id);

        if (!$stmt->execute()) {
            $errorMessage = "Invalid query: " . $conn->error;
            break;
        }

        $successMessage = "Resident updated successfully!";
        $stmt->close();

        header("location: /dashboard/main.php");
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
</head>
<body>
<div class="container my-5">
    <h2>Edit Resident</h2>

    <?php if (!empty($errorMessage)): ?>
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong><?= htmlspecialchars($errorMessage) ?></strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        <div class="row mb-3">
            <label for="name" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <label for="age" class="col-sm-2 col-form-label">Age</label>
            <div class="col-sm-6">
                <input type="number" class="form-control" name="age" value="<?= htmlspecialchars($age) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <label for="sex" class="col-sm-2 col-form-label">Sex</label>
            <div class="col-sm-6">
                <select class="form-select" name="sex">
                    <option value="male" <?= $sex == "male" ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $sex == "female" ? 'selected' : '' ?>>Female</option>
                    <option value="other" <?= $sex == "other" ? 'selected' : '' ?>>Other</option>
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
                <input type="date" class="form-control" name="birthday" value="<?= htmlspecialchars($birthday) ?>">
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
                <a class="btn btn-secondary" href="/dashboard/main.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>
</body>
</html>