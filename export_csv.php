<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

//Use online DB connection 
include 'db_connect.php';

//Set headers to force download as CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="health_data.csv"');

//Output stream to write CSV
$output = fopen('php://output', 'w');

// CSV header row
fputcsv($output, ['Age', 'Gender', 'Height', 'Weight', 'BMI', 'Systolic BP', 'Diastolic BP', 'Cholesterol Level']);

//Fetch data
$sql = "SELECT age, gender, height, weight, bmi, systolicbp, diastolicbp, cholesterol_level FROM health_data";
$result = $conn->query($sql);

//Write rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit;
?>
