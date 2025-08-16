<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Database connection
require_once "backend/db.php";

// Initialize variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Validate sort column
$allowedColumns = ['name', 'age', 'barangay', 'sex', 'birthday'];
if (!in_array($sort, $allowedColumns)) {
    $sort = 'name';
}

// Validate order
$order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

// Get total records
$total_query = "SELECT COUNT(*) as total FROM pop_data";
if ($search) {
    $total_query .= " WHERE name LIKE ? OR address LIKE ? OR barangay LIKE ?";
}

$stmt = $conn->prepare($total_query);
if ($search) {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query for records
$sql = "SELECT * FROM pop_data";
if ($search) {
    $sql .= " WHERE name LIKE ? OR address LIKE ? OR barangay LIKE ?";
}
$sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($search) {
    $searchParam = "%$search%";
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$residents = $result->fetch_all(MYSQLI_ASSOC);

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

// Replace the age group counting section
$ageGroupSql = "SELECT 
    SUM(CASE WHEN age BETWEEN 18 AND 30 THEN 1 ELSE 0 END) as age_18_30,
    SUM(CASE WHEN age BETWEEN 31 AND 50 THEN 1 ELSE 0 END) as age_31_50,
    SUM(CASE WHEN age BETWEEN 51 AND 60 THEN 1 ELSE 0 END) as age_51_60,
    COUNT(*) as total
FROM pop_data 
WHERE age IS NOT NULL";

$ageResult = $conn->query($ageGroupSql);
if ($ageResult) {
    $ageCounts = $ageResult->fetch_assoc();
    $ageGroups = [
        "18-30" => (int)$ageCounts['age_18_30'],
        "31-50" => (int)$ageCounts['age_31_50'],
        "51-60" => (int)$ageCounts['age_51_60']
    ];
    $totalPopulation = (int)$ageCounts['total'];
}

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

// --- Total Population Counting ---
$totalQuery = "SELECT COUNT(*) as total FROM pop_data";
$totalResult = $conn->query($totalQuery);
$totalPopulation = $totalResult->fetch_assoc()['total'];

// --- Pagination ---
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total pages
$total_pages = ceil($total_records / $records_per_page);

// --- Resident Query (with search and pagination) ---
$countSql = "SELECT COUNT(*) as total FROM pop_data " . ($search ? "WHERE name LIKE '%$search%' OR address LIKE '%$search%' OR barangay LIKE '%$search%'" : "");
$totalResult = $conn->query($countSql);
$total_records = $totalResult->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Validate sort column to prevent SQL injection
$allowedColumns = ['name', 'age', 'barangay', 'sex', 'birthday'];
if (!in_array($sort, $allowedColumns)) {
    $sort = 'name';
}

// Validate order
$order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

// Update your main query to include sorting
$sql = "SELECT * FROM pop_data " . 
       ($search ? "WHERE name LIKE '%$search%' OR address LIKE '%$search%' OR barangay LIKE '%$search%' " : "") . 
       "ORDER BY $sort $order " .
       "LIMIT $records_per_page OFFSET $offset";

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

// Get barangay counts from database
$barangayQuery = "SELECT barangay, COUNT(*) as count 
                 FROM pop_data 
                 WHERE barangay IS NOT NULL 
                 GROUP BY barangay 
                 ORDER BY barangay";
$barangayResult = $conn->query($barangayQuery);

$barangayCounts = array_fill_keys($barangays, 0); // Initialize with zeros
if ($barangayResult) {
    while ($row = $barangayResult->fetch_assoc()) {
        if (isset($barangayCounts[$row['barangay']])) {
            $barangayCounts[$row['barangay']] = (int)$row['count'];
        }
    }
}

// Replace the gender counting section with this optimized query
$genderSql = "SELECT 
    sex,
    COUNT(*) as count 
FROM pop_data 
WHERE sex IS NOT NULL 
GROUP BY sex";

$genderResult = $conn->query($genderSql);
$genderCounts = ["Male" => 0, "Female" => 0];

if ($genderResult) {
    while ($row = $genderResult->fetch_assoc()) {
        $sex = ucfirst(strtolower($row['sex']));
        if (isset($genderCounts[$sex])) {
            $genderCounts[$sex] = (int)$row['count'];
        }
    }
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
        <a class="nav-link" href="resident.php">Residents</a>
        <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
    </nav>
    <div class="main-content position-relative">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <p class="lead">Sustainable growth starts with balance</p>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="counter-total">
                    <h5>Total Population</h5>
                    <h2 class="counter-number" data-value="<?= $totalPopulation ?>"><?= $totalPopulation ?></h2>
                    <p>Residents</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-18" data-target="<?= $totalPopulation > 0 ? round(($ageGroups["18-30"] / $totalPopulation) * 100, 1) : 0 ?>">
                    <h6>Age 18-30</h6>
                    <p class="counter-percent">
                        <?= $totalPopulation > 0 ? round(($ageGroups["18-30"] / $totalPopulation) * 100, 1) : 0 ?>%
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-31" data-target="<?= $totalPopulation > 0 ? round(($ageGroups["31-50"] / $totalPopulation) * 100, 1) : 0 ?>">
                    <h6>Age 31-50</h6>
                    <p class="counter-percent">
                        <?= $totalPopulation > 0 ? round(($ageGroups["31-50"] / $totalPopulation) * 100, 1) : 0 ?>%
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-51" data-target="<?= $totalPopulation > 0 ? round(($ageGroups["51-60"] / $totalPopulation) * 100, 1) : 0 ?>">
                    <h6>Age 51-60</h6>
                    <p class="counter-percent">
                        <?= $totalPopulation > 0 ? round(($ageGroups["51-60"] / $totalPopulation) * 100, 1) : 0 ?>%
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
        <div class="row mb-4">
            <!-- Barangay Distribution Chart -->
            <div class="col-md-7">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Population by Barangay</h5>
                    </div>
                    <div class="chart-toggle mb-3">
    <button class="btn btn-primary active" data-chart="bar">Bar</button>
    <button class="btn btn-primary" data-chart="line">Line</button>
    <button class="btn btn-primary" data-chart="pie">Pie</button>
</div>
                    <div class="chart-container">
                        <canvas id="barangayChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Gender Distribution Chart -->
            <div class="col-md-5">
    <div class="gender-chart-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Gender Distribution</h5>
        </div>
        <div class="gender-chart-container">
            <canvas id="genderChart"></canvas>
        </div>
        <div class="gender-legend">
            <div class="legend-item">
                <div class="legend-color" style="background: rgba(59, 131, 246, 1)"></div>
                <span>Male</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: rgba(236, 72, 153, 0.8)"></div>
                <span>Female</span>
            </div>
        </div>
    </div>
</div>
        </div>
        <div id="chartLoading" style="display:none;" class="loader-container">
    <div class="loader"></div>
</div>
<div id="chartError" style="display:none;" class="alert alert-danger">
    Error loading chart data. Please try again later.
</div>
        <hr>
        <form class="d-flex search-bar" method="get" action="">
            <input class="form-control me-2" type="search" name="search" placeholder="Search resident..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-secondary ms-2" href="main.php">Reset</a>
            <a class="btn btn-success ms-auto" href="backend/create.php">Add </a>
            <a class="btn btn-warning ms-2" href="backend/import.php">Import </a>
            <button type="button" class="btn btn-info ms-2" id="exportBtn">Export</button>
            
            <!-- Replace the existing sort dropdown -->
            <div class="dropdown ms-2">
    <button class="btn btn-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
        <?php
        $currentSort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
        $currentOrder = isset($_GET['order']) ? $_GET['order'] : 'asc';
        echo 'Sort by: ' . ucfirst($currentSort) . ' (' . ($currentOrder === 'asc' ? 'A-Z' : 'Z-A') . ')';
        ?>
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item sort-option" href="#" data-column="name" data-order="asc">Name (A-Z)</a></li>
        <li><a class="dropdown-item sort-option" href="#" data-column="name" data-order="desc">Name (Z-A)</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item sort-option" href="#" data-column="age" data-order="asc">Age (Ascending)</a></li>
        <li><a class="dropdown-item sort-option" href="#" data-column="age" data-order="desc">Age (Descending)</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item sort-option" href="#" data-column="barangay" data-order="asc">Barangay (A-Z)</a></li>
        <li><a class="dropdown-item sort-option" href="#" data-column="barangay" data-order="desc">Barangay (Z-A)</a></li>
    </ul>
</div>
        </form>
        <!-- Replace the existing table header and body -->
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <!-- Removed ID column -->
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
                        <!-- Removed ID column -->
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
                    <tr><td colspan="8" class="text-center">No residents found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Replace the existing pagination controls -->
    <div class="pagination-wrapper mt-4">
    <div class="d-flex flex-column align-items-center">
        <div class="text-muted mb-2">
            Showing <?= min(($page - 1) * $records_per_page + 1, $total_records) ?> to 
            <?= min($page * $records_per_page, $total_records) ?> of <?= $total_records ?> entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?><?= $search ? "&search=$search" : "" ?>">
                            Previous
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?><?= $search ? "&search=$search" : "" ?>">
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Pass PHP data to JS for external JS file
        window.barangayDataPoints = <?= json_encode($barangayDataPoints, JSON_NUMERIC_CHECK); ?>;
        window.genderDataPoints = <?= json_encode($genderDataPoints, JSON_NUMERIC_CHECK); ?>;
    </script>
    <script>
    // Initialize chart data
    const chartData = {
        barangayLabels: <?= json_encode(array_keys($barangayCounts)) ?>,
        barangayData: <?= json_encode(array_values($barangayCounts)) ?>,
        genderData: [<?= $genderCounts['Male'] ?>, <?= $genderCounts['Female'] ?>]
    };
</script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/setting.js"></script>
    <script>
    // You can add custom JS here if needed
    </script>

</body>

</html>