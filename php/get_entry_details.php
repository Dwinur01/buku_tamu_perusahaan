<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak disediakan.']);
    exit;
}

$id = $_GET['id'];

// Mengambil data dari tabel 'tamu' dan menggabungkannya dengan 'jadwal'
// Mengambil semua kolom yang diperlukan untuk form edit
$stmt = $conn->prepare("
    SELECT 
        t.name, t.instansi, t.email, t.no_telepon,
        j.keperluan, j.bertemu_dengan, j.waktu_mulai, j.waktu_selesai 
    FROM tamu t
    LEFT JOIN jadwal j ON t.id = j.tamu_id
    WHERE t.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Data tamu tidak ditemukan.']);
}

$stmt->close();
$conn->close();
?>