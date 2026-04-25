<?php
session_start();
include '../config/database.php';

// Otorisasi: Hanya Pimpinan dan Kepala LKSA
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak. Hanya Atasan yang berhak membatalkan Surat Tugas.");
}

$id_surat_tugas = $_GET['id_tugas'] ?? '';

if ($id_surat_tugas) {
    // Update Status Tugas menjadi 'Batal'
    $sql = "UPDATE SuratTugas SET Status_Tugas = 'Batal' WHERE ID_Surat_Tugas = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_surat_tugas);
    
    if ($stmt->execute()) {
        header("Location: dana-kotak-amal.php?status=tugas_dibatalkan");
    } else {
        die("Error saat membatalkan Surat Tugas: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: dana-kotak-amal.php");
}

$conn->close();
exit;
?>