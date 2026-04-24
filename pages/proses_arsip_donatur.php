<?php
session_start();
include '../config/database.php';

// Authorization check: Pimpinan, Kepala LKSA, Pegawai
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

$id_donatur = $_GET['id'] ?? '';

if ($id_donatur) {
    // Soft Delete (Arsip): Mengubah Status_Data menjadi 'Archived'
    $sql = "UPDATE Donatur SET Status_Data = 'Archived' WHERE ID_donatur = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_donatur);
    
    if ($stmt->execute()) {
        header("Location: donatur.php");
    } else {
        die("Error saat mengarsipkan donatur: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: donatur.php");
}

$conn->close();
exit;
?>