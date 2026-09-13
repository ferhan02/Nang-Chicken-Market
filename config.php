<?php
$db_name = "mysql:host=localhost;dbname=chicken;charset=utf8mb4";
$username = "root";
$password = "";

try {
    $conn = new PDO($db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
