<?php
session_start();
include '../config/database.php';

// Authorization check: Pimpinan, Kepala LKSA, dan Petugas Kotak Amal
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id'] ?? '';

if ($id_kwitansi) {
    // Hapus data pengambilan dana kotak amal dari database
    $sql = "DELETE FROM Dana_KotakAmal WHERE ID_Kwitansi_KA = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_kwitansi);
    
    if ($stmt->execute()) {
        header("Location: dana-kotak-amal.php");
    } else {
        die("Error saat menghapus data pengambilan: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: dana-kotak-amal.php");
}

$conn->close();
exit;
?>