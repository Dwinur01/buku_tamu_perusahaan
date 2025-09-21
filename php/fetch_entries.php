<?php
// Aktifkan pelaporan error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

// Atur header sebagai JSON
header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}

// Query untuk mengambil semua data yang dibutuhkan
$sql = "SELECT 
            t.id, t.name, t.instansi, t.tujuan, t.kategori, t.photos, 
            t.date_submitted, t.status as original_status, j.waktu_mulai, j.waktu_selesai
        FROM tamu t
        LEFT JOIN jadwal j ON t.id = j.tamu_id
        ORDER BY t.date_submitted DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query SQL gagal: ' . $conn->error]);
    exit;
}

$entries = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        try {
            $status = $row['original_status'];

            // Logika status dinamis HANYA berlaku jika ada jadwal yang valid
            // dan status awalnya relevan (bukan Menunggu Verifikasi atau Dibatalkan)
            if (($status === 'Dikonfirmasi' || $status === 'Sedang Berkunjung' || $status === 'Terjadwalkan') && !empty($row['waktu_mulai'])) {
                
                $stmt_check = $conn->prepare("
                    SELECT 
                        CASE
                            WHEN NOW() BETWEEN ? AND ? THEN 'Sedang Berkunjung'
                            WHEN NOW() > ? THEN 'Selesai'
                            WHEN DATE(?) = CURDATE() AND NOW() < ? THEN 'Terjadwalkan'
                            ELSE 'Dikonfirmasi'
                        END AS current_status
                ");
                
                $stmt_check->bind_param("sssss", 
                    $row['waktu_mulai'], 
                    $row['waktu_selesai'], 
                    $row['waktu_selesai'],
                    $row['waktu_mulai'], // Untuk pengecekan tanggal hari ini
                    $row['waktu_mulai']  // Untuk pengecekan waktu sebelum mulai
                );

                $stmt_check->execute();
                $status_result = $stmt_check->get_result()->fetch_assoc();
                $status = $status_result['current_status'];
                $stmt_check->close();
            }
            
            $row['status'] = $status;
            unset($row['original_status']);
            $entries[] = $row;
            
        } catch (Exception $e) {
            error_log('Error processing guest ID ' . $row['id'] . ': ' . $e->getMessage());
        }
    }
}

echo json_encode($entries);
$conn->close();
?>