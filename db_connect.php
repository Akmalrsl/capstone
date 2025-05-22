<?php
$servername = "capstonespring2025.duckdns.org";
$username = "Capstone";          
$password = "Capstone123"; 
$port = 3306;

// Connect to healthmate database
$health_conn = new mysqli($servername, $username, $password, "healthmate", $port);
if ($health_conn->connect_error) {
    die("Connection to healthmate failed: " . $health_conn->connect_error);
}

// ✅ Alias for compatibility with login.php
$conn = $health_conn;

// Connect to capstone_sensors database
$sensor_conn = new mysqli($servername, $username, $password, "capstone_sensors", $port);
if ($sensor_conn->connect_error) {
    die("Connection to capstone_sensors failed: " . $sensor_conn->connect_error);
}
?>
