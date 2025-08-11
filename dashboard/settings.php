<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}
// You can add PHP logic here for settings if needed in the future
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body.darkmode, .darkmode .main-content, .darkmode .sidebar, .darkmode #calendar, .darkmode .event-container, .darkmode .table-responsive {
            background: #181818 !important;
            color: #fff !important;
        }
        .darkmode .dashboard-header,
        .darkmode .counter-card,
        .darkmode .counter-total,
        .darkmode .counter-18,
        .darkmode .counter-31,
        .darkmode .counter-51,
        .darkmode .table,
        .darkmode .table-striped > tbody > tr:nth-of-type(odd),
        .darkmode .table thead.table-dark th,
        .darkmode .alert,
        .darkmode .form-control,
        .darkmode .form-select {
            background: #232323 !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        .darkmode .sidebar .nav-link,
        .darkmode .sidebar .nav-link.active {
            background: #232323 !important;
            color: #fff !important;
            border-color: #444 !important;
        }
        .darkmode .btn,
        .darkmode .btn-primary,
        .darkmode .btn-secondary,
        .darkmode .btn-success,
        .darkmode .btn-warning,
        .darkmode .btn-danger {
            filter: grayscale(1) invert(1) brightness(0.8);
        }
        .darkmode .form-label {
            color: #fff !important;
        }
        .darkmode .table-striped > tbody > tr > td {
            color: #fff !important;
        }
    </style>
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
        <a class="nav-link active" href="settings.php">Setting</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header =">
            <h1>Settings</h1>
            <p class="lead">Toggle dark mode for the entire website.</p>
        </div>
        <div class="text-center mb-4">
            <button id="darkmodeBtn" class="btn btn-dark">Dark Mode</button>
        </div>
        <div class="text-center mb-4">
            <form method="post" onsubmit="return confirm('Are you sure you want to delete All data');">
                <button type="submit" name="delete_all_pop_data" class="btn btn-danger">Delete All</button>
            </form>
            <?php
            if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_all_pop_data"])) {
                require_once "backend/db.php";
                $conn->query("DELETE FROM pop_data");
                echo '<div class="alert alert-success mt-3">All data has been deleted.</div>';
            }
            ?>
        </div>
        <div class="text-center">
            <div class="alert alert-info">Add your settings options here.</div>
        </div>
    </div>
    <script src="assets/setting.js"></script>
</body>
</html>