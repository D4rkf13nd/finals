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

// First, add this after your database connection to check for errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
    $end = $start;

    if (!$title || !$type || !$date || !$time) {
        $addError = "All fields are required.";
    } else {
        try {
            // First check if the events table exists
            $checkTable = $conn->query("SHOW TABLES LIKE 'events'");
            if ($checkTable->num_rows == 0) {
                // Create events table if it doesn't exist
                $createTable = "CREATE TABLE IF NOT EXISTS events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'Other',
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    time TIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($createTable);
            }

            // After creating the table, check if the type column exists
            $checkTypeColumn = $conn->query("SHOW COLUMNS FROM events LIKE 'type'");
            if ($checkTypeColumn->num_rows == 0) {
                // Add type column if it doesn't exist
                $conn->query("ALTER TABLE events ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'Other' AFTER title");
            }

            // Prepare statement with error checking
            $stmt = $conn->prepare("INSERT INTO events (title, type, start_date, end_date, time) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            if (!$stmt->bind_param("sssss", $title, $type, $date, $date, $time)) {
                throw new Exception("Binding parameters failed: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $addSuccess = "Event added successfully!";
            $stmt->close();
            
            // Redirect after successful addition
            header("Location: calendar.php");
            exit;

        } catch (Exception $e) {
            $addError = "Error: " . $e->getMessage();
            error_log("Calendar error: " . $e->getMessage());
        }
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

// First add this PHP code after your existing database queries
$upcomingBirthdaysSql = "SELECT name, birthday, barangay 
    FROM pop_data 
    WHERE DATE_FORMAT(birthday, '%m-%d') 
    BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
    AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
    ORDER BY DATE_FORMAT(birthday, '%m-%d')
    LIMIT 5";

$birthdayResult = $conn->query($upcomingBirthdaysSql);

// Add this PHP code near your other queries
$monthlyBirthdaysQuery = "SELECT 
    MONTH(birthday) as birth_month,
    COUNT(*) as count
    FROM pop_data 
    GROUP BY MONTH(birthday)
    ORDER BY birth_month";

$monthlyResult = $conn->query($monthlyBirthdaysQuery);
$monthlyData = array_fill(1, 12, 0); // Initialize all months with 0

if ($monthlyResult) {
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlyData[$row['birth_month']] = (int)$row['count'];
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

    /* Birthday Container Styles */
.birthday-container {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 400px; /* Increased to match chart container */
}

.birthday-list {
    max-height: 320px; /* Increased from 220px to accommodate more items */
    overflow-y: auto;
}

.birthday-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.4rem;
    background: #f8fafc;
    transition: all 0.3s ease;
}

.birthday-item:hover {
    transform: translateX(5px);
    background: #f1f5f9;
}

.birthday-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0d0058ff 0%, #375cffff 100%);
    border-radius: 10px;
    color: white;
    margin-right: 1rem;
}

.birth-month {
    font-size: 0.7rem;
    text-transform: uppercase;
    opacity: 0.9;
}

.birth-day {
    font-size: 1.2rem;
    font-weight: bold;
}

.birthday-info {
    flex: 1;
}

.birthday-name {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.birthday-details {
    font-size: 0.875rem;
}

/* Dark mode support */
.darkmode .birthday-container {
    background: #000000ff;
    color: white;
}

.darkmode .birthday-item {
    background: #374151;
}

.darkmode .birthday-item:hover {
    background: #4b5563;
}

/* Add to your existing style section */
.birthday-chart-container {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 400px; /* Increased from 300px */
}

.darkmode .birthday-chart-container {
    background: #1f2937;
    color: white;
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
        <a class="nav-link" href="user_management.php">User Management</a>
        <a class="nav-link" href="settings.php">Setting</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Calendar</h1>
            <p class="lead">View and manage your events.</p>
        </div>
        <div class="row mb-4">
    <div class="col-md-8">
        <div class="birthday-chart-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Birthday</h5>
                <span class="badge bg-info">Monthly</span>
            </div>
            <canvas id="birthdayChart"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="birthday-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Upcoming Birthdays</h5>
                <span class="badge bg-primary">Next 7 days</span>
            </div>
            <div class="birthday-list">
                <?php if ($birthdayResult && $birthdayResult->num_rows > 0): ?>
                    <?php while ($birthday = $birthdayResult->fetch_assoc()):
                        $birthDate = new DateTime($birthday['birthday']);
                        $today = new DateTime();
                        $age = $today->diff($birthDate)->y;
                        $nextBirthday = new DateTime($birthday['birthday']);
                        $nextBirthday->setDate($today->format('Y'), $birthDate->format('m'), $birthDate->format('d'));
                        if ($nextBirthday < $today) {
                            $nextBirthday->modify('+1 year');
                        }
                        $daysUntil = $today->diff($nextBirthday)->days;
                    ?>
                        <div class="birthday-item">
                            <div class="birthday-date">
                                <span class="birth-month"><?= $birthDate->format('M') ?></span>
                                <span class="birth-day"><?= $birthDate->format('d') ?></span>
                            </div>
                            <div class="birthday-info">
                                <div class="birthday-name"><?= htmlspecialchars($birthday['name']) ?></div>
                                <div class="birthday-details">
                                    <span class="text-primary">Turning <?= $age + 1 ?></span>
                                    <span class="text-muted mx-2">•</span>
                                    <span class="text-success"><?= $daysUntil ?> days away</span>
                                    <span class="badge bg-secondary ms-2"><?= htmlspecialchars($birthday['barangay']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-birthday-cake mb-2" style="font-size: 1.5rem;"></i>
                        <p class="mb-0">No upcoming birthdays</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const monthlyData = <?= json_encode(array_values($monthlyData)) ?>;
    
    const ctx = document.getElementById('birthdayChart').getContext('2d');
    new Chart(ctx, {
        type: 'line', // Changed from 'bar' to 'line'
        data: {
            labels: monthNames,
            datasets: [{
                label: 'Birthdays',
                data: monthlyData,
                fill: true,
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderColor: '#4f46e5',
                borderWidth: 2,
                pointBackgroundColor: [
                    '#f30000ff', '#2ffff1ff', '#00647aff', '#5fd19cff',
                    '#fac800ff', '#b65b5bff', '#b8008dff', '#180555ff',
                    '#d85300ff', '#2044a7ff', '#059b2bff', '#F6C667'
                ],
                pointBorderColor: '#ffffff',
                pointRadius: 6,
                pointHoverRadius: 8,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 10,
                    bottom: 10
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            return monthNames[tooltipItems[0].dataIndex];
                        },
                        label: function(context) {
                            return context.raw + ' birthdays';
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        display: true,
                        drawBorder: true,
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
    <script src="assets/setting.js"></script>
</body>
</html>