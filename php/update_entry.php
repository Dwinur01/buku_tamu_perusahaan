<?php
include 'config.php';
header('Content-Type: application/json');

// --- Ambil semua data dari form ---
$id = $_POST['guest_id'];

// Data untuk tabel 'tamu'
$name = $_POST['name'];
$instansi = $_POST['instansi'];
$email = $_POST['email'];
$no_telepon = $_POST['no_telepon'];

// BARU: Logika untuk menentukan nilai kategori
$kategori = $_POST['kategori'];
if ($kategori === 'Lainnya' && !empty($_POST['kategori_lainnya'])) {
    // Jika "Lainnya" dipilih dan input teks diisi, gunakan nilainya
    $kategori = htmlspecialchars(trim($_POST['kategori_lainnya'])); 
}

// Data untuk tabel 'jadwal'
$keperluan = $_POST['keperluan'];
$bertemu_dengan = $_POST['bertemu_dengan'];
$waktu_mulai = !empty($_POST['waktu_mulai']) ? $_POST['waktu_mulai'] : null;
$waktu_selesai = !empty($_POST['waktu_selesai']) ? $_POST['waktu_selesai'] : null;

// Mulai transaction
$conn->begin_transaction();

try {
    // Langkah 1: Update data di tabel 'tamu'
    // Kolom 'tujuan' dan 'kategori' juga diupdate agar tetap sinkron
    $stmt1 = $conn->prepare("UPDATE tamu SET name = ?, instansi = ?, email = ?, no_telepon = ?, tujuan = ?, kategori = ? WHERE id = ?");
    $stmt1->bind_param("ssssssi", $name, $instansi, $email, $no_telepon, $keperluan, $kategori, $id);
    $stmt1->execute();
    $stmt1->close();

    // Langkah 2: Update data di tabel 'jadwal'
    $stmt2 = $conn->prepare("UPDATE jadwal SET keperluan = ?, bertemu_dengan = ?, waktu_mulai = ?, waktu_selesai = ? WHERE tamu_id = ?");
    $stmt2->bind_param("ssssi", $keperluan, $bertemu_dengan, $waktu_mulai, $waktu_selesai, $id);
    $stmt2->execute();
    $stmt2->close();
    
    // Jika semua berhasil, commit perubahan
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Data dan jadwal berhasil diperbarui.']);

} catch (Exception $e) {
    // Jika ada error, batalkan semua perubahan
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()]);
}

$conn->close();
?>