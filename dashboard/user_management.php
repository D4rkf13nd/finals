<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

require_once "backend/db.php";

// Handle user actions
$message = '';
$error = '';

// Add user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $email = trim($_POST['email']);

    if (empty($username) || empty($password) || empty($role)) {
        $error = "All fields are required";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $role, $email);
        
        if ($stmt->execute()) {
            $message = "User added successfully";
        } else {
            $error = "Error adding user";
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    if ($user_id != $_SESSION['user_id']) { // Prevent self-deletion
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "User deleted successfully";
        } else {
            $error = "Error deleting user";
        }
    } else {
        $error = "Cannot delete your own account";
    }
}

// Fetch users - Update query to use user_id
$users = [];
$result = $conn->query("SELECT user_id, username, role, email, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
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
        <a class="nav-link" href="settings.php">Setting</a>
        <a class="nav-link active" href="user_management.php">User Management</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>

    <div class="main-content position-relative">
        <div class="dashboard-header">
            <h1>User Management</h1>
            <p class="lead">Manage system users and access control</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New User</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" name="add_user" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                </span></td>
                                <td><?= htmlspecialchars($user['created_at']) ?></td>
                                <td>
                                    <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/setting.js"></script>
    <script>
    document.querySelector('input[name="username"]').addEventListener('blur', function() {
        const username = this.value;
        if (username) {
            fetch(`check_username.php?username=${encodeURIComponent(username)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        this.setCustomValidity('Username already exists');
                        this.reportValidity();
                    } else {
                        this.setCustomValidity('');
                    }
                });
        }
    });

    document.querySelector('input[name="email"]').addEventListener('blur', function() {
        const email = this.value;
        if (email) {
            fetch(`check_email.php?email=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        this.setCustomValidity('Email already exists');
                        this.reportValidity();
                    } else {
                        this.setCustomValidity('');
                    }
                });
        }
    });
    </script>
</body>
</html>
<?php
require_once "backend/db.php";

header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
} else {
    echo json_encode(['error' => 'No username provided']);
}
?>
<?php
require_once "backend/db.php";

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
} else {
    echo json_encode(['error' => 'No email provided']);
}
?>