<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["importFile"])) {
    $file = $_FILES["importFile"]["tmp_name"];
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Skip header row
        $header = fgetcsv($handle, 1000, ",");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Adjust column indexes as needed
            $name = $data[0] ?? '';
            $address = $data[1] ?? '';
            $age = intval($data[2] ?? 0);
            $gender = $data[3] ?? '';
            $contact = $data[4] ?? '';
            $birthday = $data[5] ?? '';

            // Calculate age from birthday if age is 0 or invalid
            if (!empty($birthday) && ($age == 0 || $age < 1)) {
                $birthDate = new DateTime($birthday);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;
            }

            $sql = "INSERT INTO pop_data (name, address, age, sex, contact, birthday) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisss", $name, $address, $age, $gender, $contact, $birthday);
            $stmt->execute();
        }
        fclose($handle);
        header("Location: ../main.php");
        exit;
    } else {
        echo "Error opening file.";
    }
} else {
    echo "No file uploaded.";
}
?>