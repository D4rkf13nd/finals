<?php
session_start();
require_once "backend/db.php";

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        
        // Add this after the database query
        if (!$stmt->execute()) {
            error_log("Query failed: " . $stmt->error);
            throw new Exception("Database error");
        }

        $result = $stmt->get_result();
        error_log("Query result rows: " . $result->num_rows);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Update last login
                $update = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                $update->bind_param("i", $user['user_id']);
                $update->execute();
                $update->close();

                $message = "Login successful";
                $toastClass = "bg-success";
                
                // Redirect based on role
                header("Location: " . ($user['role'] === 'admin' ? 'main.php' : 'user_panel/user.php'));
                exit();
            } else {
                $message = "Incorrect password";
                $toastClass = "bg-danger";
            }
        } else {
            $message = "Username not found";
            $toastClass = "bg-warning";
        }

        $stmt->close();
    } catch (Exception $e) {
        $message = "An error occurred. Please try again.";
        $toastClass = "bg-danger";
        error_log("Login error: " . $e->getMessage());
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

<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white <?php echo $toastClass; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <form action="" method="post" class="form-control mt-5 p-4" style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="row">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="text-align: center; color: green;"></i>
                <h5 class="text-center p-4" style="font-weight: 700;">Login to Population Dashboard</h5>
            </div>
            
            <div class="col-mb-3">
                <label for="username"><i class="fa fa-user"></i> Username</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            
            <div class="col mb-3 mt-3">
                <label for="password"><i class="fa fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            
            <div class="col mb-3 mt-3">
                <button type="submit" class="btn btn-success w-100">Login</button>
            </div>
            
            <div class="col mb-2 mt-4">
                <p class="text-center" style="font-weight: 600; color: navy;">
                    <a href="signup.php" style="text-decoration: none;">Create Account</a>
                </p>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        var toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 3000 });
        });
        toastList.forEach(toast => toast.show());
    </script>
</body>
</html>