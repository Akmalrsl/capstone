<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

// 1. Get values from POST
$sensor_id = $_POST['sensor_id'];
$ecg = (float) $_POST['ecgAverage'];  // Optional: still capture if you want to store later

// Get SBP and DBP directly from the form
$systolic = (float) $_POST['sbp'];
$diastolic = (float) $_POST['dbp'];

$age = $_POST['age'];
$gender = $_POST['gender'];
$height_cm = (float) $_POST['height'];
$weight = (float) $_POST['weight'];
$cholesterol = (int) $_POST['cholesterol'];

// Safety check
if ($height_cm <= 0) {
    die("Height must be greater than zero.");
}

// BMI calculation
$height_m = $height_cm / 100;
$bmi = $weight / ($height_m * $height_m);

// 2. Insert into health_data
$stmt = $health_conn->prepare("
    INSERT INTO health_data (
        age, gender, height, weight, bmi,
        systolicbp, diastolicbp, cholesterol_level
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssdddddd", $age, $gender, $height_m, $weight, $bmi, $systolic, $diastolic, $cholesterol);

// 3. Execute and redirect
if ($stmt->execute()) {
    header("Location: usersotherinfo.php?success=1");
    exit();
} else {
    header("Location: usersotherinfo.php?error=2");
    exit();
}

// Cleanup
$stmt->close();
$health_conn->close();
?>
