<?php
include 'config.php';

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM tamu WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data.']);
}

$stmt->close();
$conn->close();
?>