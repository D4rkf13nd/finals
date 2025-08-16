<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit();
}

// Add this code to handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

require_once "backend/db.php";

$error = $success = "";

// Check import status
if (isset($_SESSION['import_status'])) {
    if ($_SESSION['import_status']['success']) {
        $success = $_SESSION['import_status']['message'];
    } else {
        $error = $_SESSION['import_status']['message'];
    }
    unset($_SESSION['import_status']);
}

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

// Handle search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Modify the residents query to include search and sort
$sql = "SELECT * FROM pop_data WHERE 1=1";
if ($search) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (name LIKE '%$search%' 
              OR age LIKE '%$search%' 
              OR sex LIKE '%$search%' 
              OR barangay LIKE '%$search%' 
              OR address LIKE '%$search%')";
}

// Add sorting
$sql .= " ORDER BY $sort_column $sort_order";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
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

// Calculate generations based on birth year
$generationCounts = [
    "Gen_Z" => 0,
    "Millennials" => 0,
    "Gen_X" => 0,
    "Boomers" => 0,
    "Silent" => 0
];

$currentYear = date('Y');

foreach ($residents as $resident) {
    $birthYear = date('Y', strtotime($resident['birthday']));
    
    if ($birthYear >= 1997 && $birthYear <= 2012) {
        $generationCounts["Gen_Z"]++;
    } 
    elseif ($birthYear >= 1981 && $birthYear <= 1996) {
        $generationCounts["Millennials"]++;
    }
    elseif ($birthYear >= 1965 && $birthYear <= 1980) {
        $generationCounts["Gen_X"]++;
    }
    elseif ($birthYear >= 1946 && $birthYear <= 1964) {
        $generationCounts["Boomers"]++;
    }
    elseif ($birthYear >= 1928 && $birthYear <= 1945) {
        $generationCounts["Silent"]++;
    }
}

// Add tooltips to show year ranges
$generationRanges = [
    "Gen_Z" => "1997-2012",
    "Millennials" => "1981-1996",
    "Gen_X" => "1965-1980",
    "Boomers" => "1946-1964",
    "Silent" => "1928-1945"
];
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

        <!-- Population by Generation Card -->
        <div class="generation-container mb-4">
            <h5 class="mb-3">Population by Generation</h5>
            <div class="row g-3">
                <div class="col">
                    <div class="card generation-item">
                        <div class="card-body text-center">
                            <div class="generation-z">Gen Z</div>
                            <div class="generation-value counter"><?= $generationCounts["Gen_Z"] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card generation-item">
                        <div class="card-body text-center">
                            <div class="generation-millennials">Millennials</div>
                            <div class="generation-value counter"><?= $generationCounts["Millennials"] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card generation-item">
                        <div class="card-body text-center">
                            <div class="generation-x">Gen X</div>
                            <div class="generation-value counter"><?= $generationCounts["Gen_X"] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card generation-item">
                        <div class="card-body text-center">
                            <div class="generation-baby">Baby Boomers</div>
                            <div class="generation-value counter"><?= $generationCounts["Boomers"] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card generation-item">
                        <div class="card-body text-center">
                            <div class="generation-silent">Silent Generation</div>
                            <div class="generation-value counter"><?= $generationCounts["Silent"] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Action Bar -->
        <form class="d-flex search-bar" method="get" action="user.php">
            <input class="form-control me-2" type="search" name="search" placeholder="Search resident..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-secondary ms-2" href="user.php">Reset</a>
            <a class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#addResidentModal">Add</a>
            <button type="button" class="btn btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#importModal">Import</button>
            <button type="button" class="btn btn-info ms-2" id="exportBtn">Export</button>
            
            <!-- Sort dropdown -->
            <div class="dropdown ms-2">
                <button class="btn btn-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                    Sort by
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item sort-option" href="#" data-column="name">A-Z</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-column="name" data-order="desc">Z-A</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item sort-option" href="#" data-column="age">Age (Ascending)</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-column="age" data-order="desc">Age (Descending)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item sort-option" href="#" data-column="barangay">Barangay (A-Z)</a></li>
                </ul>
            </div>
        </form>

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

        <!-- Update the import form section -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="backend/import.php" method="post" enctype="multipart/form-data" id="importForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Choose CSV File</label>
                                <input type="file" name="import_file" class="form-control" accept=".csv" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="import" class="btn btn-primary">Import</button>
                        </div>
                    </form>
                </div>
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

    document.getElementById('importForm').addEventListener('submit', function(e) {
        if (!this.querySelector('input[name="import_file"]').value) {
            e.preventDefault();
            alert('Please select a file to import');
        }
    });

    // Prevent form submission on modal close
    document.querySelector('#importModal .btn-close').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('importForm').reset();
    });

    document.querySelector('#importModal .btn-secondary').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('importForm').reset();
    });
    </script>
</body>
</html>
