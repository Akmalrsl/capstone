<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

session_start();

//clear all session variables
$_SESSION = [];

session_destroy();

//redirect to login page
header("Location: login.php");
exit;
