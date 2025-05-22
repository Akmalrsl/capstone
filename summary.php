<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
//connect database
$conn = new mysqli("localhost", "root", "", "healthmate");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT * FROM health_data WHERE id = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $height_m = $row['height'] / 100;
    $weight = $row['weight'];
    $bmi = $height_m > 0 ? $weight / ($height_m * $height_m) : 0;
    $bmi = round($bmi, 1);

    $systolic = $row['systolicbp'];
    $diastolic = $row['diastolicbp'];
    $cholesterol = $row['cholesterol_level'];

    if ($bmi < 18.5) $bmi_category = "Underweight";
    elseif ($bmi < 25) $bmi_category = "Normal";
    elseif ($bmi < 30) $bmi_category = "Overweight";
    else $bmi_category = "Obese";

    if ($systolic < 120 && $diastolic < 80) $bp_category = "Normal";
    elseif ($systolic < 130 && $diastolic < 80) $bp_category = "Elevated";
    elseif ($systolic < 140 || $diastolic < 90) $bp_category = "High BP Stage 1";
    else $bp_category = "High BP Stage 2";

    if ($cholesterol < 200) {
        $chol_category = "Desirable";
        $chol_tag = "✅ Safe";
        $chol_color = "#4CAF50";
    } elseif ($cholesterol <= 239) {
        $chol_category = "Borderline High";
        $chol_tag = "⚠️ Caution";
        $chol_color = "#FFC107";
    } else {
        $chol_category = "High";
        $chol_tag = "❗Danger";
        $chol_color = "#F44336";
    }

    $health_score = 0;
    if ($bmi_category == 'Normal') $health_score += 30;
    if ($bp_category == 'Normal') $health_score += 30;
    if ($chol_category == 'Desirable') $health_score += 40;
} else {
    echo "No data found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Summary</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            padding: 30px;
        }
        .summary-container {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            max-width: 700px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
        }
        ul {
            list-style: none;
            padding-left: 0;
        }
        li {
            margin: 10px 0;
            font-size: 1.1rem;
        }
        .tag {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="summary-container">
    <h2>📋 Instant Health Summary</h2>
    <ul>
        <li><strong>BMI:</strong> <?= $bmi ?> (<?= $bmi_category ?>)</li>
        <li><strong>Blood Pressure:</strong> <?= $systolic ?>/<?= $diastolic ?> mmHg (<?= $bp_category ?>)</li>
        <li><strong>Cholesterol:</strong> <?= $cholesterol ?> mg/dL 
            <span class="tag" style="background-color: <?= $chol_color ?>; color: white;">
                <?= $chol_category ?> <?= $chol_tag ?>
            </span>
        </li>
    </ul>

    <h3 style="text-align:center;">🧭 Overall Health Indicator</h3>
    <canvas id="healthMeter" width="300" height="150" style="display: block; margin: 20px auto;"></canvas>
</div>

<script>
    const ctx = document.getElementById('healthMeter').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Health Score', 'Remaining'],
            datasets: [{
                data: [<?= $health_score ?>, 100 - <?= $health_score ?>],
                backgroundColor: ['#4CAF50', '#e0e0e0'],
                borderWidth: 0
            }]
        },
        options: {
            circumference: 180,
            rotation: -90,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
</script>

</body>
</html>
