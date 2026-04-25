<?php
session_start();
include '../config/database.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_kotak_amal = $_POST['id_kotak_amal'] ?? '';
    $jadwal_baru = $_POST['jadwal_baru'] ?? '';

    if (empty($id_kotak_amal) || empty($jadwal_baru)) {
        die("Data tidak lengkap.");
    }

    // Kueri SQL untuk memperbarui Jadwal_Pengambilan
    $sql = "UPDATE KotakAmal SET Jadwal_Pengambilan = ? WHERE ID_KotakAmal = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }

    $stmt->bind_param("ss", $jadwal_baru, $id_kotak_amal);

    if ($stmt->execute()) {
        $stmt->close();
        // Redirect kembali ke halaman utama Pengambilan Kotak Amal dengan status sukses
        header("Location: dana-kotak-amal.php?status=jadwal_success");
        exit;
    } else {
        die("Error saat memperbarui jadwal: " . $stmt->error);
    }
} else {
    header("Location: dana-kotak-amal.php");
    exit;
}

$conn->close();
?>