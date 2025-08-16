<?php
session_start();
require_once "backend/db.php"; // Create this for DB connection

$username = $password = $role = "";
$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $role = $_POST["role"] ?? "user";

    if (!$username || !$password) {
        $error = "All fields are required.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hash, $role);
        if ($stmt->execute()) {
            $success = "Account created! You can now <a href='index.php'>login</a>.";
        } else {
            $error = "Username already exists.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
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
<div class="container my-5" style="max-width:400px;">
    <h2 class="mb-4">Sign Up</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Username</label>
            <input name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input name="password" type="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button class="btn btn-primary w-100">Sign Up</button>
        <div class="mt-2 text-center">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </form>
</div>
</body>
</html>