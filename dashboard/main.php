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

// Example: Get population breakdowns for charts
$totalPopulation = 0;
$ageGroups = ['0-17' => 0, '18-59' => 0, '60+' => 0];
$gender = ['Male' => 0, 'Female' => 0];
$barangay = [];
$birthdaysToday = [];
$birthdaysUpcoming = [];

$sql = "SELECT * FROM client";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $totalPopulation++;
    // Age groups
    if ($row['age'] <= 17) $ageGroups['0-17']++;
    elseif ($row['age'] <= 59) $ageGroups['18-59']++;
    else $ageGroups['60+']++;
    // Gender (case-insensitive, accepts M/F/male/female)
    $sex = strtolower(trim($row['sex']));
    if ($sex === 'male' || $sex === 'm') {
        $gender['Male']++;
    } elseif ($sex === 'female' || $sex === 'f') {
        $gender['Female']++;
    }
    // Barangay
    $barangayName = $row['address'];
    if (!isset($barangay[$barangayName])) $barangay[$barangayName] = 0;
    $barangay[$barangayName]++;
    // Birthdays
    if (date('m-d', strtotime($row['birthday'])) == date('m-d')) $birthdaysToday[] = $row;
    elseif (strtotime($row['birthday']) > strtotime(date('Y-m-d')) && strtotime($row['birthday']) <= strtotime('+7 days')) $birthdaysUpcoming[] = $row;
    $data[] = $row;
}

// Example: Calculate growth rate based on last year and current year
$lastYearPopulation = 0;
$currentYearPopulation = $totalPopulation;

// Calculate last year's population (replace with your actual query if available)
foreach ($data as $row) {
    if (date('Y', strtotime($row['created_at'] ?? $row['birthday'])) == date('Y', strtotime('-1 year'))) {
        $lastYearPopulation++;
    }
}

// Avoid division by zero
if ($lastYearPopulation > 0) {
    $growthRate = round((($currentYearPopulation - $lastYearPopulation) / $lastYearPopulation) * 100, 2) . '%';
} else {
    $growthRate = 'N/A';
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
                <!-- Welcome Panel -->
                <div class="welcome-panel mb-4">
                    <span class="emoji">🧑‍💻</span>
                    <div>
                        <h2 class="mb-1">Hello, <?= htmlspecialchars($username ?? 'Admin') ?>!</h2>
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
                    <div class="card-female">
                        <div class="card-title">Female</div>
                        <div class="card-value"><?= $gender['Female'] ?? 0 ?></div>
                    </div>
                    <div class="card-component">
                        <div class="card-title">Growth Rate</div>
                        <div class="card-value"><?= $growthRate ?? '0%' ?></div>
                    </div>
                </div>
                <!-- Charts Row -->
                <div class="dashboard-container">
                    <div class="row mb-4">
                        <!-- Population Trend -->
                        <div class="main-graph-area mb-4">
                            <div class="graph-trend">Population Trend</div>
                            <canvas id="growthChart"></canvas>
                        </div>
                        <!-- Barangay Breakdown -->
                        <div class="barangay-breakdown mb-4">
                            <div class="graph-breakdown">Barangay Breakdown</div>
                            <canvas id="barangayChart"></canvas>
                        </div>
                        <!-- Age Groups -->
                        <div class="main-graph-area mb-4">
                            <div class="graph-title">Age Groups</div>
                            <canvas id="ageChart"></canvas>
                        </div>
                        <!-- Gender Ratio -->
                        <div class="main-graph-area mb-4">
                            <div class="graph-title">Gender Ratio</div>
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
                                <?php foreach ($birthdaysToday as $b) echo "<li>{$b['name']} ({$b['birthday']})</li>"; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-component">
                            <div class="card-title">Upcoming Birthdays</div>
                            <ul class="mb-0">
                                <?php foreach ($birthdaysUpcoming as $b) echo "<li>{$b['name']} ({$b['birthday']})</li>"; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- Population Table -->
                <div class="main-graph-area mb-4">
                    <div class="graph-title">Population Table</div>
                    <a class="btn btn-primary mb-3" href="./backend/create.php" role="button">Add Resident</a>
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
                                <td><?= htmlspecialchars($row['sex']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['contact']) ?></td>
                                <td><?= htmlspecialchars($row['birthday']) ?></td>
                                <td>
                                    <a class='btn btn-secondary btn-sm' href='./backend/edit.php?id=<?= $row['id'] ?>'>Edit</a>
                                    <a class='btn btn-danger btn-sm' href='./backend/delete.php?id=<?= $row['id'] ?>'>Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                </script>
            <?php elseif ($section === 'calendar'): ?>
                <h2>Calendar</h2>
                <p>Calendar features go here.</p>
            <?php elseif ($section === 'setting'): ?>
                <h2>Setting</h2>
                <p>Settings features go here.</p>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>