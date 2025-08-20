<?php
session_start();
require_once "backend/db.php";

// Get user details
function getUserDetails($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get system settings
function getSystemSettings($conn) {
    $stmt = $conn->prepare("SELECT * FROM settings");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $contact = trim($_POST["contact"]);
    
    // Handle profile picture upload
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["profile_picture"]["name"];
        $filetype = $_FILES["profile_picture"]["type"];
        $filesize = $_FILES["profile_picture"]["size"];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            $error = "Error: Please select a valid file format.";
        }
        
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $error = "Error: File size is larger than the allowed limit.";
        }
        
        if (!isset($error)) {
            $newname = uniqid() . "." . $ext;
            move_uploaded_file($_FILES["profile_picture"]["tmp_name"], "uploads/" . $newname);
            
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newname, $_SESSION["user_id"]);
            $stmt->execute();
        }
    }
    
    // Update user details
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, contact = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $name, $email, $contact, $_SESSION["user_id"]);
    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
    } else {
        $error = "Error updating profile.";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current = $_POST["current_password"];
    $new = $_POST["new_password"];
    $confirm = $_POST["confirm_password"];
    
    if ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current, $result["password"])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hash, $_SESSION["user_id"]);
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error changing password.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Get user details
$userDetails = getUserDetails($conn, $_SESSION["user_id"]);
$systemSettings = getSystemSettings($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Update existing styles */
        .settings-container {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .settings-section {
            margin-bottom: 20px;
        }

        .settings-section h3 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-size: 1.2rem;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        /* Form controls */
        .form-control, .form-select {
            height: 38px;
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        /* Buttons */
        .btn {
            padding: 6px 15px;
            font-size: 0.9rem;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* Modal adjustments */
        .modal-dialog {
            max-width: 450px;
        }

        .list-group-item {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* Dark mode adjustments */
        .darkmode .settings-container {
            background: #232323;
        }

        .darkmode .settings-section h3 {
            color: #fff;
            border-bottom-color: #444;
        }

        /* Dashboard header */
        .dashboard-header {
            margin-bottom: 1.5rem;
        }

        .dashboard-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .lead {
            font-size: 1rem;
        }
    </style>
</head>
<body>
 <nav class="sidebar">
        <div class="text-center mb-115">
            <div class="logo d-flex align-items-center gap-2">
                <img src="./assets/img/binky.png" alt="Binky-Logo" style="width: 200px; height: 200px; object-fit: contain;">
                <img src="./assets/img/tayo.png" alt="Tayo-Logo" style="width: 100px; height: 100px; margin-left: -80px;">
            </div>
            <h4 style="color:#000000;">Menu</h4>
        </div>
        <a class="nav-link" href="main.php">Dashboard</a>
        <a class="nav-link" href="calendar.php">Calendar</a>
        <a class="nav-link active" href="settings.php">Setting</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Settings</h1>
            <p class="lead">Manage your account and system preferences</p>
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
                    <a class="nav-link" data-bs-toggle="tab" href="#system">System</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#barangay">Barangay</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#users">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#notifications">Notifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#about">About</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Profile Settings -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="settings-section">
                        <h3>Profile Settings</h3>
                        <form method="post" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <img src="assets/img/default-profile.png" class="profile-picture" id="profilePreview">
                                <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($userDetails['name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($userDetails['email']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="contact" value="<?php echo htmlspecialchars($userDetails['contact']); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security">
                    <div class="settings-section">
                        <h3>Account Security</h3>
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
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">Two-Factor Authentication</label>
                            <button class="btn btn-secondary">Enable 2FA</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Active Sessions</label>
                            <button class="btn btn-danger">Log Out All Devices</button>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="tab-pane fade" id="system">
                    <div class="settings-section">
                        <h3>System Settings</h3>
                        <div class="mb-3">
                            <label class="form-label">Theme</label>
                            <select class="form-select" id="themeSelect">
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data Refresh Interval</label>
                            <select class="form-select">
                                <option>Real-time</option>
                                <option>Hourly</option>
                                <option>Daily</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time Zone</label>
                            <select class="form-select">
                                <option>Asia/Manila</option>
                                <!-- Add more time zones -->
                            </select>
                        </div>
                        <button class="btn btn-primary">Save Settings</button>
                    </div>
                    <hr class="my-4">
                    <div class="settings-section">
                        <h3 class="text-danger">Danger Zone</h3>
                        <div class="card border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Clear All Data</h5>
                                <p class="card-text">This action will permanently delete all resident records, events, and system data. This cannot be undone.</p>
                                <button type="button" class="btn btn-danger" id="clearDataBtn" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                                    Clear All Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add remaining tabs content here -->
            </div>
        </div>
    </div>

    <div class="modal fade" id="clearDataModal" tabindex="-1" aria-labelledby="clearDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="clearDataModalLabel">⚠️ Warning: Clear All Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to delete all data from the system. This includes:</p>
                <ul class="list-group mb-3">
                    <li class="list-group-item text-danger">✗ All resident records</li>
                    <li class="list-group-item text-danger">✗ All event data</li>
                    <li class="list-group-item text-danger">✗ All system settings</li>
                    <li class="list-group-item text-danger">✗ All uploaded files</li>
                </ul>
                <div class="alert alert-warning">
                    <strong>This action cannot be undone!</strong>
                </div>
                <div class="mb-3">
                    <label for="confirmDelete" class="form-label">Type "DELETE" to confirm:</label>
                    <input type="text" class="form-control" id="confirmDelete" placeholder="DELETE">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmClearData" disabled>
                    Clear All Data
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/setting.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Clear Data Confirmation
    const confirmDelete = document.getElementById('confirmDelete');
    const confirmButton = document.getElementById('confirmClearData');

    if (confirmDelete && confirmButton) {
        confirmDelete.addEventListener('input', function() {
            confirmButton.disabled = this.value !== 'DELETE';
        });

        confirmButton.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Clearing...';

            // Send delete request to server
            fetch('backend/clear_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    confirmation: confirmDelete.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to clear data: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = 'Clear All Data';
                }
            })
            .catch(error => {
                alert('An error occurred while clearing data');
                this.disabled = false;
                this.innerHTML = 'Clear All Data';
            });
        });
    }
});
    </script>
</body>
</html>