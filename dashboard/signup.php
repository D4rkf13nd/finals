<?php
session_start();
require_once "backend/db.php";

$username = $password = "";
$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        
        // Basic validation
        if (empty($username) || empty($password)) {
            throw new Exception("All fields are required.");
        }

        // Check username
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        if (!$check_stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Username already exists. Please choose another.");
        }
        $check_stmt->close();

        // Insert new user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("ss", $username, $hash);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating account: " . $stmt->error);
        }

        $success = "Account created successfully! You can now <a href='index.php'>login</a>.";
        $stmt->close();

    } catch (Exception $e) {
        $error = $e->getMessage();
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
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post" id="signupForm" novalidate>
            <div class="form-group mb-3">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= htmlspecialchars($username) ?>" required>
            </div>

            <div class="form-group mb-3">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       minlength="6" required>
            </div>

            <button type="submit" class="btn btn-signup w-100 mb-3">Sign Up</button>
            
            <div class="text-center">
                Already have an account? 
                <a href="index.php" class="signup-link">Login</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
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