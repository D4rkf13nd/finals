<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

require_once "backend/db.php";

// Initialize variables
$message = '';
$error = '';

try {
    // Add user
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = 'user'; // Set default role to user

        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required");
        }

        // Check if username exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Username already exists");
        }
        $check_stmt->close();

        // Insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding user: " . $conn->error);
        }
        $message = "User added successfully";
    }

    // Delete user
    if (isset($_GET['delete'])) {
        $user_id = (int)$_GET['delete'];
        if ($user_id == $_SESSION['user_id']) {
            throw new Exception("Cannot delete your own account");
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting user: " . $conn->error);
        }
        $message = "User deleted successfully";
    }

    // Fetch users
    $users = [];
    $result = $conn->query("SELECT user_id, username, role, created_at FROM users ORDER BY created_at DESC");
    if (!$result) {
        throw new Exception("Error fetching users: " . $conn->error);
    }
    $users = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
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
            <p class="lead">Manage system users</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">System Users</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    Add User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['created_at']) ?></td>
                                <td>
                                    <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $user['user_id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure?')">Delete</a>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>