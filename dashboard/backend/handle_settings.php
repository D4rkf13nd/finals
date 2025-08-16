<?php

session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

function handleProfileUpdate($conn) {
    $userId = $_SESSION["user_id"];
    $name = filter_var($_POST["name"], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $contact = filter_var($_POST["contact"], FILTER_SANITIZE_STRING);

    // Handle profile picture upload
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["profile_picture"]["name"];
        $filetype = $_FILES["profile_picture"]["type"];
        $filesize = $_FILES["profile_picture"]["size"];

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            return ["success" => false, "message" => "Invalid file format"];
        }

        $maxsize = 5 * 1024 * 1024; // 5MB
        if ($filesize > $maxsize) {
            return ["success" => false, "message" => "File size exceeds limit"];
        }

        $newname = uniqid() . "." . $ext;
        $path = "../uploads/profiles/" . $newname;
        
        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $path)) {
            return ["success" => false, "message" => "Failed to upload file"];
        }
        
        $profile_picture = "uploads/profiles/" . $newname;
    }

    $sql = "INSERT INTO user_settings (user_id, name, email, contact, profile_picture) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            name = VALUES(name), 
            email = VALUES(email), 
            contact = VALUES(contact)" .
            (isset($profile_picture) ? ", profile_picture = VALUES(profile_picture)" : "");

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $name, $email, $contact, $profile_picture ?? '');
    
    return ["success" => $stmt->execute()];
}

function handlePasswordChange($conn) {
    $userId = $_SESSION["user_id"];
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($currentPassword, $user["password"])) {
        return ["success" => false, "message" => "Current password is incorrect"];
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    return ["success" => $stmt->execute()];
}

function handleSystemSettings($conn) {
    $userId = $_SESSION["user_id"];
    $theme = filter_var($_POST["theme"], FILTER_SANITIZE_STRING);
    $refreshInterval = filter_var($_POST["refresh_interval"], FILTER_SANITIZE_STRING);
    $timezone = filter_var($_POST["timezone"], FILTER_SANITIZE_STRING);

    $sql = "UPDATE user_settings SET 
            theme = ?, 
            refresh_interval = ?, 
            timezone = ? 
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $theme, $refreshInterval, $timezone, $userId);
    
    return ["success" => $stmt->execute()];
}

// Handle different types of requests
$action = $_POST["action"] ?? '';
$response = [];

switch ($action) {
    case 'update_profile':
        $response = handleProfileUpdate($conn);
        break;
    case 'change_password':
        $response = handlePasswordChange($conn);
        break;
    case 'system_settings':
        $response = handleSystemSettings($conn);
        break;
    default:
        $response = ["success" => false, "message" => "Invalid action"];
}

header('Content-Type: application/json');
echo json_encode($response);
?>