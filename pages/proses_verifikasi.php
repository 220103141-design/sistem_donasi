<?php
session_start();
include '../config/database.php';

// Verifikasi otorisasi: Hanya Pimpinan dan Kepala LKSA yang bisa akses
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id'] ?? '';

if ($id_kwitansi) {
    // Kueri SQL untuk memperbarui status sumbangan menjadi 'Terverifikasi'
    $sql = "UPDATE Sumbangan SET Status_Verifikasi = 'Terverifikasi' WHERE ID_Kwitansi_ZIS = ?";
    $stmt = $conn->prepare($sql);
    
    // Periksa jika prepare berhasil
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_kwitansi);
    
    if ($stmt->execute()) {
        // Arahkan kembali ke halaman verifikasi dengan pesan sukses (opsional)
        header("Location: verifikasi-donasi.php?status=success");
    } else {
        die("Error saat memverifikasi sumbangan: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    // Jika tidak ada ID, kembalikan ke halaman sebelumnya
    header("Location: verifikasi-donasi.php");
}

$conn->close();
exit;
?>