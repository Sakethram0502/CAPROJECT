<?php
// Simple MySQLi connection file for CA Project Management System

$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Adjust if your MySQL root user has a password
$db_name = 'caproject';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
}

// Use UTF-8 for safety
$conn->set_charset('utf8mb4');

