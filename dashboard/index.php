<?php
session_start();
require_once "backend/db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Please enter both username and password";
        $messageType = "danger";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("s", $username);
            
            if (!$stmt->execute()) {
                throw new Exception("Query failed: " . $stmt->error);
            }

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: main.php");
                    } else {
                        header("Location: user_panel/user.php");
                    }
                    exit();
                } else {
                    $message = "Incorrect password";
                    $messageType = "danger";
                }
            } else {
                $message = "Username not found";
                $messageType = "warning";
            }

            $stmt->close();
        } catch (Exception $e) {
            $message = "An error occurred. Please try again.";
            $messageType = "danger";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Population Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(30, 64, 175, 0.10);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            color: #198754;
            margin-bottom: 1rem;
        }
        .login-header h5 {
            color: #333;
            font-weight: 700;
        }
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .alert {
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="login-header">
            <i class="fa fa-user-circle-o fa-3x"></i>
            <h5>Login to Population Dashboard</h5>
        </div>

        <form method="post" action="">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fa fa-user"></i> Username
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?= htmlspecialchars($username ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fa fa-lock"></i> Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-success w-100 mb-3">Login</button>

            <div class="text-center">
                <a href="signup.php" class="text-decoration-none fw-bold">Create Account</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>