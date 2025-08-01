<?php

$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "dashboard_db";

$conn = new mysqli($servername, $username, $password, $dbname);

$name = "";
$address = "";
$age = "";  
$sex = "";  
$contact = "";
$birthday = "";

$successmessage = "";
$errorMessage = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $contact = $_POST['contact'];
    $birthday = $_POST['birthday'];

    do {
        if (empty($name) || empty($address) || empty($age) || empty($sex) || empty($contact) || empty($birthday)) {
            $errorMessage = "All fields are required";
            break;
        }

        $sql = "INSERT INTO pop_data (name, address, age, sex, contact, birthday) VALUES 
        ('$name', '$address', '$age', '$sex', '$contact', '$birthday')";
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            $errorMessage = "Invalid query: " . mysqli_error($conn);
            break;
        }

        // add new resident to database
        $name = "";
        $address = "";
        $age = "";
        $sex = "";
        $contact = "";
        $birthday = "";

        $successmessage = "Resident added successfully";

        header("location: ../main.php");
        exit;
    } while (false);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Dos</title>
    <link rel="stylesheet" href="../frontend/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Add Resident Form Custom Styles */
        .add-resident-form {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(44,62,80,0.08);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 600px;
            margin: 2rem auto;
        }
        .add-resident-form h2 {
            font-weight: 700;
            color: #154db6;
            margin-bottom: 1.5rem;
        }
        .add-resident-form label {
            font-weight: 500;
            color: #34495E;
        }
        .add-resident-form input,
        .add-resident-form select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
        }
        .add-resident-form .btn-primary {
            background: #154db6;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 2rem;
        }
        .add-resident-form .btn-outline-primary {
            border-radius: 8px;
            font-weight: 600;
        }
        .add-resident-form .alert {
            margin-bottom: 1.2rem;
        }
        @media (max-width: 600px) {
            .add-resident-form {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
        <div class="container my-5">
    <div class="add-resident-form">
        <h2>New Resident</h2>

        <?php
        if  (!empty($errorMessage)) {  
            echo "
            <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                <strong>$errorMessage</strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
            ";
        }
        ?>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Name</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Address</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($address); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Age</label>
                <div class="col-sm-6">
                    <input type="number" class="form-control" name="age" value="<?php echo htmlspecialchars($age); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Sex</label>
                <div class="col-sm-6">
                    <select class="form-control" name="sex">
                        <option value="">Select</option>
                        <option value="Male" <?php if($sex=="Male") echo "selected"; ?>>Male</option>
                        <option value="Female" <?php if($sex=="Female") echo "selected"; ?>>Female</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Contact</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($contact); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Birthday</label>
                <div class="col-sm-6">
                    <input type="date" class="form-control" name="birthday" value="<?php echo htmlspecialchars($birthday); ?>">
                </div>
            </div>
            <?php
            if (!empty($successmessage)) {
                echo "
                <div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <strong>$successmessage</strong>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
                ";
            }
            ?>
            <div class="row mb-3">
                <div class="col-sm-6 offset-sm-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a class="btn btn-outline-primary" href="/dashboard/main.php" role="button">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>