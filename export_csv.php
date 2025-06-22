<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

//use online DB connection 
include 'db_connect.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="health_data.csv"');

$output = fopen('php://output', 'w');

//CSV header row
fputcsv($output, ['Age', 'Gender', 'Height', 'Weight', 'BMI', 'Systolic BP', 'Diastolic BP', 'Cholesterol Level']);

//fetch data
$sql = "SELECT age, gender, height, weight, bmi, systolicbp, diastolicbp, cholesterol_level FROM health_data";
$result = $conn->query($sql);

//write rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit;
?>
