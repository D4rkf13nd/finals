<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Data setup
$totalPopulation = 0;
$ageGroups = ['0-17' => 0, '18-59' => 0, '60+' => 0];
$gender = ['Male' => 0, 'Female' => 0];
$barangay = [];
$birthdaysToday = [];
$birthdaysUpcoming = [];

$sql = "SELECT * FROM pop_data";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$data = [];
foreach ($result as $row) {
    $totalPopulation++;
    // Age groups
    if ($row['age'] <= 17) $ageGroups['0-17']++;
    elseif ($row['age'] <= 59) $ageGroups['18-59']++;
    else $ageGroups['60+']++;
    // Gender
    $genderValue = strtolower(trim($row['gender'] ?? $row['sex'] ?? ''));
    if ($genderValue === 'male' || $genderValue === 'm') $gender['Male']++;
    elseif ($genderValue === 'female' || $genderValue === 'f') $gender['Female']++;
    // Barangay
    $barangayName = $row['address'];
    if (!isset($barangay[$barangayName])) $barangay[$barangayName] = 0;
    $barangay[$barangayName]++;
    // Birthdays
    if (date('m-d', strtotime($row['birthday'])) == date('m-d')) $birthdaysToday[] = $row;
    elseif (strtotime($row['birthday']) > strtotime(date('Y-m-d')) && strtotime($row['birthday']) <= strtotime('+7 days')) $birthdaysUpcoming[] = $row;
    $data[] = $row;
}

// Growth rate calculation
$lastYearPopulation = 0;
$currentYearPopulation = $totalPopulation;
foreach ($data as $row) {
    if (date('Y', strtotime($row['created_at'] ?? $row['birthday'])) == date('Y', strtotime('-1 year'))) {
        $lastYearPopulation++;
    }
}
if ($lastYearPopulation > 0) {
    $growthRate = round((($currentYearPopulation - $lastYearPopulation) / $lastYearPopulation) * 100, 2) . '%';
} else {
    $growthRate = 'N/A';
}
$registeredThisMonth = 0; // Set this if you want to show new registrations this month
$username = "Admin"; // Set your username variable if needed

