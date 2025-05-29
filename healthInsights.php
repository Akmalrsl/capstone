<?php
// Connect to database
$conn = new mysqli("capstonespring2025.duckdns.org", "Capstone", "Capstone123", "healthmate", 3306);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get all IDs for dropdown
$id_result = $conn->query("SELECT id FROM health_data ORDER BY id DESC");

// If form is submitted
$prediction = null;
if (isset($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
    $sql = "SELECT * FROM health_data WHERE id = $patient_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Prepare data to send to Flask
        $data = [
            'gender' => ($row['gender'] === 'male') ? 1 : 0,
            'age' => (int)$row['age'],
            'cholesterol_level' => (float)$row['cholesterol_level'],
            'systolicbp' => (float)$row['systolicbp'],
            'diastolicbp' => (float)$row['diastolicbp'],
            'bmi' => (float)$row['bmi']
        ];

        // Send data using cURL to Flask
        $curl = curl_init('http://127.0.0.1:5000/predict');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($curl);
        if ($response === false) {
            $prediction = ['result' => 'Error contacting Flask API: ' . curl_error($curl)];
        } else {
            $prediction = json_decode($response, true);
            
            if (isset($prediction['prediction'])) {
                $pred_val = (int)$prediction['prediction'];
                $insert_sql = "INSERT INTO predictions (health_data_id, prediction_value) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ii", $patient_id, $pred_val);
                $stmt->execute();
                $stmt->close();
            }

        }
        curl_close($curl);
    } else {
        $error = "No patient found with ID $patient_id.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
         body {
            background-color: #e0e0e0;
        }

        .medical-report-box{
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 80px;
        }

        .user-data{
            list-style-type : none;
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Dropdown
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="medical-report-box mx-auto col-md-8 text-center">
        <h2>Select Patient ID</h2>
        <form method="get">
            <select name="patient_id" required>
                <option value="">-- Choose ID --</option>
                <?php while($id_row = $id_result->fetch_assoc()): ?>
                    <option value="<?= $id_row['id'] ?>" <?= (isset($patient_id) && $patient_id == $id_row['id']) ? 'selected' : '' ?>>
                        <?= $id_row['id'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Generate Report</button>
        </form>
    

        <?php if (isset($row)): ?>
            <h2>Medical Report for Patient ID <?= htmlspecialchars($patient_id) ?></h2>
            <ul class="user-data">
                <li><strong>Gender:</strong> <?= htmlspecialchars($row['gender']) ?></li>
                <li><strong>Age:</strong> <?= htmlspecialchars($row['age']) ?></li>
                <li><strong>Cholesterol Level:</strong> <?= htmlspecialchars($row['cholesterol_level']) ?></li>
                <li><strong>Systolic BP:</strong> <?= htmlspecialchars($row['systolicbp']) ?></li>
                <li><strong>Diastolic BP:</strong> <?= htmlspecialchars($row['diastolicbp']) ?></li>
                <li><strong>BMI:</strong> <?= htmlspecialchars($row['bmi']) ?></li>
            </ul>
            <h3>AI Prediction</h3>
            <?php if (isset($prediction['prediction'])): ?>
                <p><strong>Risk of Hypertension:</strong> <?= $prediction['prediction'] == 1 ? 'High' : 'Low' ?></p>
            <?php else: ?>
                 <p>Prediction not available</p>
            <?php endif; ?>

        <?php elseif (isset($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>

    
</body>
</html>
