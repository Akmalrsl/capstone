<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

session_start();

// Block unauthorized access
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['user']['role'];
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];

include 'db_connect.php';

$chartType = isset($_GET['chart']) ? $_GET['chart'] : 'bar';
$genderFilter = isset($_GET['gender']) ? $_GET['gender'] : '';

$whereClause = ($role === 'admin')
    ? "WHERE 1"
    : "WHERE DATE(date_created) >= CURDATE() - INTERVAL 7 DAY";

if (!empty($genderFilter)) {
    $whereClause .= " AND gender = '" . $health_conn->real_escape_string($genderFilter) . "'";
}

$sql = "SELECT age, systolicbp, diastolicbp FROM health_data $whereClause";
$result = $health_conn->query($sql);

$ageGroups = [
    "10-19" => [],
    "20-29" => [],
    "30-39" => [],
    "40-49" => [],
    "50-59" => [],
    "60-69" => [],
    "70+" => []
];

while ($row = $result->fetch_assoc()) {
    $age = (int)$row['age'];
    $systolic = is_numeric($row['systolicbp']) ? (float)$row['systolicbp'] : null;
    $diastolic = is_numeric($row['diastolicbp']) ? (float)$row['diastolicbp'] : null;

    if ($systolic === null && $diastolic === null) continue;

    if ($age < 20) $group = "10-19";
    elseif ($age < 30) $group = "20-29";
    elseif ($age < 40) $group = "30-39";
    elseif ($age < 50) $group = "40-49";
    elseif ($age < 60) $group = "50-59";
    elseif ($age < 70) $group = "60-69";
    else $group = "70+";

    $ageGroups[$group][] = ['systolic' => $systolic, 'diastolic' => $diastolic];
}

$labels = [];
$systolicData = [];
$diastolicData = [];

foreach ($ageGroups as $range => $data) {
    $systolicValues = array_filter(array_column($data, 'systolic'), fn($v) => $v !== null);
    $diastolicValues = array_filter(array_column($data, 'diastolic'), fn($v) => $v !== null);

    if (count($systolicValues) > 0 || count($diastolicValues) > 0) {
        $labels[] = $range;
        $avgSys = count($systolicValues) > 0 ? array_sum($systolicValues) / count($systolicValues) : 0;
        $avgDia = count($diastolicValues) > 0 ? array_sum($diastolicValues) / count($diastolicValues) : 0;
        $systolicData[] = round($avgSys, 1);
        $diastolicData[] = round($avgDia, 1);
    }
}

$statsQuery = "SELECT 
    MAX(systolicbp) AS max_sys,
    MIN(systolicbp) AS min_sys,
    AVG(systolicbp) AS avg_sys,
    MAX(diastolicbp) AS max_dia,
    MIN(diastolicbp) AS min_dia,
    AVG(diastolicbp) AS avg_dia,
    COUNT(*) AS total_entries
    FROM health_data $whereClause";

$statsResult = $health_conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

$riskQuery = "SELECT age, gender, systolicbp, diastolicbp, cholesterol_level 
              FROM health_data 
              WHERE (systolicbp > 130 OR diastolicbp > 90) 
              AND (systolicbp IS NOT NULL OR diastolicbp IS NOT NULL)";

if ($role !== 'admin') {
    $riskQuery .= " AND DATE(date_created) >= CURDATE() - INTERVAL 7 DAY";
}
if (!empty($genderFilter)) {
    $riskQuery .= " AND gender = '" . $health_conn->real_escape_string($genderFilter) . "'";
}

$riskResult = $health_conn->query($riskQuery);
$highRiskUsers = [];
while ($row = $riskResult->fetch_assoc()) {
    $highRiskUsers[] = $row;
}

