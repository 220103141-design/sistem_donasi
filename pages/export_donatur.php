<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
$jabatan = $_SESSION['jabatan'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'] ?? '';

// Query untuk mengambil data donatur
$sql = "SELECT d.ID_donatur, d.Nama_Donatur, d.NO_WA, d.Email, d.Alamat_Lengkap, d.Status, u.Nama_User AS Dibuat_Oleh, d.ID_LKSA
        FROM Donatur d 
        JOIN User u ON d.ID_user = u.Id_user";
        
if ($jabatan != 'Pimpinan') {
    $sql .= " WHERE d.ID_LKSA = '$id_lksa'";
}
$result = $conn->query($sql);

if (!$result) {
    die("Error dalam query: " . $conn->error);
}

// 1. Set headers untuk download file CSV
$filename = "Data_Donatur_Registrasi_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Buka output stream
$output = fopen('php://output', 'w');

// 3. Tulis header kolom
$headers = [
    'ID Donatur', 
    'Nama Donatur', 
    'Nomor WA', 
    'Email', 
    'Alamat Lengkap', 
    'Status Donasi', 
    'Dibuat Oleh',
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