<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// Database credentials
$servername = "localhost";
$username = "Capstone";
$password = "Capstone123";
$dbname = "healthmate";

// Get JSON POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["ppgAverage"]) || !isset($data["ecgAverage"])) {
    http_response_code(400);
    echo "Invalid input";
    exit;
}

$ppgAverage = intval($data["ppgAverage"]);
$ecgAverage = intval($data["ecgAverage"]);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo "Database connection failed: " . $conn->connect_error;
    exit;
}

// Create table if not exists (optional, for first-time setup)
$conn->query("CREATE TABLE IF NOT EXISTS readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppgAverage INT NOT NULL,
    ecgAverage INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert averages into table
$stmt = $conn->prepare("INSERT INTO averages (ppgAverage, ecgAverage) VALUES (?, ?)");
$stmt->bind_param("ii", $ppgAverage, $ecgAverage);

if ($stmt->execute()) {
    echo "Averages saved to database.";
} else {
    http_response_code(500);
    echo "Database insert failed.";
}

$stmt->close();
$conn->close();
?>
