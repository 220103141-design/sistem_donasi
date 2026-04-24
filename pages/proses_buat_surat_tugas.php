<?php
session_start();
include '../config/database.php';

// Otorisasi: Hanya Kepala LKSA atau Pimpinan (Atasan yang membuat tugas)
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak. Hanya Atasan yang berhak membuat Surat Tugas.");
}

$id_kotak_amal = $_GET['id'] ?? '';

if ($id_kotak_amal) {
    $id_user = $_SESSION['id_user']; // ID Atasan (Pembuat Tugas)
    $tgl_mulai = date('Y-m-d H:i:s');
    $status_aktif = 'Aktif';

    // 1. Generate ID Surat Tugas
    $tgl_id = date('ymd');
    $counter_sql = "SELECT COUNT(*) AS total FROM SuratTugas WHERE ID_Surat_Tugas LIKE 'ST_{$tgl_id}_%'";
    $result = $conn->query($counter_sql);
    $row = $result->fetch_assoc();
    $counter = $row['total'] + 1;
    $id_surat_tugas = "ST_" . $tgl_id . "_" . str_pad($counter, 3, '0', STR_PAD_LEFT);

    // 2. Insert ke tabel SuratTugas
    $sql = "INSERT INTO SuratTugas (ID_Surat_Tugas, ID_KotakAmal, ID_user, Tgl_Mulai_Tugas, Status_Tugas) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri Surat Tugas: " . $conn->error . ". Pastikan tabel SuratTugas sudah dibuat.");
    }
    
    $stmt->bind_param("sssss", $id_surat_tugas, $id_kotak_amal, $id_user, $tgl_mulai, $status_aktif);

    if ($stmt->execute()) {
        $stmt->close();
        // 3. Redirect kembali ke daftar Kotak Amal dengan pesan sukses
        header("Location: dana-kotak-amal.php?status=tugas_dibuat");
        exit;
    } else {
        die("Error saat mencatat Surat Tugas: " . $stmt->error);
    }
} else {
    header("Location: dana-kotak-amal.php");
    exit;
}

$conn->close();
?>