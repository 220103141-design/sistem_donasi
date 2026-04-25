<?php
session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form (menggunakan nama field baru)
    $id_pelapor = $_POST['id_pelapor'] ?? '';
    $pelapor_type = $_POST['pelapor_type'] ?? '';
    $id_lksa = $_POST['id_lksa'] ?? '';
    $subjek = $_POST['subjek'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    $tgl_lapor = date('Y-m-d H:i:s'); 

    // Validasi dasar
    if (empty($id_pelapor) || empty($pelapor_type) || empty($subjek) || empty($pesan)) {
        die("Data laporan tidak lengkap.");
    }

    // Membuat ID Laporan
    $tgl_id = date('ymd');
    $counter_sql = "SELECT COUNT(*) AS total FROM Laporan WHERE ID_Laporan LIKE 'LPR_{$tgl_id}_%'";
    $result = $conn->query($counter_sql);
    $row = $result->fetch_assoc();
    $counter = $row['total'] + 1;
    $id_laporan = "LPR_" . $tgl_id . "_" . str_pad($counter, 3, '0', STR_PAD_LEFT);

    // Kueri SQL untuk memasukkan data laporan (dengan Pelapor_Type)
    $sql = "INSERT INTO Laporan (ID_Laporan, ID_user_pelapor, Pelapor_Type, ID_LKSA, Subjek, Pesan, Tgl_Lapor) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }

    // Binding parameters (menambahkan Pelapor_Type)
    $stmt->bind_param("sssssss", $id_laporan, $id_pelapor, $pelapor_type, $id_lksa, $subjek, $pesan, $tgl_lapor);

    if ($stmt->execute()) {
        // Redirect kembali ke halaman laporan dengan status sukses
        $redirect_to = "tambah_laporan.php?status=success";
        header("Location: " . $redirect_to);
        exit;
    } else {
        die("Error saat menyimpan laporan: " . $stmt->error);
    }
} else {
    // Jika diakses tanpa POST, redirect ke form
    header("Location: tambah_laporan.php");
    exit;
}

$conn->close();
?>