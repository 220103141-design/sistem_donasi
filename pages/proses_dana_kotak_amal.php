<?php
session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $id_user = $_POST['id_user'] ?? '';
    $id_kotak_amal = $_POST['id_kotak_amal'] ?? '';
    $jumlah_uang = $_POST['jumlah_uang'] ?? 0;
    $tgl_ambil = date('Y-m-d');
    
    // Ambil Id_lksa dari tabel KotakAmal berdasarkan ID_KotakAmal
    // Ini adalah solusi paling andal karena Id_lksa dari KotakAmal harus valid
    $sql_lksa = "SELECT Id_lksa FROM KotakAmal WHERE ID_KotakAmal = ?";
    $stmt_lksa = $conn->prepare($sql_lksa);
    if ($stmt_lksa === false) {
        die("Error saat menyiapkan kueri Id_lksa: " . $conn->error);
    }
    $stmt_lksa->bind_param("s", $id_kotak_amal);
    $stmt_lksa->execute();
    $result_lksa = $stmt_lksa->get_result();
    $id_lksa = $result_lksa->fetch_assoc()['Id_lksa'] ?? '';
    $stmt_lksa->close();

    // Generate ID Kwitansi
    $tgl_id = date('ymd');
    $counter_sql = "SELECT COUNT(*) AS total FROM Dana_KotakAmal WHERE ID_Kwitansi_KA LIKE 'KWKA_LKSA_NH_{$tgl_id}_%'";
    $result = $conn->query($counter_sql);
    $row = $result->fetch_assoc();
    $counter = $row['total'] + 1;
    $id_kwitansi = "KWKA_LKSA_NH_" . $tgl_id . "_" . str_pad($counter, 3, '0', STR_PAD_LEFT);

    // Kueri SQL untuk memasukkan data pengambilan
    $sql = "INSERT INTO Dana_KotakAmal (ID_Kwitansi_KA, Id_lksa, ID_KotakAmal, Id_user, Tgl_Ambil, JmlUang) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Periksa jika prepare berhasil
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }

    $stmt->bind_param("sssssi", $id_kwitansi, $id_lksa, $id_kotak_amal, $id_user, $tgl_ambil, $jumlah_uang);

    if ($stmt->execute()) {
        header("Location: dana-kotak-amal.php");
        exit;
    } else {
        die("Error saat menyimpan data pengambilan: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: dashboard.php");
    exit;
}

$conn->close();
?>