if (isset($_POST['delete_all'])) {
    // Fix: Use correct MySQL credentials and table name
    $conn = new mysqli($servername, "root", $password, $dbname);
    $conn->query("DELETE FROM pop_data");
    $conn->close();
    // Optionally, redirect to avoid resubmission
    header("Location: main.php?section=dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Dos</title>
    <link rel="stylesheet" href="./frontend/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./frontend/dashboard.js"></script>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-none d-md-block bg-light sidebar py-4">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a class="nav-link active" href="?section=dashboard"><span class="bi bi-house"></span> Dashboard</a></li>
                    <li class="nav-item mb-2"><a class="nav-link" href="?section=calendar"><span class="bi bi-calendar"></span> Calendar</a></li>
                    <li class="nav-item mb-2"><a class="nav-link" href="?section=setting"><span class="bi bi-gear"></span> Setting</a></li>
                </ul>
            </div>
        </nav>
        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto px-4">
            <?php $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard'; ?>
            <?php if ($section === 'dashboard'): ?>
                <div class="dashboard-container">
                    <!-- Welcome Panel -->
                    <div class="welcome-panel mb-4">
                        <span class="emoji">🧑‍💻</span>
                        <div>
                            <h2 class="mb-1">Hello, <?= htmlspecialchars($username) ?>!</h2>
                            <p class="mb-0">Explore demographic data and activity in your community.</p>
                        </div>
                    </div>
                    <!-- Dashboard Cards -->
                    <div class="dashboard-cards mb-4">
                        <div class="card-population">
                            <div class="card-title">Total Population</div>
                            <div class="card-value"><?= $totalPopulation ?></div>
                            <div class="card-sub">This month: <?= $registeredThisMonth ?? 0 ?></div>
                        </div>
                        <div class="card-male">
                            <div class="card-title">Male</div>
                            <div class="card-value"><?= $gender['Male'] ?? 0 ?></div>
                        </div>
                        <div class="card-female card-component">
                            <div class="card-title">Female</div>
                            <div class="card-value"><?= $gender['Female'] ?? 0 ?></div>
                        </div>
                        <div class="card-component">
                            <div class="card-title">Growth Rate</div>
                            <div class="card-value"><?= $growthRate ?? '0%' ?></div>
                        </div>
                    </div>
                    <!-- Charts Row -->
                    <div class="dashboard-charts tableau-layout mb-4">
                        <div class="chart-card">
                            <div class="graph-title">Population Trend</div>
                            <div class="main-graph-area population-trend">
                                <canvas id="growthChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="graph-title">Barangay Breakdown</div>
                            <div class="main-graph-area">
                                <canvas id="barangayChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="graph-title">Age Groups</div>
                            <div class="main-graph-area">
                                <canvas id="ageChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="graph-title">Gender Ratio</div>
                            <div class="main-graph-area">
                                <canvas id="genderChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Birthdays -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card-component">
                                <div class="card-title">Today's Birthdays</div>
                                <ul class="mb-0">
                                    <?php foreach ($birthdaysToday as $b): ?>
                                        <li><?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['birthday']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-component">
                                <div class="card-title">Upcoming Birthdays</div>
                                <ul class="mb-0">
                                    <?php foreach ($birthdaysUpcoming as $b): ?>
                                        <li><?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['birthday']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Population Table -->
                    <div class="main-graph-area mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                            <div class="graph-title mb-2 mb-md-0">Population Table</div>
                            <div class="d-flex align-items-center gap-2">
                                <!-- Search Bar -->
                                <input type="text" class="form-control" id="populationSearch" placeholder="Search..." style="max-width:180px;">
                                <!-- Import Button -->
                                <form action="./backend/import.php" method="post" enctype="multipart/form-data" class="d-inline">
                                    <input type="file" name="importFile" accept=".csv" style="display:none;" id="importFileInput" onchange="this.form.submit()">
                                    <button type="button" class="btn btn-success me-2" onclick="document.getElementById('importFileInput').click();">
                                        <span class="bi bi-upload"></span> Import CSV
                                    </button>
                                </form>
                                <a class="btn btn-primary" href="./backend/create.php" role="button">Add Resident</a>
                            </div>
                        </div>
                        <div style="max-height: 350px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-striped" id="populationTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Address</th>
                                        <th>Contact</th>
                                        <th>Birthday</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['age']) ?></td>
                                        <td><?= htmlspecialchars($row['gender'] ?? $row['sex'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['address']) ?></td>
                                        <td><?= htmlspecialchars($row['contact']) ?></td>
                                        <td><?= htmlspecialchars($row['birthday']) ?></td>
                                        <td>
                                            <a class='btn btn-secondary btn-sm' href='./backend/edit.php?id=<?= $row['id'] ?>'>Edit</a>
                                            <button class='btn btn-danger btn-sm' onclick='showDeleteModal(<?= $row['id'] ?>, "<?= htmlspecialchars($row['name']) ?>")'>Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <script>
                window.dashboardData = {
                    ageGroups: [<?= $ageGroups['0-17'] ?>, <?= $ageGroups['18-59'] ?>, <?= $ageGroups['60+'] ?>],
                    barangayLabels: <?= json_encode(array_keys($barangay)) ?>,
                    barangayData: <?= json_encode(array_values($barangay)) ?>,
                    genderData: [<?= $gender['Male'] ?? 0 ?>, <?= $gender['Female'] ?? 0 ?>],
                    totalPopulation: <?= $totalPopulation ?>,
                    growthData: [100, 120, 140, 160, <?= $totalPopulation ?>] // Example, replace with real data if available
                };
                document.getElementById('populationSearch').addEventListener('input', function() {
                    const search = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#populationTable tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(search) ? '' : 'none';
                    });
                });
                </script>
            <?php elseif ($section === 'calendar'): ?>
                <h2>Calendar</h2>
                <p>Calendar features go here.</p>
            <?php elseif ($section === 'setting'): ?>
                <div class="main-graph-area mb-4" id="settings-section">
                    <div class="graph-title mb-3">Settings</div>
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <label class="form-check-label me-2" for="darkModeSwitch">
                                <span class="bi bi-moon"></span> Dark Mode
                            </label>
                            <input type="checkbox" id="darkModeSwitch" class="form-check-input" onchange="toggleDarkMode()">
                        </div>
                        <div>
                            <button class="btn btn-danger" onclick="showDeleteAllModal()">
                                <span class="bi bi-trash"></span> Delete All Data
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Delete All Confirmation Modal -->
                <div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="deleteAllModalLabel">Confirm Delete All</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        Are you sure you want to delete <b>ALL</b> population data? This action cannot be undone.
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" onclick="showFinalDeleteConfirmation()">Delete All</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Final Delete Confirmation Modal -->
                <div class="modal fade" id="finalDeleteModal" tabindex="-1" aria-labelledby="finalDeleteModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title text-danger" id="finalDeleteModalLabel">⚠️ Final Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="alert alert-danger">
                          <strong>This will permanently delete ALL population data!</strong>
                        </div>
                        <p>To confirm, please type <strong>DELETE ALL DATA</strong> in the text box below:</p>
                        <input type="text" id="deleteConfirmationInput" class="form-control" placeholder="Type DELETE ALL DATA here">
                        <small class="text-muted">This action cannot be undone.</small>
                      </div>
                      <div class="modal-footer">
                        <form method="post" id="finalDeleteForm">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" name="delete_all" class="btn btn-danger" id="finalDeleteBtn" disabled>I understand, delete all data</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <script>
                // Dark Mode Toggle
                function toggleDarkMode() {
                    document.body.classList.toggle('dark-mode');
                    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
                }
                window.addEventListener('DOMContentLoaded', function() {
                    if (localStorage.getItem('darkMode') === 'true') {
                        document.body.classList.add('dark-mode');
                        document.getElementById('darkModeSwitch').checked = true;
                    }
                });
                // Show Delete All Modal
                function showDeleteAllModal() {
                    var modal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
                    modal.show();
                }

                // Show Final Delete Confirmation Modal
                function showFinalDeleteConfirmation() {
                    // Hide first modal
                    var firstModal = bootstrap.Modal.getInstance(document.getElementById('deleteAllModal'));
                    firstModal.hide();
                    
                    // Wait for first modal to fully close before showing second modal
                    setTimeout(function() {
                        // Reset input and button state
                        const input = document.getElementById('deleteConfirmationInput');
                        const button = document.getElementById('finalDeleteBtn');
                        
                        input.value = '';
                        button.disabled = true;
                        button.classList.remove('btn-danger');
                        button.classList.add('btn-secondary');
                        
                        // Add input event listener for this modal instance
                        input.addEventListener('input', function() {
                            if (this.value.trim() === 'DELETE ALL DATA') {
                                button.disabled = false;
                                button.classList.remove('btn-secondary');
                                button.classList.add('btn-danger');
                            } else {
                                button.disabled = true;
                                button.classList.remove('btn-danger');
                                button.classList.add('btn-secondary');
                            }
                        });
                        
                        // Show final confirmation modal
                        var finalModal = new bootstrap.Modal(document.getElementById('finalDeleteModal'));
                        finalModal.show();
                    }, 300); // 300ms delay to ensure first modal is fully closed
                }

                </script>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Delete Single Resident Modal (Global) -->
<div class="modal fade" id="deleteResidentModal" tabindex="-1" aria-labelledby="deleteResidentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteResidentModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete <strong id="residentName"></strong>? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmDeleteBtn" class="btn btn-danger" href="#">Delete</a>
      </div>
    </div>
  </div>
</div>

<script>
// Global Delete Resident Modal Function
function showDeleteModal(id, name) {
    document.getElementById('residentName').textContent = name;
    document.getElementById('confirmDeleteBtn').href = './backend/delete.php?id=' + id;
    var modal = new bootstrap.Modal(document.getElementById('deleteResidentModal'));
    modal.show();
}
</script>

</body>
</html>