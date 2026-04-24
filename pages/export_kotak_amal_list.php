<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
$jabatan = $_SESSION['jabatan'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'] ?? '';

// Query untuk mengambil data Kotak Amal
$sql = "SELECT ka.ID_KotakAmal, ka.Nama_Toko, ka.Alamat_Toko, ka.Nama_Pemilik, ka.WA_Pemilik, ka.Email, ka.Jadwal_Pengambilan, ka.Ket, ka.Latitude, ka.Longitude, ka.Id_lksa
        FROM KotakAmal ka";
        
// Logika SQL diperbarui: Jika bukan Pimpinan (yaitu Kepala LKSA), batasi per LKSA.
if ($jabatan != 'Pimpinan') {
    $sql .= " WHERE ka.ID_LKSA = '$id_lksa'";
}

$result = $conn->query($sql);

if (!$result) {
    die("Error dalam query: " . $conn->error);
}

// 1. Set headers untuk download file CSV
$filename = "Data_KotakAmal_Lokasi_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Buka output stream
$output = fopen('php://output', 'w');

// 3. Tulis header kolom
$headers = [
    'ID Kotak Amal', 
    'Nama Toko', 
    'Alamat Toko', 
    'Nama Pemilik', 
    'Nomor WA Pemilik', 
    'Email Pemilik', 
    'Jadwal Pengambilan',
    'Keterangan',
    'Latitude',
    'Longitude',
    'ID LKSA'
];
fputcsv($output, $headers, ';');

// 4. Tulis data baris
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row, ';');
}

// 5. Tutup stream dan keluar
fclose($output);
$conn->close();
exit;
?>