<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

//connect to online database
$servername = "capstonespring2025.duckdns.org";  
$username = "Capstone";                          
$password = "Capstone123";                       
$database = "healthmate";                        
$port = 3306;

$conn = new mysqli($servername, $username, $password, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//admin account credentials
$new_username = "admin";
$email = "admin@gmail.com";
$plain_password = "admin123";
$hash_password = password_hash($plain_password, PASSWORD_DEFAULT);
$role = "admin";

//check if admin already exists
$check = $conn->query("SELECT * FROM users WHERE email = '$email'");
if ($check && $check->num_rows > 0) {
    echo "⚠️ Admin already exists.";
} else {
    //insert new admin
    $sql = "INSERT INTO users (username, email, password, role)
            VALUES ('$new_username', '$email', '$hash_password', '$role')";

    if ($conn->query($sql)) {
        echo "✅ Admin account created!<br>";
        echo "📧 Email: <strong>$email</strong><br>";
        echo "🔒 Password: <strong>$plain_password</strong>";
    } else {
        echo "❌ Error: " . $conn->error;
    }
}

$conn->close();
?>
