<?php
$host   = getenv('DB_HOST') ?: 'db';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: 'root';
$dbname = getenv('DB_NAME') ?: 'lume';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>
