<?php
session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $id_user = $_POST['id_user'] ?? '';
    $id_lksa = $_POST['id_lksa'] ?? '';
    $id_donatur = $_POST['id_donatur'] ?? '';
    $zakat_profesi = $_POST['zakat_profesi'] ?? 0;
    $zakat_maal = $_POST['zakat_maal'] ?? 0;
    $infaq = $_POST['infaq'] ?? 0;
    $sedekah = $_POST['sedekah'] ?? 0;
    $fidyah = $_POST['fidyah'] ?? 0;
    $natura = $_POST['natura'] ?? '';
    // Metode_Pembayaran dihapus
    $tgl_trans = date('Y-m-d'); 
    $status_verifikasi = 'Belum Terverifikasi'; // PERBAIKAN: Disamakan persis dengan nilai ENUM di database

    // Membuat ID Kwitansi yang unik sesuai format KWZIS_thbltgl_XXX
    $tgl_kwitansi = date('ymd');
    $counter_sql = "SELECT COUNT(*) AS total FROM Sumbangan WHERE ID_Kwitansi_ZIS LIKE 'KWZIS_{$tgl_kwitansi}_%'";
    $result = $conn->query($counter_sql);
    $row = $result->fetch_assoc();
    $counter = $row['total'] + 1;
    $id_kwitansi = "KWZIS_" . $tgl_kwitansi . "_" . str_pad($counter, 3, '0', STR_PAD_LEFT);

    // Kueri SQL untuk memasukkan data sumbangan
    // Kolom Metode_Pembayaran dihapus
    $sql = "INSERT INTO Sumbangan (ID_Kwitansi_ZIS, ID_user, Id_lksa, ID_donatur, Zakat_Profesi, Zakat_Maal, Infaq, Sedekah, Fidyah, Natura, Tgl, Status_Verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Memeriksa apakah prepare berhasil
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }

    // Binding parameters: 12 parameter total
    $stmt->bind_param("ssssssssssss", 
        $id_kwitansi, 
        $id_user, 
        $id_lksa, 
        $id_donatur, 
        $zakat_profesi, 
        $zakat_maal, 
        $infaq, 
        $sedekah, 
        $fidyah, 
        $natura, 
        $tgl_trans,
        $status_verifikasi
    );

    if ($stmt->execute()) {
        header("Location: sumbangan.php");
        exit;
    } else {
        die("Error saat menyimpan sumbangan: " . $stmt->error);
    }
} else {
    header("Location: tambah_sumbangan.php");
    exit;
}

$conn->close();
?>