<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
$jabatan = $_SESSION['jabatan'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'] ?? '';

// --- NEW: Ambil parameter filter dari GET request ---
$filter_mode_hist = $_GET['filter_mode_hist'] ?? ''; 
$hist_month = $_GET['hist_month'] ?? '';
$hist_year = $_GET['hist_year'] ?? '';
// --- END NEW PARAMETER ---

// Query untuk mengambil data Pengambilan Dana Kotak Amal
$sql = "SELECT dka.ID_Kwitansi_KA, dka.Tgl_Ambil, ka.Nama_Toko, dka.JmlUang, u.Nama_User AS Petugas_Pengambil, dka.ID_KotakAmal, dka.Id_lksa
        FROM Dana_KotakAmal dka
        LEFT JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
        LEFT JOIN User u ON dka.Id_user = u.Id_user";
        
$params = [];
$types = "";
$where_conditions = [];

// 1. Filter LKSA
if ($jabatan != 'Pimpinan') {
    $where_conditions[] = " dka.Id_lksa = ?";
    $params[] = $id_lksa;
    $types .= "s";
}

// 2. Filter Waktu (Menggunakan parameter dari Riwayat)
if ($filter_mode_hist == 'month' && !empty($hist_month) && !empty($hist_year)) {
    $where_conditions[] = " MONTH(dka.Tgl_Ambil) = ? AND YEAR(dka.Tgl_Ambil) = ?";
    $params[] = $hist_month;
    $params[] = $hist_year;
    $types .= "ss";
} elseif ($filter_mode_hist == 'year' && !empty($hist_year)) {
    $where_conditions[] = " YEAR(dka.Tgl_Ambil) = ?";
    $params[] = $hist_year;
    $types .= "s";
}
// Jika filter_mode_hist == 'all', tidak ada klausa WHERE tambahan untuk waktu.

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY dka.Tgl_Ambil DESC";

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error dalam query: " . $conn->error);
}

// 1. Set headers untuk download file CSV
$filename = "Data_Pengambilan_Dana_KotakAmal_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Buka output stream
$output = fopen('php://output', 'w');

// 3. Tulis header kolom
$headers = [
    'ID Kwitansi KA', 
    'Tanggal Ambil', 
    'Nama Toko', 
    'Jumlah Uang (Rp)', 
    'Petugas Pengambil', 
    'ID Kotak Amal',
    'ID LKSA'
];
fputcsv($output, $headers, ';');

// INISIALISASI TOTAL
$total_jml_uang = 0;

// 4. Tulis data baris dan akumulasi total
while ($row = $result->fetch_assoc()) {
    $jml_uang = $row['JmlUang'] ?? 0;
    
    // AKUMULASI TOTAL
    $total_jml_uang += $jml_uang;
    
    fputcsv($output, $row, ';');
}

// 5. Tulis baris total (SUM per kolom)
$total_row = [
    'TOTAL', 
    '', 
    '', 
    $total_jml_uang, 
    '', 
    '', 
    ''
];
fputcsv($output, $total_row, ';');

// 6. Tutup stream dan keluar
fclose($output);
$conn->close();
exit;
?>