<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

// Add these variables for event handling
$addSuccess = "";
$addError = "";

// Handle event add form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["event_title"])) {
    $title = $_POST["event_title"];
    $type = $_POST["event_type"];
    $date = $_POST["event_date"];
    $time = $_POST["event_time"];

    if (!$title || !$type || !$date || !$time) {
        $addError = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO events (title, type, start_date, end_date, time, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            if (!$stmt->bind_param("sssssi", $title, $type, $date, $date, $time, $_SESSION["user_id"])) {
                throw new Exception("Binding parameters failed: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $addSuccess = "Event added successfully!";
            header("Location: calendar.php");
            exit;
        } catch (Exception $e) {
            $addError = "Error: " . $e->getMessage();
        }
    }
}

// Handle event delete
if (isset($_GET['delete_event'])) {
    $delete_id = intval($_GET['delete_event']);
    // Only allow deletion of events created by the current user
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $_SESSION["user_id"]);
    $stmt->execute();
    header("Location: calendar.php");
    exit;
}

// Modify the event fetch query to only show events for this user
$events = [];
$sql = "SELECT * FROM events WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$eventList = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "title" => $row["title"] . (isset($row["type"]) && $row["type"] ? " ({$row["type"]})" : ""),
            "start" => $row["start_date"] . (isset($row["time"]) && $row["time"] ? "T" . $row["time"] : ""),
            "end"   => $row["end_date"] . (isset($row["time"]) && $row["time"] ? "T" . $row["time"] : "")
        ];
        $eventList[] = $row;
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
    <link rel="stylesheet" href="../assets/style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="css/calendar.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
</head>
<body>
    <nav class="sidebar">
        <div class="text-center mb-115">
            <div class="logo d-flex align-items-center gap-2">
                <img src="../assets/img/binky.png" alt="Binky-Logo" style="width: 200px; height: 200px; object-fit: contain;">
                <img src="../assets/img/tayo.png" alt="Tayo-Logo" style="width: 100px; height: 100px; margin-left: -80px;">
            </div>
            <h4 style="color:#000000;">Menu</h4>
        </div>
        <a class="nav-link" href="user.php">Dashboard</a>
        <a class="nav-link active" href="calendar.php">Calendar</a>
        <a class="nav-link" href="setting.php">Setting</a>
        <a class="nav-link" style="color: red;" href="../logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Calendar</h1>
            <p class="lead">View upcoming events.</p>
        </div>
        <div class="row">
            <div class="col-md-9 mb-3">
                <div id="calendar" style="height: 600px;"></div>
            </div>
            <div class="col-md-3 mb-3">
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
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid/main.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 600,
                events: <?= json_encode($events) ?>,
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                eventDisplay: 'block',
                displayEventTime: true,
                eventColor: '#3788d8'
            });
            calendar.render();
        });
    </script>
    <script src="../assets/setting.js"></script>
</body>
</html>