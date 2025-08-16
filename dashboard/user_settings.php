<?php

session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: login.php");
    exit;
}

require_once "backend/db.php";

$successMessage = '';
$errorMessage = '';

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    
    $updateSuccess = false;
    
    // Handle profile picture upload
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["profile_picture"]["name"];
        $filetype = $_FILES["profile_picture"]["type"];
        $filesize = $_FILES["profile_picture"]["size"];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (array_key_exists($ext, $allowed) && $filesize < 5000000) {
            $newname = uniqid() . "." . $ext;
            $path = "uploads/profiles/" . $newname;
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $path)) {
                // Update profile picture in database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                if ($stmt === false) {
                    $errorMessage = "Error preparing statement: " . $conn->error;
                } else {
                    $stmt->bind_param("si", $path, $_SESSION["user_id"]);
                    $updateSuccess = $stmt->execute();
                    if (!$updateSuccess) {
                        $errorMessage = "Error updating profile picture: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // Update user information with error handling
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, contact = ? WHERE id = ?");
    if ($stmt === false) {
        $errorMessage = "Error preparing statement: " . $conn->error;
    } else {
        $stmt->bind_param("sssi", $name, $email, $contact, $_SESSION["user_id"]);
        $updateSuccess = $stmt->execute();
        if (!$updateSuccess) {
            $errorMessage = "Error updating profile: " . $stmt->error;
        } else {
            $successMessage = "Profile updated successfully!";
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    if ($new_password === $confirm_password) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user["password"])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION["user_id"]);
            $passwordSuccess = $stmt->execute();
        }
    }
}

// Get user data
$userData = [];
$stmt = $conn->prepare("SELECT name, email, contact, profile_picture FROM users WHERE id = ?");
if ($stmt === false) {
    $errorMessage = "Error preparing statement: " . $conn->error;
} else {
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
        }
    } else {
        $errorMessage = "Error fetching user data: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Settings</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Replace the include with actual navigation -->
    <nav class="sidebar">
        <div class="text-center mb-4">
            <div class="logo d-flex align-items-center gap-2">
                <img src="./assets/img/binky.png" alt="Binky-Logo" style="width: 200px; height: 200px; object-fit: contain;">
                <img src="./assets/img/tayo.png" alt="Tayo-Logo" style="width: 100px; height: 100px; margin-left: -80px;">
            </div>
            <h4 style="color:#fff;">Menu</h4>
        </div>
        <a class="nav-link" href="user.php">Home</a>
        <a class="nav-link active" href="user_settings.php">Settings</a>
        <a class="nav-link" href="logout.php">Logout</a>
    </nav>

    <div class="main-content">
        <div class="container py-4">
            <h2>Settings</h2>
            
            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            
            <div class="row mt-4">
                <div class="col-md-3">
                    <!-- Settings Navigation -->
                    <div class="list-group">
                        <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">Profile Settings</a>
                        <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">Account Security</a>
                        <a href="#preferences" class="list-group-item list-group-item-action" data-bs-toggle="list">Preferences</a>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="tab-content">
                        <!-- Profile Settings -->
                        <div class="tab-pane fade show active" id="profile">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Profile Settings</h5>
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label">Profile Picture</label>
                                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?= htmlspecialchars($userData['name'] ?? '') ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?= htmlspecialchars($userData['email'] ?? '') ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Contact</label>
                                            <input type="text" class="form-control" name="contact" 
                                                   value="<?= htmlspecialchars($userData['contact'] ?? '') ?>">
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Settings -->
                        <div class="tab-pane fade" id="security">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Change Password</h5>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preferences -->
                        <div class="tab-pane fade" id="preferences">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">System Preferences</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Theme</label>
                                        <select class="form-select" id="themeSelect">
                                            <option value="light">Light</option>
                                            <option value="dark">Dark</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary" id="savePreferences">Save Preferences</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/settings.js"></script>
</body>
</html>