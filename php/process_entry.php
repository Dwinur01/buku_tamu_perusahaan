<?php
// Include file konfigurasi database
include 'config.php';

// Atur header untuk respons JSON
header('Content-Type: application/json');

// --- Ambil semua data dari form ---
// Data untuk tabel 'tamu'
$name = $_POST['name']; 
$instansi = $_POST['instansi'];
$email = $_POST['email'];
$no_telepon = $_POST['no_telepon'];
$date = date("Y-m-d H:i:s");

//untuk menentukan nilai kategori
$kategori = $_POST['kategori'];
if ($kategori === 'Lainnya' && !empty($_POST['kategori_lainnya'])) {
    // Jika "Lainnya" dipilih dan input teks diisi, gunakan nilai dari input teks
    $kategori = htmlspecialchars(trim($_POST['kategori_lainnya'])); 
}

// Data untuk tabel 'jadwal'
$keperluan = $_POST['tujuan']; 
$bertemu_dengan = $_POST['bertemu_dengan'];
$waktu_mulai = $_POST['waktu_mulai'];
$waktu_selesai = $_POST['waktu_selesai'];

// Mulai database transaction
$conn->begin_transaction();

try {
    // --- Proses Upload Proposal PDF (jika ada) ---
    $proposal_path = null;
    if (isset($_FILES['proposal_pdf']) && $_FILES['proposal_pdf']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_info = pathinfo($_FILES['proposal_pdf']['name']);
        if (strtolower($file_info['extension']) === 'pdf') {
            $file_name = 'proposal-' . uniqid() . '.pdf';
            if (move_uploaded_file($_FILES['proposal_pdf']['tmp_name'], $upload_dir . $file_name)) {
                $proposal_path = $file_name;
            } else {
                throw new Exception('Gagal mengunggah proposal PDF.');
            }
        } else {
            throw new Exception('Hanya file format PDF yang diizinkan untuk proposal.');
        }
    }

    // --- Proses Upload Foto (jika ada) ---
    $photo_names = [];
    if (isset($_FILES['photo']) && !empty($_FILES['photo']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        // ================= PERUBAHAN UTAMA DI SINI =================
        // Variabel $name diubah menjadi $photo_filename untuk menghindari konflik
        foreach ($_FILES['photo']['name'] as $key => $photo_filename) {
            $file_tmp = $_FILES['photo']['tmp_name'][$key];
            // Menggunakan $photo_filename
            $file_ext = strtolower(pathinfo($photo_filename, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                // Menggunakan $photo_filename
                $new_file_name = uniqid() . '-' . basename($photo_filename);
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $photo_names[] = $new_file_name;
                } else {
                    throw new Exception('Gagal mengunggah salah satu file foto.');
                }
            } else {
                throw new Exception('Format file foto tidak valid.');
            }
        }
        // ================= AKHIR DARI PERUBAHAN =================
    }
    $photo_paths = implode(',', $photo_names);

    // --- Langkah 1: Masukkan data ke tabel 'tamu' ---
    // Sekarang, variabel $name di sini berisi nama tamu yang benar
    $sql_tamu = "INSERT INTO tamu (name, instansi, email, no_telepon, proposal_pdf, tujuan, kategori, photos, date_submitted, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Dikonfirmasi')";
    $stmt_tamu = $conn->prepare($sql_tamu);
    $stmt_tamu->bind_param("sssssssss", $name, $instansi, $email, $no_telepon, $proposal_path, $keperluan, $kategori, $photo_paths, $date);
    $stmt_tamu->execute();

    // Ambil ID dari tamu yang baru saja dimasukkan
    $tamu_id = $conn->insert_id;
    if ($tamu_id == 0) {
        throw new Exception('Gagal mendapatkan ID tamu yang baru dibuat.');
    }
    $stmt_tamu->close();


    // --- Langkah 2: Masukkan data jadwal ke tabel 'jadwal' ---
    $sql_jadwal = "INSERT INTO jadwal (tamu_id, keperluan, bertemu_dengan, waktu_mulai, waktu_selesai) VALUES (?, ?, ?, ?, ?)";
    $stmt_jadwal = $conn->prepare($sql_jadwal);
    $stmt_jadwal->bind_param("issss", $tamu_id, $keperluan, $bertemu_dengan, $waktu_mulai, $waktu_selesai);
    $stmt_jadwal->execute();
    $stmt_jadwal->close();

    // Jika semua query berhasil, commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Data tamu dan jadwal berhasil disimpan!']);

} catch (Exception $e) {
    // Jika terjadi error, rollback semua perubahan
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}

// Tutup koneksi
$conn->close();
?>