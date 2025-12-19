<?php
// config.php - Database configuration for your existing smart_city database
$host = 'localhost';
$dbname = 'smart_city';  // Your existing database name
$username = 'root';      // Change to your MySQL username
$password = 3674;          // Change to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>