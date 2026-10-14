<?php
session_start();

$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "banksampah_alfin";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function wajib_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: index.php");
        exit;
    }
}

function role($role) {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === $role;
}
?>