<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak disediakan.']);
    exit;
}

$id = $_POST['id'];

// Hanya update status di tabel tamu menjadi 'Dikonfirmasi'.
// Proses pembuatan jadwal dipindahkan ke halaman edit.
$stmt = $conn->prepare("UPDATE tamu SET status = 'Dikonfirmasi' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Tamu berhasil dikonfirmasi. Silakan edit untuk mengatur jadwal.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengonfirmasi tamu: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>