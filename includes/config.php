<?php
$host = "sql208.infinityfree.com";
$user = "if0_42540363";
$pass = "lQRxFGVbB09Uejf";
$db = "if0_42540363_just9";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
?>
