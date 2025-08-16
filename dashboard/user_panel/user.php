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

// Validate sort column
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

$allowedColumns = ['name', 'age', 'barangay', 'sex', 'birthday'];
if (!in_array($sort, $allowedColumns)) {
    $sort = 'name';
}

// Validate order
$order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

// Get residents data
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
            <h4 style="color:#000000;">Menu</h4>
        </div>
        <a class="nav-link active" href="user.php">Dashboard</a>
        <a class="nav-link" href="calendar.php">Calendar</a>
        <a class="nav-link" href="setting.php">Setting</a>
        <a class="nav-link" style="color: red;" href="../logout.php">Logout</a>
    </nav>
    <div class="main-content position-relative">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <p class="lead">Welcome, <?= htmlspecialchars($_SESSION["username"]) ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="counter-total">
                    <h5>Total Population</h5>
                    <h2 class="counter-number"><?= $totalPopulation ?></h2>
                    <p>Residents</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-18">
                    <h6>Age 18-30</h6>
                    <p class="counter-percent" data-value="<?= $totalPopulation > 0 ? round(($ageGroups["18-30"] / $totalPopulation) * 100, 1) : 0 ?>">
                        <?= $totalPopulation > 0 ? round(($ageGroups["18-30"] / $totalPopulation) * 100, 1) : 0 ?>
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-31">
                    <h6>Age 31-50</h6>
                    <p class="counter-percent" data-value="<?= $totalPopulation > 0 ? round(($ageGroups["31-50"] / $totalPopulation) * 100, 1) : 0 ?>">
                        <?= $totalPopulation > 0 ? round(($ageGroups["31-50"] / $totalPopulation) * 100, 1) : 0 ?>
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="counter-51">
                    <h6>Age 51-60</h6>
                    <p class="counter-percent" data-value="<?= $totalPopulation > 0 ? round(($ageGroups["51-60"] / $totalPopulation) * 100, 1) : 0 ?>">
                        <?= $totalPopulation > 0 ? round(($ageGroups["51-60"] / $totalPopulation) * 100, 1) : 0 ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <form class="d-flex search-bar" method="get" action="">
            <div class="d-flex flex-grow-1 gap-2">
                <input class="form-control me-2" type="search" name="search" placeholder="Search resident..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <a class="btn btn-secondary" href="user.php">Reset</a>
                <a class="btn btn-success" href="create.php">Add</a>
                <a class="btn btn-warning" href="import.php">Import</a>
                <button type="button" class="btn btn-info" onclick="window.location.href='export.php'">Export</button>
                <div class="dropdown">
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
            </div>
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
        <div class="pagination-wrapper mt-4">
            <div class="d-flex flex-column align-items-center">
                <div class="text-muted mb-2">
                    Showing <?= min(($page - 1) * $records_per_page + 1, $total_records) ?>
                    to <?= min($page * $records_per_page, $total_records) ?> of <?= $total_records ?> entries
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?><?= $search ? "&search=$search" : "" ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?><?= $search ? "&search=$search" : "" ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Scripts - Remove Chart.js, keep only Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function animateCounter(element, start, end, duration = 2000) {
    const range = end - start;
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function for smooth animation
        const easeOutQuad = progress * (2 - progress);
        
        const currentValue = start + (range * easeOutQuad);
        
        // Format based on whether it's a percentage or number
        if (element.classList.contains('counter-percent')) {
            element.textContent = currentValue.toFixed(1) + '%';
        } else {
            element.textContent = Math.round(currentValue).toLocaleString();
        }

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }

    requestAnimationFrame(updateCounter);
}

// Initialize all counters
document.addEventListener('DOMContentLoaded', function() {
    // Total population counter
    const totalCounter = document.querySelector('.counter-number');
    if (totalCounter) {
        animateCounter(totalCounter, 0, <?= $totalPopulation ?>);
    }

    // Age group percentage counters
    const percentCounters = document.querySelectorAll('.counter-percent');
    percentCounters.forEach(counter => {
        const container = counter.closest('div[class^="counter-"]');
        if (container) {
            const currentText = counter.textContent.trim();
            const targetValue = parseFloat(currentText);
            if (!isNaN(targetValue)) {
                animateCounter(counter, 0, targetValue);
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle sort dropdown clicks
    document.querySelectorAll('.sort-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const column = this.dataset.column;
            const order = this.dataset.order;
            const currentUrl = new URL(window.location.href);
            const search = currentUrl.searchParams.get('search');
            
            let newUrl = `user.php?sort=${column}&order=${order}`;
            if (search) {
                newUrl += `&search=${encodeURIComponent(search)}`;
            }
            window.location.href = newUrl;
        });
    });
});
</script>
</body>
</html>