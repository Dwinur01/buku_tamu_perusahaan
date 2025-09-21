<?php
include 'config.php';

// Atur header JSON
header('Content-Type: application/json');

$data = [];

// 1. Data untuk Stat Cards
$data['tamu_hari_ini'] = $conn->query("SELECT COUNT(id) as total FROM tamu WHERE DATE(date_submitted) = CURDATE()")->fetch_assoc()['total'];
$data['tamu_minggu_ini'] = $conn->query("SELECT COUNT(id) as total FROM tamu WHERE YEARWEEK(date_submitted, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['total'];
$data['sedang_berkunjung'] = $conn->query("SELECT COUNT(id) as total FROM jadwal WHERE NOW() BETWEEN waktu_mulai AND waktu_selesai")->fetch_assoc()['total'];
$data['kunjungan_selesai_hari_ini'] = $conn->query("SELECT COUNT(id) as total FROM jadwal WHERE DATE(waktu_selesai) = CURDATE() AND NOW() > waktu_selesai")->fetch_assoc()['total'];

// 2. Data untuk Grafik Kunjungan
$grafik = [];
// Harian (7 hari terakhir)
$harian = $conn->query("SELECT DATE_FORMAT(date_submitted, '%Y-%m-%d') as tanggal, COUNT(id) as jumlah FROM tamu WHERE date_submitted >= CURDATE() - INTERVAL 6 DAY GROUP BY tanggal ORDER BY tanggal ASC");
$grafik['harian'] = ['labels' => [], 'values' => []];
$temp_harian = [];
while($row = $harian->fetch_assoc()) {
    $temp_harian[$row['tanggal']] = $row['jumlah'];
}
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $grafik['harian']['labels'][] = date('D', strtotime($tanggal));
    $grafik['harian']['values'][] = isset($temp_harian[$tanggal]) ? (int)$temp_harian[$tanggal] : 0;
}
// Mingguan & Bulanan
// (Kode mingguan dan bulanan tidak berubah, jadi disingkat untuk keringkasan)
$mingguan = $conn->query("SELECT YEARWEEK(date_submitted, 1) as minggu, COUNT(id) as jumlah FROM tamu WHERE date_submitted >= CURDATE() - INTERVAL 4 WEEK GROUP BY minggu ORDER BY minggu ASC");
$grafik['mingguan'] = ['labels' => [], 'values' => []];
while($row = $mingguan->fetch_assoc()){
    $grafik['mingguan']['labels'][] = "Minggu " . substr($row['minggu'], 4);
    $grafik['mingguan']['values'][] = (int)$row['jumlah'];
}
$bulanan = $conn->query("SELECT DATE_FORMAT(date_submitted, '%b %Y') as bulan, COUNT(id) as jumlah FROM tamu WHERE date_submitted >= CURDATE() - INTERVAL 5 MONTH GROUP BY bulan ORDER BY MIN(date_submitted) ASC");
$grafik['bulanan'] = ['labels' => [], 'values' => []];
while($row = $bulanan->fetch_assoc()){
    $grafik['bulanan']['labels'][] = $row['bulan'];
    $grafik['bulanan']['values'][] = (int)$row['jumlah'];
}
$data['grafik_kunjungan'] = $grafik;


// 3. Data untuk Chart Kategori Tamu
$kategori = $conn->query("SELECT kategori, COUNT(id) as jumlah FROM tamu GROUP BY kategori");
$data['kategori_tamu'] = ['labels' => [], 'values' => []];
while($row = $kategori->fetch_assoc()){
    $data['kategori_tamu']['labels'][] = $row['kategori'];
    $data['kategori_tamu']['values'][] = (int)$row['jumlah'];
}

// 4. Data untuk Daftar Tamu Terbaru
$tamu_terbaru = [];
$result = $conn->query("SELECT name, instansi, tujuan, DATE_FORMAT(waktu_mulai, '%H:%i') as waktu FROM jadwal JOIN tamu ON jadwal.tamu_id = tamu.id ORDER BY waktu_mulai DESC LIMIT 5");
while($row = $result->fetch_assoc()){
    $tamu_terbaru[] = $row;
}
$data['tamu_terbaru'] = $tamu_terbaru;

// 5. [FIX] Data untuk Notifikasi Real-time dengan Logika Waktu yang Benar
$notifikasi = [];
// Mengambil 'date_submitted' untuk dihitung di PHP
$result = $conn->query("SELECT name, instansi, status, date_submitted FROM tamu ORDER BY date_submitted DESC LIMIT 3");

while($row = $result->fetch_assoc()){
    $now = new DateTime("now", new DateTimeZone('Asia/Jakarta'));
    $then = new DateTime($row['date_submitted'], new DateTimeZone('Asia/Jakarta'));
    $diff = $now->diff($then);

    $waktu_lalu = '';
    if ($diff->y > 0) {
        $waktu_lalu = $diff->y . ' tahun lalu';
    } elseif ($diff->m > 0) {
        $waktu_lalu = $diff->m . ' bulan lalu';
    } elseif ($diff->d > 0) {
        $waktu_lalu = $diff->d . ' hari lalu';
    } elseif ($diff->h > 0) {
        $waktu_lalu = $diff->h . ' jam lalu';
    } elseif ($diff->i > 0) {
        $waktu_lalu = $diff->i . ' menit lalu';
    } else {
        $waktu_lalu = 'Baru saja';
    }
    
    // Menambahkan field baru 'waktu_lalu' ke array
    $row['waktu_lalu'] = $waktu_lalu;
    unset($row['date_submitted']); // Hapus timestamp asli
    $notifikasi[] = $row;
}
$data['notifikasi'] = $notifikasi;

// Mengirimkan semua data sebagai satu JSON
echo json_encode($data);

$conn->close();
?>