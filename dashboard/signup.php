<?php
session_start();
require_once "backend/db.php";

$username = $password = $role = $email = "";
$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);
    $role = $_POST["role"] ?? "user";

    if (!$username || !$password || !$email) {
        $error = "All fields are required.";
    } else {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists. Please choose another.";
        } else {
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered. Please use another email.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hash, $role, $email);
                
                if ($stmt->execute()) {
                    $success = "Account created! You can now <a href='index.php'>login</a>.";
                } else {
                    $error = "An error occurred. Please try again.";
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/signup.css">
</head>
<body>
    <div class="signup-container">
        <h2 class="signup-title">Sign Up</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post" id="signupForm">
            <div class="form-group">
                <label>Username</label>
                <input name="username" class="form-control" required 
                       value="<?= htmlspecialchars($username) ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input name="email" type="email" class="form-control" required 
                       value="<?= htmlspecialchars($email) ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input name="password" type="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <button class="btn btn-signup w-100 mb-3">Sign Up</button>
            
            <div class="text-center">
                Already have an account? 
                <a href="index.php" class="signup-link">Login</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            
            if (!username || !email || !password) {
                e.preventDefault();
                alert('All fields are required');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return;
            }
        });
    </script>
</body>
</html>