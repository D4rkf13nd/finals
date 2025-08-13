<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// --- Database connection ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard_db";
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Barangay List ---
$barangays = [
    "BF Homes", "Don Bosco", "Marcelo Green", "Merville",
    "Moonwalk", "San Antonio", "San Martin de Porres", "Sun Valley"
];

// --- Age Group Counters ---
$ageGroups = [
    "18-30" => 0,
    "31-50" => 0,
    "51-60" => 0
];
$totalPopulation = 0;

// --- Barangay Chart Data ---
$barangayCounts = [];
foreach ($barangays as $b) $barangayCounts[$b] = 0;

// --- Search ---
$search = $_GET['search'] ?? '';
$searchSql = "";
if ($search) {
    $search = $conn->real_escape_string($search);
    $searchSql = "WHERE name LIKE '%$search%' OR address LIKE '%$search%' OR barangay LIKE '%$search%'";
}

// --- Resident Query (with search) ---
$sql = "SELECT * FROM pop_data $searchSql";
$result = $conn->query($sql);

// --- Generation Counting ---
$generationCounts = [
    "Gen_Z" => 0,
    "Millennials" => 0,
    "Gen_X" => 0,
    "Boomers" => 0,
    "Silent" => 0
];

$generationRanges = [
    "Gen_Z" => ["start" => 1997, "end" => 2012],
    "Millennials" => ["start" => 1981, "end" => 1996],
    "Gen_X" => ["start" => 1965, "end" => 1980],
    "Boomers" => ["start" => 1946, "end" => 1964],
    "Silent" => ["start" => 1928, "end" => 1945]
];

// Get generation counts from database
$generationSql = "SELECT 
    CASE 
        WHEN YEAR(birthday) BETWEEN 1997 AND 2012 THEN 'Gen_Z'
        WHEN YEAR(birthday) BETWEEN 1981 AND 1996 THEN 'Millennials'
        WHEN YEAR(birthday) BETWEEN 1965 AND 1980 THEN 'Gen_X'
        WHEN YEAR(birthday) BETWEEN 1946 AND 1964 THEN 'Boomers'
        WHEN YEAR(birthday) BETWEEN 1928 AND 1945 THEN 'Silent'
    END as generation,
    COUNT(*) as count
FROM pop_data
WHERE birthday IS NOT NULL
GROUP BY generation";

$generationResult = $conn->query($generationSql);
while ($row = $generationResult->fetch_assoc()) {
    if ($row['generation']) {
        $generationCounts[$row['generation']] = $row['count'];
    }
}

// --- Age Group and Barangay Counting ---
$residents = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $age = (int)$row['age'];
        $barangay = $row['barangay'] ?? '';
        
        // Age group counting
        if ($age >= 18 && $age <= 30) $ageGroups["18-30"]++;
        elseif ($age >= 31 && $age <= 50) $ageGroups["31-50"]++;
        elseif ($age >= 51 && $age <= 60) $ageGroups["51-60"]++;
        
        // Barangay counting
        if (in_array($barangay, $barangays)) $barangayCounts[$barangay]++;
        
        $totalPopulation++;
        $residents[] = $row;
    }
}

// Count male and female
$genderCounts = ["Male" => 0, "Female" => 0, "Other" => 0];
foreach ($residents as $row) {
    $sex = strtolower($row['sex']);
    if ($sex == "male") $genderCounts["Male"]++;
    elseif ($sex == "female") $genderCounts["Female"]++;
    else $genderCounts["Other"]++;
}
$genderDataPoints = [];
foreach ($genderCounts as $label => $count) {
    if ($count > 0) {
        $genderDataPoints[] = ["label" => $label, "y" => $count];
    }
}

