<?php
session_start();
require_once "backend/db.php";

// If already logged in, redirect to appropriate page
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: main.php");
    } else {
        header("Location: user.php");
    }
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    
    try {
        // Select specific columns instead of *
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user["password"])) {
            // Set session variables using correct column names
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            // Update last login timestamp with error checking
            $update = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
            if ($update === false) {
                throw new Exception("Update prepare failed: " . $conn->error);
            }

            if (!$update->bind_param("i", $user["user_id"])) {
                throw new Exception("Update binding failed: " . $update->error);
            }

            if (!$update->execute()) {
                throw new Exception("Update failed: " . $update->error);
            }
            $update->close();

            // Redirect based on role
            header("Location: " . ($user["role"] === "admin" ? "main.php" : "user.php"));
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again.";
        error_log("Login error: " . $e->getMessage());
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
body {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
}
.container {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(30, 64, 175, 0.10);
    padding: 40px 30px 30px 30px;
}
h2 {
    font-weight: 700;
    color: #2563eb;
    text-align: center;
}
.form-label, label {
    font-weight: 500;
    color: #374151;
}
.btn-primary {
    background: #2563eb;
    border: none;
    font-weight: 600;
    letter-spacing: 1px;
}
.btn-primary:hover {
    background: #1d4ed8;
}
.alert {
    margin-bottom: 18px;
}
a {
    color: #2563eb;
    text-decoration: underline;
}
a:hover {
    color: #1d4ed8;
}
</style>
</head>
<body>
<div class="container" style="max-width:400px;">
    <h2 class="mb-4">Login</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Username</label>
            <input name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input name="password" type="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Login</button>
        <div class="mt-2 text-center">
            No account? <a href="signup.php">Sign Up</a>
        </div>
    </form>
</div>
</body>
</html>
