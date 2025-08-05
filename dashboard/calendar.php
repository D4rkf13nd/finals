<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Database connection for events
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

// Handle event delete
if (isset($_GET['delete_event'])) {
    $delete_id = intval($_GET['delete_event']);
    $conn->query("DELETE FROM events WHERE id = $delete_id");
    header("Location: calendar.php");
    exit;
}

// Handle event add form
$addSuccess = "";
$addError = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["event_title"])) {
    $title = $_POST["event_title"];
    $type = $_POST["event_type"];
    $date = $_POST["event_date"];
    $time = $_POST["event_time"];
    $start = $date . " " . $time;
    $end = $start; // For simplicity, single datetime

    if (!$title || !$type || !$date || !$time) {
        $addError = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO events (title, type, start_date, end_date, time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $type, $date, $date, $time);
        if ($stmt->execute()) {
            $addSuccess = "Event added!";
        } else {
            $addError = "Failed to add event.";
        }
        $stmt->close();
        // Refresh to show new event
        header("Location: calendar.php");
        exit;
    }
}

// Fetch events from the events table
$events = [];
$sql = "SELECT * FROM events";
$result = $conn->query($sql);
$eventList = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "title" => $row["title"] . (isset($row["type"]) && $row["type"] ? " ({$row["type"]})" : ""),
            "start" => $row["start_date"] . (isset($row["time"]) && $row["time"] ? "T" . $row["time"] : ""),
            "end"   => $row["end_date"] . (isset($row["time"]) && $row["time"] ? "T" . $row["time"] : "")
        ];
        $eventList[] = $row; // For the event list with id
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
    <style>
        #calendar { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #0001; padding: 10px; }
        .event-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #0001; padding: 20px; min-height: 350px; }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="text-center mb-4">
            <div class="logo d-flex align-items-center gap-2">
        <img src="./assets/img/binky.png" alt="Binky-Logo" style="width: 200px; height: 200px; object-fit: contain;">
        <img src="./assets/img/tayo.png" alt="Tayo-Logo" style="width: 100px; height: 100px; margin-left: -80px;">
      </div>
            <h4 style="color:#fff;">Menu</h4>
        </div>
        <a class="nav-link" href="main.php">Dashboard</a>
        <a class="nav-link active" href="calendar.php">Calendar</a>
        <a class="nav-link" href="settings.php">Setting</a>
        <a class="nav-link" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header text-center">
            <h1>Calendar</h1>
            <p class="lead">View and manage your events.</p>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <div id="calendar"></div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="event-container">
                    <h5>Add Event</h5>
                    <?php if ($addSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($addSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($addError): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($addError) ?></div>
                    <?php endif; ?>
                    <form method="post" class="mb-4">
                        <div class="mb-2">
                            <label class="form-label">Name of Event</label>
                            <input type="text" name="event_title" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Type</label>
                            <select name="event_type" class="form-select" required>
                                <option value="">Select type</option>
                                <option value="Meeting">Meeting</option>
                                <option value="Holiday">Holiday</option>
                                <option value="Birthday">Birthday</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Time</label>
                            <input type="time" name="event_time" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </form>
                    <h5>Events</h5>
                    <ul class="list-group">
                        <?php if (count($eventList)): ?>
                            <?php foreach ($eventList as $event): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($event['title']) ?></strong><br>
                                        <?= htmlspecialchars($event['start_date']) ?> <?= htmlspecialchars($event['time']) ?>
                                    </div>
                                    <a href="calendar.php?delete_event=<?= $event['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this event?');">Delete</a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No events found.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 400,
                events: <?= json_encode($events) ?>
            });
            calendar.render();
        });
    </script>
    <script src="assets/setting.js"></script>
</body>
</html>