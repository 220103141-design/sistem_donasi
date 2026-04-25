<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
$jabatan = $_SESSION['jabatan'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'] ?? '';

// Query untuk mengambil data sumbangan
$sql = "SELECT s.ID_Kwitansi_ZIS, s.Tgl, d.Nama_Donatur, s.Zakat_Profesi, s.Zakat_Maal, s.Infaq, s.Sedekah, s.Fidyah, s.Natura, s.Status_Verifikasi, u.Nama_User AS Petugas_Input, s.Id_lksa
        FROM Sumbangan s 
        LEFT JOIN Donatur d ON s.ID_donatur = d.ID_donatur
        LEFT JOIN User u ON s.ID_user = u.Id_user";
        
if ($jabatan != 'Pimpinan') {
    $sql .= " WHERE s.ID_LKSA = '$id_lksa'";
}
$result = $conn->query($sql);

if (!$result) {
    die("Error dalam query: " . $conn->error);
}

// 1. Set headers untuk download file CSV
$filename = "Data_Sumbangan_ZIS_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Buka output stream
$output = fopen('php://output', 'w');

// 3. Tulis header kolom
$headers = [
    'ID Kwitansi', 
    'Tanggal', 
    'Nama Donatur', 
    'Zakat Profesi (Rp)', 
    'Zakat Maal (Rp)', 
    'Infaq (Rp)', 
    'Sedekah (Rp)', 
    'Fidyah (Rp)', 
    'Total Sumbangan Uang (Rp)', 
    'Natura', 
    'Status Verifikasi',
    'Petugas Input',
    'ID LKSA'
];
fputcsv($output, $headers, ';');

// INISIALISASI TOTAL
$total_profesi = 0;
$total_maal = 0;
$total_infaq = 0;
$total_sedekah = 0;
$total_fidyah = 0;
$total_semua_uang = 0;

// 4. Tulis data baris dan akumulasi total
while ($row = $result->fetch_assoc()) {
    // Pastikan nilai NULL diubah menjadi 0 untuk kolom numerik
    $zakat_profesi = $row['Zakat_Profesi'] ?? 0;
    $zakat_maal = $row['Zakat_Maal'] ?? 0;
    $infaq = $row['Infaq'] ?? 0;
    $sedekah = $row['Sedekah'] ?? 0;
    $fidyah = $row['Fidyah'] ?? 0;
    
    // Hitung Total Sumbangan Uang per baris
    $total_uang = $zakat_profesi + $zakat_maal + $infaq + $sedekah + $fidyah;

    // AKUMULASI TOTAL
    $total_profesi += $zakat_profesi;
    $total_maal += $zakat_maal;
    $total_infaq += $infaq;
    $total_sedekah += $sedekah;
    $total_fidyah += $fidyah;
    $total_semua_uang += $total_uang;

    // Susun data untuk export
    $export_row = [
        $row['ID_Kwitansi_ZIS'],
        $row['Tgl'],
        $row['Nama_Donatur'],
        $zakat_profesi,
        $zakat_maal,
        $infaq,
        $sedekah,
        $fidyah,
        $total_uang, 
        $row['Natura'],
        $row['Status_Verifikasi'],
        $row['Petugas_Input'],
        $row['Id_lksa']
    ];

    fputcsv($output, $export_row, ';');
}

// 5. Tulis baris total (SUM per kolom)
$total_row = [
    'TOTAL', // Placeholder untuk kolom pertama
    '',
    '',
    $total_profesi,
    $total_maal,
    $total_infaq,
    $total_sedekah,
    $total_fidyah,
    $total_semua_uang,
    '',
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