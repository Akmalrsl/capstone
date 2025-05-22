<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

//connect to db
$conn = new mysqli("localhost", "root", "", "healthmate");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

//to get latest row from autogeneration
$sql = "SELECT gender, age, cholesterol_level, systolicbp, diastolicbp, bmi 
        FROM health_data ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    //cuz u need proper variable data types to match the desired input for the trained model
    $gender = ($row['gender'] === 'male') ? 1 : 0;
    $age = (int)$row['age'];
    $cholesterol = (float)$row['cholesterol_level'];
    $systolic = (float)$row['systolicbp'];
    $diastolic = (float)$row['diastolicbp'];
    $bmi = (float)$row['bmi'];

    $data = [
        'gender' => $gender,
        'age' => $age,
        'cholesterol_level' => $cholesterol,
        'systolicbp' => $systolic,
        'diastolicbp' => $diastolic,
        'bmi' => $bmi
    ];

    //to debug
    //echo "<pre>Payload sent to API:\n" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>"; //to show if data is obtained from database

    //send to flask
    $ch = curl_init('http://127.0.0.1:5000/predict'); //send to /predict endpoint in predict_api.py
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "<pre>❌ Flask API Error: HTTP $httpCode\nRaw response:\n$response</pre>";
        exit;
    }

    //check for error
    $result = json_decode($response, true);
    if (isset($result['prediction'])) {
        $prediction = $result['prediction'];
        $message = ($prediction == 1)
            ? "⚠️ High Risk of Hypertension"
            : "✅ Low Risk of Hypertension";
    } else {
        $message = "❌ No prediction received. Response: <pre>" . htmlspecialchars($response) . "</pre>";
    }

    //output
    echo "<h2>🩺 AI Health Checkup Result</h2>";
    echo "<p><strong>Status:</strong> $message</p>";
    echo "<hr><h3>📊 Your Input Data</h3><ul>";
    foreach ($data as $key => $value) {
        echo "<li><strong>$key:</strong> $value</li>";
    }
    echo "</ul>";

} else {
    echo "No data found.";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AI Health Checkup Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding: 30px;
      font-family: 'Segoe UI', sans-serif;
    }
    .report-card {
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      border-radius: 10px;
    }
    .badge-high {
      background-color: #dc3545;
    }
    .badge-low {
      background-color: #28a745;
    }
  </style>
</head>
<body>
<div class="card report-card p-4">
  <h3 class="text-center mb-3">🩺 AI Health Checkup Result</h3>
  <p><strong>Status:</strong> 
    <span class="badge <?= ($prediction == 1) ? 'badge-high' : 'badge-low' ?>">
      <?= $message ?>
    </span>
  </p>
  <hr>
  <h5>📊 Your Input Data</h5>
  <ul class="list-group mb-3">
    <?php foreach ($data as $key => $value): ?>
      <li class="list-group-item">
        <strong><?= ucfirst($key) ?>:</strong> <?= $value ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
</body>
</html>

