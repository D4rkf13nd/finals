<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

require_once "../backend/db.php";

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify table exists and has correct structure
$checkTable = $conn->query("SHOW TABLES LIKE 'events'");
if ($checkTable->num_rows == 0) {
    // Create events table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        time TIME NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    if (!$conn->query($createTable)) {
        die("Error creating table: " . $conn->error);
    }
} else {
    // Check and add missing columns
    $columns = [
        "time" => "ADD COLUMN time TIME NOT NULL AFTER end_date",
        "user_id" => "ADD COLUMN user_id INT NOT NULL AFTER time, 
                     ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE"
    ];

    foreach ($columns as $column => $alterSql) {
        $checkColumn = $conn->query("SHOW COLUMNS FROM events LIKE '$column'");
        if ($checkColumn->num_rows == 0) {
            $alterTable = "ALTER TABLE events $alterSql";
            if (!$conn->query($alterTable)) {
                die("Error adding $column column: " . $conn->error);
            }
        }
    }
}

// Handle event delete
if (isset($_GET['delete_event'])) {
    $delete_id = intval($_GET['delete_event']);
    $user_id = $_SESSION["user_id"];
    // Only allow users to delete their own events
    $conn->query("DELETE FROM events WHERE id = $delete_id AND user_id = $user_id");
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
    $user_id = $_SESSION["user_id"];

    if (!$title || !$type || !$date || !$time) {
        $addError = "All fields are required.";
    } else {
        try {
            // Prepare statement with error checking
            $stmt = $conn->prepare("INSERT INTO events (title, type, start_date, end_date, time, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            if (!$stmt->bind_param("sssssi", $title, $type, $date, $date, $time, $user_id)) {
                throw new Exception("Binding parameters failed: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $addSuccess = "Event added successfully!";
            $stmt->close();
            header("Location: calendar.php");
            exit;

        } catch (Exception $e) {
            $addError = "Error: " . $e->getMessage();
            error_log("Calendar error: " . $e->getMessage());
        }
    }
}

// Fetch only the user's events
$events = [];
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM events WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$eventList = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "id" => $row["id"],
            "title" => $row["title"] . " (" . $row["type"] . ")",
            "start" => $row["start_date"] . "T" . $row["time"],
            "end" => $row["end_date"] . "T" . $row["time"]
        ];
        $eventList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <style>
    /* Dark mode styles */
    .darkmode .form .form-label,
    .darkmode .sidebar .nav-link,
    .darkmode .sidebar .nav-link.active {
        background: #232323 !important;
        color: #fff !important;
        border-color: white !important;
    }

    .darkmode .btn {
        filter: grayscale(1) invert(1) brightness(0.8);
    }

    /* Calendar styles */
    #calendar {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .fc-header-toolbar {
        padding: 15px;
        margin-bottom: 0 !important;
    }

    /* Header colors */
    .fc-col-header-cell {
        background-color: #070bff !important;
        padding: 10px 0 !important;
    }

    .fc-col-header-cell-cushion {
        color: white !important;
        font-weight: 600 !important;
    }

    /* Today highlight */
    .fc-day-today {
        background-color: rgba(74, 144, 226, 0.1) !important;
    }

    .fc-day-today .fc-daygrid-day-number {
        color: #000 !important;
        background-color: #fcff36 !important;
        border-radius: 50% !important;
        width: 30px !important;
        height: 30px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 5px !important;
    }

    /* Dark mode calendar */
    .darkmode .fc {
        background-color: #181818;
    }

    .darkmode .fc-toolbar-title,
    .darkmode .fc th {
        color: #fff;
    }
    </style>
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
        <a class="nav-link" href="setting.php">Settings</a>
        <a class="nav-link" style="color: red;" href="../logout.php">Logout</a>
    </nav>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>My Calendar</h1>
            <p class="lead">View and manage your events</p>
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

                    <h5>My Events</h5>
                    <ul class="list-group">
                        <?php if (count($eventList)): ?>
                            <?php foreach ($eventList as $event): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($event['title']) ?></strong><br>
                                        <?= htmlspecialchars($event['start_date']) ?> <?= htmlspecialchars($event['time']) ?>
                                    </div>
                                    <a href="calendar.php?delete_event=<?= $event['id'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Delete this event?');">
                                        Delete
                                    </a>
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
    <script src="../assets/setting.js"></script>
</body>
</html>