// --- Chart Data for JS ---
$ageDataPoints = [];
foreach ($ageGroups as $label => $count) {
    $ageDataPoints[] = ["label" => $label, "y" => $count];
}
$barangayDataPoints = [];
foreach ($barangayCounts as $label => $count) {
    $barangayDataPoints[] = ["label" => $label, "y" => $count];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Population Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS & Custom CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
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
        <a class="nav-link active" href="main.php">Dashboard</a>
        <a class="nav-link" href="calendar.php">Calendar</a>
        <a class="nav-link" href="settings.php">Setting</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <p class="lead">Sustainable growth starts with balance</p>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="counter-total">
                    <h5>Total Population</h5>
                    <h2><?= $totalPopulation ?></h2>
                    <p>Residents</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-18">
                    <h6>Age 18-30</h6>
                    <p>
                        <?php
                            $indicator = $totalPopulation > 0 ? round(($ageGroups["18-30"] / $totalPopulation) * 100, 1) : 0;
                            echo $indicator . "%";
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-31">
                    <h6>Age 31-50</h6>
                    <p>
                        <?php
                            $indicator = $totalPopulation > 0 ? round(($ageGroups["31-50"] / $totalPopulation) * 100, 1) : 0;
                            echo $indicator . "%";
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-51">
                    <h6>Age 51-60</h6>
                    <p>
                        <?php
                            $indicator = $totalPopulation > 0 ? round(($ageGroups["51-60"] / $totalPopulation) * 100, 1) : 0;
                            echo $indicator . "%";
                        ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Population by Generation</h5>
            </div>
            <div class="card-body">
                <div class="generation-container">
                    <div class="generation-item">
                        <div class="generation-z mb-2">Gen Z</div>
                        <div class="generation-value" data-bs-toggle="tooltip" title="Born: 1997-2012">
                            <?= $generationCounts["Gen_Z"] ?>
                        </div>
                    </div>
                    <div class="generation-item">
                        <div class="generation-millennials mb-2">Millennials</div>
                        <div class="generation-value" data-bs-toggle="tooltip" title="Born: 1981-1996">
                            <?= $generationCounts["Millennials"] ?>
                        </div>
                    </div>
                    <div class="generation-item">
                        <div class="generation-x mb-2">Gen X</div>
                        <div class="generation-value" data-bs-toggle="tooltip" title="Born: 1965-1980">
                            <?= $generationCounts["Gen_X"] ?>
                        </div>
                    </div>
                    <div class="generation-item">
                        <div class="generation-baby mb-2">Baby Boomers</div>
                        <div class="generation-value" data-bs-toggle="tooltip" title="Born: 1946-1964">
                            <?= $generationCounts["Boomers"] ?>
                        </div>
                    </div>
                    <div class="generation-item">
                        <div class="generation-silent mb-2">Silent Generation</div>
                        <div class="generation-value" data-bs-toggle="tooltip" title="Born: 1928-1945">
                            <?= $generationCounts["Silent"] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        <div class="chart-toggle text-center">
            <button class="btn btn-outline-primary me-2" id="barBtn">Bar Chart</button>
            <button class="btn btn-outline-success me-2" id="lineBtn">Line Chart</button>
            <button class="btn btn-outline-secondary" id="pieBtn">Pie Chart</button>
        </div>
        <div class="row">
            <div class="col-md-7 mb-3">
                <div class="barangay-container">
                    <canvas id="barangayChart" style="height: 350px; width: 100%;"></canvas>
                </div>
            </div>
            <div class="col-md-5 mb-3">
                <div class="chart-container">
                    <canvas id="genderChart" style="height: 350px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <hr>
        <form class="d-flex search-bar" method="get" action="">
            <input class="form-control me-2" type="search" name="search" placeholder="Search resident..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-secondary ms-2" href="main.php">Reset</a>
            <a class="btn btn-success ms-auto" href="backend/create.php">Add </a>
            <a class="btn btn-warning ms-2" href="backend/import.php">Import </a>
            <button type="button" class="btn btn-info ms-2" id="exportBtn">Export</button>
            
            <!-- Moved sort dropdown here -->
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
                <?php if (!empty($residents)): ?>
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
                            <a class='btn btn-primary btn-sm' href='backend/edit.php?id=<?= $row['id'] ?>'>Edit</a>
                            <a class='btn btn-danger btn-sm' href='backend/delete.php?id=<?= $row['id'] ?>' onclick="return confirm('Are you sure you want to delete this resident?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No residents found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Pass PHP data to JS for external JS file
        window.barangayDataPoints = <?= json_encode($barangayDataPoints, JSON_NUMERIC_CHECK); ?>;
        window.genderDataPoints = <?= json_encode($genderDataPoints, JSON_NUMERIC_CHECK); ?>;
    </script>
    <script src="assets/dashboard.js"></script>
    <script src="assets/setting.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
</body>


</html>
