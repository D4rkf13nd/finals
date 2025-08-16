<?php
session_start();
// Change admin check to user check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    // Add profile update logic here
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    // Add password change logic here
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <!-- Keep the darkmode styles -->
    <style>
        /* Copy the darkmode styles from the original file */
        body.darkmode, .darkmode .main-content, .darkmode .sidebar {
            background: #181818 !important;
            color: #fff !important;
        }
        /* Add other darkmode styles... */
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="text-center mb-115">
            <div class="logo d-flex align-items-center gap-2">
                <img src="../assets/img/binky.png" alt="Binky-Logo" style="width: 200px; height: 200px; object-fit: contain;">
                <img src="../assets/img/tayo.png" alt="Tayo-Logo" style="width: 100px; height: 100px; margin-left: -80px;">
            </div>
            <h4 style="color:#000000;">Menu</h4>
        </div>
        <a class="nav-link" href="user.php">Dashboard</a>
        <a class="nav-link" href="calendar.php">Calendar</a>
        <a class="nav-link active" href="setting.php">Settings</a>
        <a class="nav-link" style="color: red;" href="../logout.php">Logout</a>
    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Settings</h1>
            <p class="lead">Manage your account preferences</p>
        </div>

        <div class="settings-container">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#profile">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#security">Security</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#system">Preferences</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Profile Settings -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="settings-section">
                        <h3>Profile Settings</h3>
                        <form method="post" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <img src="../assets/img/default-profile.png" class="profile-picture" id="profilePreview">
                                <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="contact">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security">
                    <div class="settings-section">
                        <h3>Security Settings</h3>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="tab-pane fade" id="system">
                    <div class="settings-section">
                        <h3>System Preferences</h3>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/setting.js"></script>
</body>
</html>