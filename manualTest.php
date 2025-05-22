<!DOCTYPE html>
<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Redirect to login if not logged in
// session_start();
// // Temporarily skip login check
// if (!isset($_SESSION['user'])) {
//     header("Location: login.php");
//     exit;
// }
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manual Health Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e0e0e0;
        }

        .form-section {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 80px;
        }

        .bmi-result {
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 10px;
        }

        .btn-group-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
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

    <div class="container">
        <div class="form-section mx-auto col-md-8">
            <h2 class="mb-4">Manual Health Testing</h2>

            <form method="POST" action="submit_health.php" id="healthForm">
                <div class="mb-3">
                    <label for="age" class="form-label">Age (years)</label>
                    <input type="number" class="form-control" id="age" name="age" required>
                </div>

                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">-- Select --</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="height" class="form-label">Height (cm)</label>
                        <input type="number" class="form-control" id="height" name="height" oninput="calculateBMI()" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control" id="weight" name="weight" oninput="calculateBMI()" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">BMI Result</label>
                    <div id="bmiResult" class="bmi-result text-primary">Enter height and weight</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="systolic" class="form-label">Systolic BP (mmHg)</label>
                        <input type="number" class="form-control" id="systolic" name="systolic" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="diastolic" class="form-label">Diastolic BP (mmHg)</label>
                        <input type="number" class="form-control" id="diastolic" name="diastolic" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="cholesterol" class="form-label">Cholesterol Level</label>
                    <select class="form-select" id="cholesterol" name="cholesterol" required>
                        <option value="">-- Select --</option>
                        <option value="1">1 - Normal</option>
                        <option value="2">2 - Above Normal</option>
                        <option value="3">3 - Well Above Normal</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit</button>
            </form>

            <div class="mt-4">
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-3">
                    <button class="btn btn-secondary" onclick="generateRandomData()">Generate Random Data</button>
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-3 mb-3">
                    <a href="livesensor.php" class="btn btn-dark">Live Sensor</a>
                </div>

                <div class="d-flex justify-content-center">
                    <a href="export_csv.php" class="btn btn-outline-success">Download CSV</a>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <form id="uploadCsvForm" action="upload_csv.php" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control form-control-sm w-auto">
                        <button type="submit" class="btn btn-sm btn-primary px-3 rounded-pill shadow-sm">
                            📁 Upload CSV
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </div>

    <script>
        function calculateBMI() {
            const height = parseFloat(document.getElementById('height').value) / 100;
            const weight = parseFloat(document.getElementById('weight').value);
            const resultEl = document.getElementById('bmiResult');

            if (height > 0 && weight > 0) {
                const bmi = weight / (height * height);
                let category = "";

                if (bmi < 18.5) category = "Underweight";
                else if (bmi < 24.9) category = "Normal";
                else if (bmi < 29.9) category = "Overweight";
                else category = "Obese";

                resultEl.innerHTML = `Your BMI is ${bmi.toFixed(1)} (${category})`;
            } else {
                resultEl.innerHTML = "Enter height and weight";
            }
        }

        function generateRandomData() {
            const getRandom = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
            const genders = ['male', 'female'];
            const randomGender = genders[Math.floor(Math.random() * genders.length)];

            document.getElementById('age').value = getRandom(18, 70);
            document.getElementById('gender').value = randomGender;
            document.getElementById('height').value = getRandom(150, 190);
            document.getElementById('weight').value = getRandom(50, 100);
            document.getElementById('systolic').value = getRandom(100, 140);
            document.getElementById('diastolic').value = getRandom(60, 90);
            document.getElementById('cholesterol').value = getRandom(1, 3);

            calculateBMI();
        }

        document.getElementById('healthForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('submit_health.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert(data.trim() === '✅ Data successfully saved!' ? 'Data successfully saved!' : 'Something went wrong:\n' + data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });

        document.getElementById('uploadCsvForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('csv_file');
            if (!fileInput.value) {
                e.preventDefault();
                alert('Please choose a CSV file before uploading!');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>