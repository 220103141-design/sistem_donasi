<?php
session_start();
include '../config/database.php';

// Authorization check: Semua yang bisa melihat sumbangan juga bisa menghapusnya (Pimpinan, Kepala LKSA, Pegawai)
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id'] ?? '';

if ($id_kwitansi) {
    // Hapus data sumbangan dari database
    $sql = "DELETE FROM Sumbangan WHERE ID_Kwitansi_ZIS = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_kwitansi);
    
    if ($stmt->execute()) {
        header("Location: sumbangan.php");
    } else {
        die("Error saat menghapus sumbangan: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: sumbangan.php");
}

$conn->close();
exit;
?>