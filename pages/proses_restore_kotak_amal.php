<?php
session_start();
include '../config/database.php';

// Authorization check: Pimpinan, Kepala LKSA, Petugas Kotak Amal
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kotak_amal = $_GET['id'] ?? '';

if ($id_kotak_amal) {
    // Restore: Mengubah Status menjadi 'Active'
    $sql = "UPDATE KotakAmal SET Status = 'Active' WHERE ID_KotakAmal = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_kotak_amal);
    
    if ($stmt->execute()) {
        header("Location: arsip_kotak_amal.php"); // Redirect kembali ke halaman arsip
    } else {
        die("Error saat mengembalikan Kotak Amal: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: arsip_kotak_amal.php");
}

$conn->close();
exit;
?>