<?php
include 'config.php';
header('Content-Type: application/json');

// Menyiapkan struktur data final
$data = [
    'akan_datang' => [],
    'sedang_berkunjung' => [],
    'telah_selesai' => []
];

// Mengambil semua jadwal yang relevan untuk HARI INI
$sql = "SELECT 
            j.waktu_mulai, 
            j.waktu_selesai, 
            t.name AS nama_tamu, 
            j.keperluan, 
            j.bertemu_dengan 
        FROM jadwal j
        JOIN tamu t ON j.tamu_id = t.id
        WHERE DATE(j.waktu_mulai) = CURDATE()
        ORDER BY j.waktu_mulai ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $now = new DateTime("now", new DateTimeZone('Asia/Jakarta')); // Waktu saat ini

    while ($row = $result->fetch_assoc()) {
        $waktu_mulai = new DateTime($row['waktu_mulai'], new DateTimeZone('Asia/Jakarta'));
        $waktu_selesai = new DateTime($row['waktu_selesai'], new DateTimeZone('Asia/Jakarta'));

        // Format waktu untuk ditampilkan di frontend
        $row['waktu_formatted'] = $waktu_mulai->format('H:i') . ' - ' . $waktu_selesai->format('H:i');

        // Mengkategorikan jadwal berdasarkan waktu
        if ($now < $waktu_mulai) {
            $data['akan_datang'][] = $row;
        } elseif ($now >= $waktu_mulai && $now <= $waktu_selesai) {
            $data['sedang_berkunjung'][] = $row;
        } else {
            $data['telah_selesai'][] = $row;
        }
    }
}

// Mengirimkan semua data sebagai satu JSON
echo json_encode($data);

$conn->close();
?>