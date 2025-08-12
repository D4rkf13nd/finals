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
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <style>
    body.darkmode,
    .darkmode .main-content,
    .darkmode .sidebar,
    .darkmode #calendar,
    .darkmode .event-container,
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
        background: #181818 !important;
        color: white !important;
         border: 2px solid #fff !important;
}
    
    .darkmode .form .form-label,
    .darkmode .sidebar .nav-link,
    .darkmode .sidebar .nav-link.active {
        background: #232323 !important;
        color: #fff !important;
        border-color: white !important;
    }

    .darkmode .btn,
    .darkmode .btn-primary,
    .darkmode .btn-secondary,
    .darkmode .btn-success,
    .darkmode .btn-warning,
    .darkmode .btn-danger {
        filter: grayscale(1) invert(1) brightness(0.8);
    }

    .darkmode .form-label,
    .darkmode .table-striped > tbody > tr > td {
        color: black!important;
    }
    
        #calendar {
        background: #ffffffff;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .fc-header-toolbar {
        padding: 15px;
        margin-bottom: 0 !important;
    }

    .fc-day-today {
        background-color: rgba(55, 136, 216, 0.1) !important;
    }

    .fc-event {
        border-radius: 3px;
        padding: 3px 5px;
        margin: 2px 0;
        border: none;
        cursor: pointer;
    }

    .fc-daygrid-event {
        white-space: normal;
    }

    /* Dark mode support */
    .darkmode .fc {
        background-color: #181818;
    }

    .darkmode .fc-toolbar-title,
    .darkmode .fc th {
        color: #fff;
    }

        .event-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #0001; padding: 20px; min-height: 350px; }

    /* Weekday Header Colors */
    .fc-col-header-cell {
        background-color: #070bffff !important;
        padding: 10px 0 !important;
    }

    .fc-col-header-cell-cushion {
        color: white !important;
        font-weight: 600 !important;
        text-decoration: none !important;
    }

    /* Weekend Days Different Color */


    /* Today's Date Highlight */
    .fc-day-today {
        background-color: rgba(74, 144, 226, 0.1) !important;
    }

    /* Dark Mode Support */
    .darkmode .fc-col-header-cell {
        background-color: #2c3e50 !important;
    }

    .darkmode .fc-day-sat,
    .darkmode .fc-day-sun {
        background-color: #222222 !important;
    }

    .darkmode .fc-day-today {
        background-color: rgba(74, 144, 226, 0.2) !important;
    }

    .darkmode .fc-col-header-cell-cushion {
        color: #000000ff !important;
    }

    /* Date Numbers Color */
    .fc-daygrid-day-number {
        color: #333333 !important;
        font-weight: 600 !important;
        font-size: 1.1em !important;
        padding: 8px !important;
        text-decoration: none;
    }

    /* Weekend Days */
    .fc-day-sat .fc-daygrid-day-number,
    .fc-day-sun .fc-daygrid-day-number {
        color: #000000ff !important;  /* Red color for weekends */
    }

    /* Weekday Colors */
    /* Today's Date Special Styling */
    .fc-day-today .fc-daygrid-day-number {
        color: #000000ff !important;
        background-color: #fcff36ff !important;
        border-radius: 50% !important;
        width: 30px !important;
        height: 30px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 5px !important;
    }

    /* Dark Mode Support */
    .darkmode .fc-daygrid-day-number {
        color: #ffffff !important;
    }

    .darkmode .fc-day-mon { background-color: #0080ffff !important; }
    .darkmode .fc-day-tue { background-color: #00ff80ff !important; }
    .darkmode .fc-day-wed { background-color: #3c2b1a !important; }
    .darkmode .fc-day-thu { background-color: #2b1a3c !important; }
    .darkmode .fc-day-fri { background-color: #3c1a1a !important; }
    .darkmode .fc-day-sat { background-color: #2a2a2a !important; }
    .darkmode .fc-day-sun { background-color: #2a2a2a !important; }

    .darkmode .fc-day-today .fc-daygrid-day-number {
        background-color: #4a90e2 !important;
        color: #000000ff !important;
    }

    .darkmode .fc-day-sat .fc-daygrid-day-number,
    .darkmode .fc-day-sun .fc-daygrid-day-number {
        color: #ff6b6b !important;
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
        <a class="nav-link active" href="calendar.php">Calendar</a>
        <a class="nav-link" href="settings.php">Setting</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Calendar</h1>
            <p class="lead">View and manage your events.</p>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <div id="calendar" style="height: 600px;"></div>
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
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid/main.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/interaction/main.js'></script>
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
                eventColor: '#3788d8',
                eventClick: function(info) {
                    if (confirm('Do you want to delete this event?')) {
                        window.location.href = 'calendar.php?delete_event=' + info.event.id;
                    }
                }
            });
            calendar.render();
        });
    </script>
    <script src="assets/setting.js"></script>
</body>
</html>
