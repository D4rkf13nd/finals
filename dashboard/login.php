<?php
session_start();

// Check if config exists
if (!file_exists("backend/db.php")) {
    die("Database configuration file not found!");
}

require_once "backend/db.php";

// Clear any existing session if not logged in
if (!isset($_SESSION["user_id"])) {
    session_unset();
    session_destroy();
    session_start();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Prevent SQL injection using prepared statements
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user["password"])) {
                    // Set session variables
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    
                    // Redirect based on role
                    if ($user["role"] === "admin") {
                        header("Location: main.php");
                    } else {
                        header("Location: user.php");
                    }
                    exit;
                }
            }
            $error = "Invalid username or password.";
            $stmt->close();
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
            // Log the actual error for debugging
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(30, 64, 175, 0.10);
            padding: 40px 30px 30px 30px;
            max-width: 400px;
            width: 90%;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-container img {
            max-width: 120px;
            height: auto;
        }
        h2 {
            font-weight: 700;
            color: #2563eb;
            text-align: center;
            margin-bottom: 1.5rem;
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
        .signup-link {
            text-align: center;
            margin-top: 1rem;
        }
        .signup-link a {
            color: #2563eb;
            text-decoration: underline;
        }
        .signup-link a:hover {
            color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
        </div>
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    required 
                    autocomplete="username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                >
                <div class="invalid-feedback">
                    Please enter your username.
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                >
                <div class="invalid-feedback">
                    Please enter your password.
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log In</button>
            <div class="signup-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>