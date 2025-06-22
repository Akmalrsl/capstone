<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

//connect to online databases (healthmate + capstone_sensors)
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

//fetch latest ECG average from sensor_data table
$result = $sensor_conn->query("SELECT AVG(ecg) AS avg_ecg FROM sensor_data WHERE ecg IS NOT NULL AND created_at >= NOW() - INTERVAL 10 MINUTE");
$row = $result->fetch_assoc();
$avg_ecg = isset($row['avg_ecg']) ? (float)$row['avg_ecg'] : null;

//store as systolic or diastolic
$systolic = $avg_ecg > 100 ? $avg_ecg : null;
$diastolic = $avg_ecg <= 100 ? $avg_ecg : null;

//collect and sanitize form input
$age = $_POST['age'];
$gender = $_POST['gender'];
$height = $_POST['height'] / 100; //convert cm to meters
$weight = $_POST['weight'];
$cholesterol = $_POST['cholesterol'];

if ($height <= 0) {
    die("Height must be greater than zero.");
}

//calculate BMI
$bmi = $weight / ($height * $height);

//prepare SQL insert
$sql = "INSERT INTO health_data (age, gender, height, weight, bmi, systolicbp, diastolicbp, cholesterol_level)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $health_conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssdddddd", $age, $gender, $height, $weight, $bmi, $systolic, $diastolic, $cholesterol);
    
    if ($stmt->execute()) {
        header("Location: prediction_result.php");
        exit();
    } else {
        echo "Error executing statement: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error preparing statement: " . $health_conn->error;
}

$health_conn->close();
$sensor_conn->close();
?>
