<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: login.php");
    exit;
}
require_once "backend/db.php";

$error = $success = "";

// CREATE Resident
if (isset($_POST['add_resident'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $barangay = $_POST['barangay'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $birthday = $_POST['birthday'];
    if ($name && $age && $sex && $barangay && $address && $contact && $birthday) {
        $stmt = $conn->prepare("INSERT INTO pop_data (name, age, sex, barangay, address, contact, birthday) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $name, $age, $sex, $barangay, $address, $contact, $birthday);
        if ($stmt->execute()) $success = "Resident added successfully.";
        else $error = "Error adding resident: " . $stmt->error;
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}

// DELETE Resident
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM pop_data WHERE id=$id");
    header("Location: user.php");
    exit;
}

// EDIT Resident (fetch for modal)
$editResident = null;
if (isset($_GET['edit_id'])) {
    $id = intval($_GET['edit_id']);
    $res = $conn->query("SELECT * FROM pop_data WHERE id=$id");
    $editResident = $res->fetch_assoc();
}

// UPDATE Resident
if (isset($_POST['update_resident'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $barangay = $_POST['barangay'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $birthday = $_POST['birthday'];
    if ($name && $age && $sex && $barangay && $address && $contact && $birthday) {
        $stmt = $conn->prepare("UPDATE pop_data SET name=?, age=?, sex=?, barangay=?, address=?, contact=?, birthday=? WHERE id=?");
        $stmt->bind_param("sisssssi", $name, $age, $sex, $barangay, $address, $contact, $birthday, $id);
        if ($stmt->execute()) $success = "Resident updated!";
        else $error = "Failed to update resident.";
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}

// READ Residents
$residents = [];
$res = $conn->query("SELECT * FROM pop_data");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $residents[] = $row;
    }
}

// Fetch events
$events = [];
$res2 = $conn->query("SELECT * FROM events");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $events[] = $row;
    }
}

// Handle Add Event
if (isset($_POST['add_event'])) {
    $title = $_POST['event_title'];
    $type = $_POST['event_type'];
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    if ($title && $type && $date && $time) {
        $stmt = $conn->prepare("INSERT INTO events (title, type, start_date, end_date, time) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $type, $date, $date, $time);
        $stmt->execute();
        $stmt->close();
        header("Location: user.php");
        exit;
    }
}

// DELETE Event
if (isset($_GET['delete_event'])) {
    $id = intval($_GET['delete_event']);
    $conn->query("DELETE FROM events WHERE id=$id");
    header("Location: user.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
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
        <a class="nav-link active" href="user.php">Home</a>
        <a class="nav-link" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]) ?></h1>
            <p class="lead">Resident Data</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Add Resident Button and Modal Trigger -->
        <div class="mb-3 d-flex align-items-center">
            <button class="btn btn-success me-3" data-bs-toggle="modal" data-bs-target="#addResidentModal">Add</button>
            <form method="post" action="backend/import.php" enctype="multipart/form-data" class="d-flex align-items-center">
                <input type="file" name="import_file" accept=".csv" class="form-control" required>
                <button class="btn btn-primary ms-2" name="import">Import</button>
            </form>
        </div>

        <!-- Add Resident Modal -->
        <div class="modal fade" id="addResidentModal" tabindex="-1" aria-labelledby="addResidentModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addResidentModalLabel">Add Resident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Age</label>
                    <input name="age" type="number" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Sex</label>
                    <select name="sex" class="form-select" required>
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Barangay</label>
                    <select name="barangay" class="form-select" required>
                        <option value="">Select</option>
                        <option>BF Homes</option>
                        <option>Don Bosco</option>
                        <option>Marcelo Green</option>
                        <option>Merville</option>
                        <option>Moonwalk</option>
                        <option>San Antonio</option>
                        <option>San Martin de Porres</option>
                        <option>Sun Valley</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Address</label>
                    <input name="address" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Contact</label>
                    <input name="contact" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Birthday</label>
                    <input name="birthday" type="date" class="form-control" required>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-success" name="add_resident">Add</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Resident Modal -->
        <?php if ($editResident): ?>
        <div class="modal show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Resident</h5>
                            <a href="user.php" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $editResident['id'] ?>">
                            <input name="name" class="form-control mb-2" value="<?= htmlspecialchars($editResident['name']) ?>" required>
                            <input name="age" type="number" class="form-control mb-2" value="<?= htmlspecialchars($editResident['age']) ?>" required>
                            <select name="sex" class="form-select mb-2" required>
                                <option value="male" <?= $editResident['sex']=='male'?'selected':'' ?>>Male</option>
                                <option value="female" <?= $editResident['sex']=='female'?'selected':'' ?>>Female</option>
                                <option value="other" <?= $editResident['sex']=='other'?'selected':'' ?>>Other</option>
                            </select>
                            <select name="barangay" class="form-select mb-2" required>
                                <option>BF Homes</option>
                                <option>Don Bosco</option>
                                <option>Marcelo Green</option>
                                <option>Merville</option>
                                <option>Moonwalk</option>
                                <option>San Antonio</option>
                                <option>San Martin de Porres</option>
                                <option>Sun Valley</option>
                            </select>
                            <input name="address" class="form-control mb-2" value="<?= htmlspecialchars($editResident['address']) ?>" required>
                            <input name="contact" class="form-control mb-2" value="<?= htmlspecialchars($editResident['contact']) ?>" required>
                            <input name="birthday" type="date" class="form-control mb-2" value="<?= htmlspecialchars($editResident['birthday']) ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" name="update_resident">Update</button>
                            <a href="user.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Population Counter Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="counter-card counter-total">
                    <h5>Total Population</h5>
                    <h2 id="populationCounter"><?= count($residents) ?></h2>
                    <p>Residents</p>
                </div>
            </div>
        </div>

        <!-- Search Bar for Residents -->
        <div class="mb-3 d-flex" style="max-width:400px;">
            <input type="text" id="residentSearch" class="form-control" placeholder="Search residents...">
            <button class="btn btn-primary ms-2" id="residentSearchBtn">Search</button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Barangay</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Birthday</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($residents as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['age']) ?></td>
                        <td><?= htmlspecialchars($row['sex']) ?></td>
                        <td><?= htmlspecialchars($row['barangay'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= htmlspecialchars($row['contact']) ?></td>
                        <td><?= htmlspecialchars($row['birthday']) ?></td>
                        <td>
                            <a href="user.php?edit_id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="user.php?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this resident?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Event & Calendar Section -->
        <div class="row mt-4">
            <!-- Events List -->
            <div class="col-md-6">
                <div class="event-container">
                    <h5>Events</h5>
                    <ul class="list-group">
                        <?php if (count($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($event['title']) ?></strong>
                                        <br><?= htmlspecialchars($event['start_date']) ?> <?= htmlspecialchars($event['time']) ?>
                                    </div>
                                    <a href="user.php?delete_event=<?= $event['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this event?');">Delete</a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No events found.</li>
                        <?php endif; ?>
                    </ul>
                    <!-- Add Event Button -->
                    <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>
                </div>
            </div>
            <!-- Calendar -->
            <div class="col-md-6">
                <div class="event-container">
                    <h5>Calendar</h5>
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- Add Event Modal -->
        <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">Add Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Event Title</label>
                    <input name="event_title" class="form-control" required>
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
                    <input name="event_date" type="date" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Time</label>
                    <input name="event_time" type="time" class="form-control" required>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-success" name="add_event">Add Event</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 400,
                events: <?= json_encode(array_map(function($e) {
                    return [
                        'title' => $e['title'],
                        'start' => $e['start_date'] . ($e['time'] ? 'T' . $e['time'] : ''),
                    ];
                }, $events)) ?>
            });
            calendar.render();
        }
    });
    </script>
</body>
</html>