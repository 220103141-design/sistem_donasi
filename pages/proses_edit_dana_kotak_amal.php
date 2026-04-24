<?php
session_start();
include '../config/database.php';

// Authorization check: Pimpinan, Kepala LKSA, dan Petugas Kotak Amal
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_kwitansi = $_POST['id_kwitansi'] ?? '';
    $jumlah_uang = $_POST['jumlah_uang'] ?? 0;

    $sql = "UPDATE Dana_KotakAmal SET JmlUang = ? WHERE ID_Kwitansi_KA = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }

    $stmt->bind_param("is", $jumlah_uang, $id_kwitansi);

    if ($stmt->execute()) {
        header("Location: dana-kotak-amal.php");
        exit;
    } else {
        die("Error saat memperbarui pengambilan dana kotak amal: " . $stmt->error);
    }
} else {
    header("Location: dana-kotak-amal.php");
    exit;
}

$conn->close();
?>