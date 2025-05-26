<?php
//$conn = new mysqli("localhost",  "root", "", "healthmate");
$conn = new mysqli("capstonespring2025.duckdns.org", "Capstone", "Capstone123", "healthmate", 3306); //new db

//connect to mysql database called healthmate

//check connection
if ($conn->connect_error) {
    die("Database connection failed: " .$conn->connect_error);
}


//fetching latest health data
$sql = "SELECT gender,age, cholesterol_level, systolicbp, diastolicbp, bmi
        FROM health_data ORDER BY id DESC LIMIT 1"; //order by descencing, latest row hence latest data

$result = $conn->query($sql); //make the query

if ($result->num_rows >0) {//if there is something (not empty)
    $row = $result->fetch_assoc(); //fetches the next row

    //prepare data in variables to feed into ai model
    $age = (int)$row['age'];
    $gender = ($row['gender'] === 'male') ? 1:0;
    $cholesterol = (float)$row['cholesterol_level'];
    $systolic = (float)$row['systolicbp'];
    $diastolic = (float)$row['diastolicbp'];
    $bmi = (float)$row['bmi'];
}

//temporarily echo to see if data is fetched

echo "Gender: $gender <br>";
echo "Age: $age <br>";
echo "Cholesterol: $cholesterol <br>";
echo "Systolic: $systolic <br>";
echo "Diastolic: $diastolic <br>";
echo "BMI: $bmi <br>";

//okay nice it works

//send to flask

$flask_url = 'http://127.0.0.1:5000/predict'; //goes to the predict app route


//this builds a PHP associative array with the health data
//php assoc array is like a python dictionary (key, value)
$data = [
    'gender' => $gender,
    'age' => $age,
    'cholesterol_level' => $cholesterol,
    'systolicbp' => $systolic,
    'diastolicbp' => $diastolic,
    'bmi' => $bmi
];

//initialise a new cURL session (kinda like opening a connection to a website)

$ch = curl_init($flask_url); //THIS IS THE LINK TO FLASK

curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
//instead of printing results to the browser, save it in a variable

//sets assoc array into JSON format because flask API expects JSON
curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));

curl_setopt($ch,CURLOPT_HTTPHEADER, ['content-type: application/json']);

//execute the request 
$response = curl_exec($ch);

//decode the result
$result = json_decode($response,true);

//dump raw response from flask to check
echo "<pre>";
var_dump($response);
echo "</pre>";



//get the prediction
$prediction = $result['prediction'];

//show it on the page
echo "<h2>Hypertension Prediction Result : $prediction </h2>";


?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
            background-color : #e0e0e0; 
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
</body>
</html>