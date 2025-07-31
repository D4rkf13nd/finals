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

        $sql = "INSERT INTO client (name, address, age, sex, contact, birthday) VALUES 
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
</head>
<body>
        <div class="container my-5">
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

             <form method="POST">
            <div class="row mb-3">
                <label class="col ism-3 col-form-label">Name</label>
                <div class="col-sm-6">
                <input type="text" class="form-control" name="name" value="<?php echo $name; ?>">
            </div>
        </div>
            <div class="row mb-3">
                <label class="col ism-3 col-form-label">Address</label>
                <div class="col-sm-6">
                <input type="text" class="form-control" name="address" value="<?php echo $address; ?>">
            </div>
        </div>
            <div class="row mb-3">
                <label class="col ism-3 col-form-label">Age</label>
                <div class="col-sm-6">
                <input type="text" class="form-control" name="age" value="<?php echo $age; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col ism-3 col-form-label">Sex</label>
                <div class="col-sm-6">
                <input type="text" class="form-control" name="sex" value="<?php echo $sex; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col ism-3 col-form-label">Contact</label>
                <div class="col-sm-6">
                <input type="text" class="form-control" name="contact" value="<?php echo $contact; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col ism-3 col-form-label">Birthday</label>
                <div class="col-sm-6">
                <input type="date" class="form-control" name="birthday" value="<?php echo $birthday; ?>">
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
                <div class="col-sm-6 offset-sm-3">
                <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>

        <div class="col-sm-3 d-grid">
            <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="/dashboard/main.php" role="button">Cancel</a>
            </div>

         </div>
        </form>
</body>
</html>