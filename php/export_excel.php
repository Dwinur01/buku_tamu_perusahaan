<?php
// Include file konfigurasi database
include 'config.php';

// Atur header untuk file Excel
$filename = "data_tamu_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Mengambil data yang lebih lengkap dari database
$sql = "SELECT 
            t.id as ID,
            t.name as Nama, 
            t.instansi as Instansi,
            t.email as Email,
            t.no_telepon as 'No. Telepon',
            t.kategori as Kategori,
            t.tujuan as Keperluan,
            t.status as Status,
            t.date_submitted as 'Waktu Daftar',
            j.bertemu_dengan as 'Bertemu Dengan',
            j.waktu_mulai as 'Jadwal Mulai',
            j.waktu_selesai as 'Jadwal Selesai'
        FROM tamu t
        LEFT JOIN jadwal j ON t.id = j.tamu_id
        ORDER BY t.date_submitted DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Tulis header kolom
    $header = array_keys($result->fetch_assoc());
    echo implode("\t", $header) . "\n";
    
    // Tulis baris data
    mysqli_data_seek($result, 0); // Kembali ke baris pertama
    while ($row = $result->fetch_assoc()) {
        // Membersihkan data dari karakter yang bisa merusak format
        array_walk($row, function(&$value) {
            $value = preg_replace("/\t/", "\\t", $value);
            $value = preg_replace("/\r?\n/", "\\n", $value);
        });
        echo implode("\t", array_values($row)) . "\n";
    }
} else {
    echo "Tidak ada data untuk diekspor.";
}

$conn->close();
exit();
?>