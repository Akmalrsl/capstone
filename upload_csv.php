<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== false) {
        fgetcsv($handle);

        $successCount = 0;
        $failCount = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($data) < 8) continue; //skip invalid rows

            $age = (int)$data[1];
            $gender = strtolower(trim($data[2]));
            $height_cm = (float)$data[3];
            $weight = (float)$data[4];
            $systolic = (int)$data[5];
            $diastolic = (int)$data[6];
            $cholesterol = (int)$data[7];

            $height_m = $height_cm / 100;
            $bmi = ($height_m > 0) ? round($weight / ($height_m * $height_m), 4) : 0;

            $stmt = $conn->prepare("INSERT INTO health_data 
                (age, gender, height, weight, bmi, systolicbp, diastolicbp, cholesterol_level, date_created)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            if ($stmt) {
                $stmt->bind_param("isdddddd", $age, $gender, $height_m, $weight, $bmi, $systolic, $diastolic, $cholesterol);
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    $failCount++;
                }
                $stmt->close();
            } else {
                $failCount++;
            }
        }

        fclose($handle);
        echo "<script>
            alert('✅ Upload complete: $successCount rows added, $failCount failed.');
            window.location.href='manualTest.php';
        </script>";
    } else {
        echo "<script>alert('❌ Unable to open the file.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('❌ No file uploaded.'); window.history.back();</script>";
}

$conn->close();
?>
