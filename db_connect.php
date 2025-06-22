<?php
$servername = "capstonespring2025.duckdns.org";
$username = "Capstone";          
$password = "Capstone123"; 
$port = 3306;

//connect to healthmate database
$health_conn = new mysqli($servername, $username, $password, "healthmate", $port);
if ($health_conn->connect_error) {
    die("Connection to healthmate failed: " . $health_conn->connect_error);
}

//✅ Alias for compatibility with login.php
$conn = $health_conn;

