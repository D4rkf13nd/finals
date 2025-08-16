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

// Edit user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['edit_user_id'];
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $role = $_POST['edit_role'];
    $password = trim($_POST['edit_password']);

    // Don't allow editing your own user
    if ($user_id === $_SESSION['user_id']) {
        $error = "Cannot edit your own account";
    } else {
        try {
            // Start with basic update
            $sql = "UPDATE users SET username = ?, email = ?, role = ?";
            $params = [$username, $email, $role];
            $types = "sss";

            // If password is provided, add it to the update
            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
                $types .= "s";
            }

            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $message = "User updated successfully";
                // Refresh the page to show updated data
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
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
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-primary btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?= $user['user_id'] ?>">
                                                Edit
                                            </button>
                                            <a href="?delete=<?= $user['user_id'] ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                Delete
                                            </a>
                                        </div>
                                        
                                        <!-- Edit Modal for each user -->
                                        <div class="modal fade" id="editModal<?= $user['user_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit User: <?= htmlspecialchars($user['username']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="edit_user_id" value="<?= $user['user_id'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Username</label>
                                                                <input type="text" class="form-control" 
                                                                       value="<?= htmlspecialchars($user['username']) ?>" 
                                                                       name="edit_username" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" class="form-control" 
                                                                       value="<?= htmlspecialchars($user['email']) ?>" 
                                                                       name="edit_email" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Role</label>
                                                                <select class="form-select" name="edit_role" required>
                                                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">New Password (leave blank to keep current)</label>
                                                                <input type="password" class="form-control" name="edit_password">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="edit_user" class="btn btn-primary">Save changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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
    <script src="assets/js/user-validation.js"></script>
</body>
</html>