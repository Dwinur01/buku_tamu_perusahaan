<?php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "daftar-tamu";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

mysqli_query($conn, "SET time_zone = '+07:00'");
?>