$health_conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Blood Pressure by Age Group</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #e0e0e0;
            padding-top: 80px;
        }

        .chart-container {
            width: 100%;
            max-width: 1000px;
            height: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .controls {
            max-width: 1000px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .summary-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 30px auto;
            max-width: 1000px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .summary-box h5 {
            text-align: center;
            margin-bottom: 20px;
        }

        .summary-item {
            text-align: center;
        }
    </style>
    <style media="print">
        body {
            background: white !important;
            color: black;
        }

        nav,
        .controls,
        button {
            display: none !important;
        }

        .chart-container {
            box-shadow: none !important;
            border: 1px solid #ccc;
        }

        .summary-box {
            page-break-inside: avoid;
        }

        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
    </style>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Healthmate</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="controls">
        <form method="GET" class="d-flex gap-3 ms-3">
            <div>
                <label for="chart">Chart Type:</label>
                <select name="chart" id="chart" class="form-select" onchange="this.form.submit()">
                    <option value="bar" <?= $chartType == 'bar' ? 'selected' : '' ?>>Bar</option>
                    <option value="line" <?= $chartType == 'line' ? 'selected' : '' ?>>Line</option>
                    <option value="radar" <?= $chartType == 'radar' ? 'selected' : '' ?>>Radar</option>
                </select>
            </div>
            <div>
                <label for="gender">Gender:</label>
                <select name="gender" id="gender" class="form-select" onchange="this.form.submit()">
                    <option value="" <?= $genderFilter == '' ? 'selected' : '' ?>>All</option>
                    <option value="male" <?= $genderFilter == 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $genderFilter == 'female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
        </form>
    </div>

    <div class="chart-container">
        <canvas id="bpChart"></canvas>
        <div class="d-flex justify-content-center mt-4">
            <button class="btn btn-success px-4 py-2 rounded-pill shadow-sm" onclick="downloadChart()">
                📥 Download Chart as PNG
            </button>
        </div>
        <div class="d-flex justify-content-center mt-2">
            <button class="btn btn-outline-dark px-4 py-2 rounded-pill shadow-sm" onclick="printReport()">
                🖨️ Print Medical Report
            </button>
        </div>

    </div>

    <div class="summary-box mt-5 pt-4" style="margin-top: 100px !important;">
        <h5>Summary Statistics (<?= $genderFilter ? ucfirst($genderFilter) : 'All Genders' ?>)</h5>
        <div class="row">
            <div class="col-md-2 summary-item"><strong>Entries</strong><br><?= $stats['total_entries'] ?></div>
            <div class="col-md-2 summary-item"><strong>Max Systolic</strong><br><?= $stats['max_sys'] ?> mmHg</div>
            <div class="col-md-2 summary-item"><strong>Min Systolic</strong><br><?= $stats['min_sys'] ?> mmHg</div>
            <div class="col-md-2 summary-item"><strong>Avg Systolic</strong><br><?= round($stats['avg_sys'], 1) ?> mmHg</div>
            <div class="col-md-2 summary-item"><strong>Max Diastolic</strong><br><?= $stats['max_dia'] ?> mmHg</div>
            <div class="col-md-2 summary-item"><strong>Avg Diastolic</strong><br><?= round($stats['avg_dia'], 1) ?> mmHg</div>
        </div>
    </div>

    <div class="summary-box">
        <h5 class="text-danger">⚠ Potential High-Risk Users (Systolic > 130 or Diastolic > 90)</h5>
        <?php if (count($highRiskUsers) > 0): ?>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Systolic BP</th>
                        <th>Diastolic BP</th>
                        <th>Cholesterol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($highRiskUsers as $user): ?>
                        <tr>
                            <td><?= $user['age'] ?></td>
                            <td><?= ucfirst($user['gender']) ?></td>
                            <td class="text-danger fw-bold"><?= $user['systolicbp'] !== null ? $user['systolicbp'] : 'NULL' ?></td>
                            <td class="text-danger fw-bold"><?= $user['diastolicbp'] !== null ? $user['diastolicbp'] : 'NULL' ?></td>
                            <td><?= $user['cholesterol_level'] !== null ? $user['cholesterol_level'] : 'NULL' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No high-risk users found at the moment.</p>
        <?php endif; ?>
    </div>

    <script>
        const ctx = document.getElementById('bpChart').getContext('2d');
        const bpChart = new Chart(ctx, {
            type: '<?= $chartType ?>',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                        label: 'Avg Systolic BP',
                        data: <?= json_encode($systolicData) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: <?= $chartType === 'radar' ? 'true' : 'false' ?>
                    },
                    {
                        label: 'Avg Diastolic BP',
                        data: <?= json_encode($diastolicData) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        fill: <?= $chartType === 'radar' ? 'true' : 'false' ?>
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: <?= $chartType === 'radar' ? '{}' : '{
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: "Blood Pressure (mmHg)"
                }
            },
            x: {
                title: {
                    display: true,
                    text: "Age Group"
                }
            }
        }' ?>
            }
        });

        //download as png
        function downloadChart() {
            const link = document.createElement('a');
            link.download = 'bp_chart.png';
            link.href = bpChart.toBase64Image();
            link.click();
        }
    </script>

    <script>
        function printReport() {
            const elementsToHide = document.querySelectorAll('button, nav, .controls');
            elementsToHide.forEach(el => el.style.display = 'none');

            //command for print
            window.print();

            setTimeout(() => {
                elementsToHide.forEach(el => el.style.display = '');
            }, 1000);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>