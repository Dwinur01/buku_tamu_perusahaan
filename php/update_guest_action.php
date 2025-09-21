<?php
include 'config.php';

header('Content-Type: application/json');

$id = $_POST['id'];
$action = $_POST['action']; // 'konfirmasi' atau 'batalkan'
$newStatus = '';

if ($action === 'konfirmasi') {
    $newStatus = 'Dikonfirmasi';
} elseif ($action === 'batalkan') {
    $newStatus = 'Dibatalkan';
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    exit;
}

$conn->begin_transaction();

try {
    // Update status di tabel tamu
    $stmt1 = $conn->prepare("UPDATE tamu SET status = ? WHERE id = ?");
    $stmt1->bind_param("si", $newStatus, $id);
    $stmt1->execute();

    // Jika aksinya adalah konfirmasi, tambahkan juga ke jadwal
    if ($action === 'konfirmasi') {
        $stmt2 = $conn->prepare("SELECT name, tujuan FROM tamu WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $tamu = $result->fetch_assoc();

        $waktu = "Akan Datang";
        $nama_tamu = $tamu['name'];
        $keperluan = $tamu['tujuan'] ? $tamu['tujuan'] : 'Belum ditentukan';
        $bertemu_dengan = 'Resepsionis';

        $stmt3 = $conn->prepare("INSERT INTO jadwal (waktu, nama_tamu, keperluan, bertemu_dengan) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("ssss", $waktu, $nama_tamu, $keperluan, $bertemu_dengan);
        $stmt3->execute();
        $stmt3->close();
        $stmt2->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Status tamu berhasil diperbarui.']);

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status: ' . $exception->getMessage()]);
}

$stmt1->close();
$conn->close();
?>