<?php
session_start();
include '../config/database.php';

// Authorization check: Pimpinan, Kepala LKSA, Pegawai
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

$id_donatur = $_GET['id'] ?? '';

if ($id_donatur) {
    // Restore: Mengubah Status_Data menjadi 'Active'
    $sql = "UPDATE Donatur SET Status_Data = 'Active' WHERE ID_donatur = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_donatur);
    
    if ($stmt->execute()) {
        header("Location: arsip_donatur.php"); // Redirect kembali ke halaman arsip
    } else {
        die("Error saat mengembalikan donatur: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: arsip_donatur.php");
}

$conn->close();
exit;
?>