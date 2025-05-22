<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

// 1. Get ECG reading from sensor_data by ID
$sensor_id = $_POST['sensor_id'];
$query = $sensor_conn->prepare("SELECT ecg FROM sensor_data WHERE id = ?");
$query->bind_param("i", $sensor_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    // If no ECG found, redirect back with error message (optional)
    header("Location: usersotherinfo.php?error=1");
    exit();
}

$ecg = (float)$result->fetch_assoc()['ecg'];
$systolic = $ecg >= 100 ? $ecg : null;
$diastolic = $ecg < 100 ? $ecg : null;

// 2. Collect other form data
$age = $_POST['age'];
$gender = $_POST['gender'];
$height_cm = (float)$_POST['height'];
$weight = (float)$_POST['weight'];
$cholesterol = (int)$_POST['cholesterol'];

if ($height_cm <= 0) {
    die("Height must be greater than zero.");
}

$height_m = $height_cm / 100;
$bmi = $weight / ($height_m * $height_m);

// 3. Insert into health_data
$stmt = $health_conn->prepare("
    INSERT INTO health_data (age, gender, height, weight, bmi, systolicbp, diastolicbp, cholesterol_level)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssdddddd", $age, $gender, $height_m, $weight, $bmi, $systolic, $diastolic, $cholesterol);

// ✅ Redirect back on success with success flag
if ($stmt->execute()) {
    header("Location: usersotherinfo.php?success=1");
    exit();
} else {
    // Optional: redirect back with error flag
    header("Location: usersotherinfo.php?error=2");
    exit();
}

// Cleanup
if ($stmt) {
    $stmt->close();
}
$query->close();
$sensor_conn->close();
$health_conn->close();
?>
