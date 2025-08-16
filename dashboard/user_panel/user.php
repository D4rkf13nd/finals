<?php
session_start();
// Change admin check to user check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

// Update database connection path
require_once "../backend/db.php";

// Initialize variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total records and population
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

// Get age group statistics
$ageGroupSql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN age BETWEEN 18 AND 30 THEN 1 ELSE 0 END) as age_18_30,
    SUM(CASE WHEN age BETWEEN 31 AND 50 THEN 1 ELSE 0 END) as age_31_50,
    SUM(CASE WHEN age BETWEEN 51 AND 60 THEN 1 ELSE 0 END) as age_51_60
FROM pop_data";

$ageResult = $conn->query($ageGroupSql);
if ($ageResult) {
    $ageCounts = $ageResult->fetch_assoc();
    $totalPopulation = (int)$ageCounts['total'];
    $ageGroups = [
        "18-30" => (int)$ageCounts['age_18_30'],
        "31-50" => (int)$ageCounts['age_31_50'],
        "51-60" => (int)$ageCounts['age_51_60']
    ];
} else {
    $totalPopulation = 0;
    $ageGroups = [
        "18-30" => 0,
        "31-50" => 0,
        "51-60" => 0
    ];
}

// Get residents data
$sql = "SELECT * FROM pop_data";
if ($search) {
    $sql .= " WHERE name LIKE ? OR address LIKE ? OR barangay LIKE ?";
}
$sql .= " LIMIT ? OFFSET ?";

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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="sidebar">
        <div class="text-center mb-115">
            <div class="logo d-flex align-items-center gap-2">
                <img src="../assets/img/binky.png" alt="Binky-Logo" style="width: 200px; height: 200px; object-fit: contain;">
                <img src="../assets/img/tayo.png" alt="Tayo-Logo" style="width: 100px; height: 100px; margin-left: -80px;">
            </div>
            <h4 style="color:#000000;">User Menu</h4>
        </div>
        <a class="nav-link active" href="user.php">Dashboard</a>
        <a class="nav-link" href="profile.php">Profile</a>
        <a class="nav-link" style="color: red;" href="../logout.php">Logout</a>
    </nav>
    <div class="main-content position-relative">
        <div class="dashboard-header">
            <h1>Population Dashboard</h1>
            <p class="lead">Welcome, <?= htmlspecialchars($_SESSION["username"]) ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="counter-total">
                    <h5>Total Population</h5>
                    <h2 class="counter-number" data-value="<?= $totalPopulation ?>"><?= $totalPopulation ?></h2>
                    <p>Residents</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-18">
                    <h6>Age 18-30</h6>
                    <p class="counter-percent">
                        <?= $totalPopulation > 0 ? round(($ageGroups["18-30"] / $totalPopulation) * 100, 1) : 0 ?>%
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-31">
                    <h6>Age 31-50</h6>
                    <p class="counter-percent">
                        <?= $totalPopulation > 0 ? round(($ageGroups["31-50"] / $totalPopulation) * 100, 1) : 0 ?>%
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-51">
                    <h6>Age 51-60</h6>
                    <p class="counter-percent">
                        <?= $totalPopulation > 0 ? round(($ageGroups["51-60"] / $totalPopulation) * 100, 1) : 0 ?>%
                    </p>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <form class="d-flex search-bar" method="get" action="">
            <input class="form-control me-2" type="search" name="search" placeholder="Search resident..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-secondary ms-2" href="user.php">Reset</a>
        </form>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Barangay</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Birthday</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($residents)): ?>
                    <?php foreach ($residents as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['age']) ?></td>
                        <td><?= htmlspecialchars($row['sex']) ?></td>
                        <td><?= htmlspecialchars($row['barangay'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= htmlspecialchars($row['contact']) ?></td>
                        <td><?= htmlspecialchars($row['birthday']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No residents found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php include '../includes/pagination.php'; ?>
    </div>

    <!-- Scripts - Remove Chart.js, keep only Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>