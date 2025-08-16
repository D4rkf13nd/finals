<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
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
    <title>Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body.darkmode, .darkmode .main-content, .darkmode .sidebar, .darkmode #calendar, .darkmode .event-container, .darkmode .table-responsive {
            background: #181818 !important;
            color: #fff !important;
        }
        .darkmode .dashboard-header,
        .darkmode .counter-card,
        .darkmode .counter-total,
        .darkmode .counter-18,
        .darkmode .counter-31,
        .darkmode .counter-51,
        .darkmode .table,
        .darkmode .table-striped > tbody > tr:nth-of-type(odd),
        .darkmode .table thead.table-dark th,
        .darkmode .alert,
        .darkmode .form-control,
        .darkmode .form-select {
            background: #232323 !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        .darkmode .sidebar .nav-link,
        .darkmode .sidebar .nav-link.active {
            background: #232323 !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        .darkmode .btn,
        .darkmode .btn-primary,
        .darkmode .btn-secondary,
        .darkmode .btn-success,
        .darkmode .btn-warning,
        .darkmode .btn-danger {
            filter: grayscale(1) invert(1) brightness(0.8);
        }
        .darkmode .form-label {
            color: #fff !important;
        }
        .darkmode .table-striped > tbody > tr > td {
            color: #fff !important;
        }

        .settings-container {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .settings-section {
            margin-bottom: 30px;
        }

        .settings-section h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .darkmode .settings-container {
            background: #232323;
        }

        .darkmode .settings-section h3 {
            color: #fff;
            border-bottom-color: #444;
        }

        .nav-tabs .nav-link {
            color: #333;
        }

        .darkmode .nav-tabs .nav-link {
            color: #fff;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
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