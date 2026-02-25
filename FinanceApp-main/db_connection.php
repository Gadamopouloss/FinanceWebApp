<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'account_management';
$port = '3306';

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

?>