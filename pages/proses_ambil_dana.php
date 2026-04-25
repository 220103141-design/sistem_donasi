<?php
session_start();
include '../config/database.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $id_user = $_POST['id_user'] ?? '';
    $id_lksa = $_POST['id_lksa'] ?? '';
    $id_kotak_amal = $_POST['id_kotak_amal'] ?? '';
    $jumlah_uang = $_POST['jumlah_uang'] ?? 0;
    $tgl_ambil = $_POST['tgl_ambil'] ?? date('Y-m-d');
    $id_surat_tugas = $_POST['id_surat_tugas'] ?? ''; // <-- BARU: Ambil ID Surat Tugas dari form

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
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }

    $stmt->bind_param("sssssi", $id_kwitansi, $id_lksa, $id_kotak_amal, $id_user, $tgl_ambil, $jumlah_uang);

    if ($stmt->execute()) {
        $stmt->close();
        
        // --- BARU: Update status Surat Tugas menjadi Selesai ---
        if (!empty($id_surat_tugas)) {
            $sql_update_st = "UPDATE SuratTugas SET Status_Tugas = 'Selesai' WHERE ID_Surat_Tugas = ?";
            $stmt_update_st = $conn->prepare($sql_update_st);
            if ($stmt_update_st) {
                $stmt_update_st->bind_param("s", $id_surat_tugas);
                $stmt_update_st->execute();
                $stmt_update_st->close();
            }
        }
        // --- END BARU: Update status Surat Tugas ---
        
        // Redirect ke halaman konfirmasi dengan data kwitansi
        header("Location: konfirmasi_pengambilan.php?kwitansi=" . $id_kwitansi);
        exit;
    } else {
        die("Error saat menyimpan data pengambilan: " . $stmt->error);
    }
} else {
    header("Location: dana-kotak-amal.php");
    exit;
}

$conn->close();
?>