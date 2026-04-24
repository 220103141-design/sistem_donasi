<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa menghapus permanen
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak. Hanya Pimpinan dan Kepala LKSA yang berhak menghapus permanen.");
}

$id_kotak_amal = $_GET['id'] ?? '';
$jabatan_session = $_SESSION['jabatan'] ?? '';
$id_lksa_session = $_SESSION['id_lksa'] ?? '';

if ($id_kotak_amal) {
    // 1. Cek apakah kotak amal benar-benar sudah diarsipkan
    $sql_check = "SELECT Id_lksa, Foto FROM KotakAmal WHERE ID_KotakAmal = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $id_kotak_amal);
    $stmt_check->execute();
    $ka_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$ka_data) {
        die("Kotak Amal tidak ditemukan.");
    }

    $id_lksa_to_delete = $ka_data['Id_lksa'];
    $foto_ka = $ka_data['Foto'];
    
    // --- LOGIKA OTORISASI LKSA ---
    $is_pimpinan_pusat = ($jabatan_session == 'Pimpinan' && $id_lksa_session == 'Pimpinan_Pusat');
    $is_same_lksa = ($id_lksa_session == $id_lksa_to_delete);

    if (!$is_pimpinan_pusat && !$is_same_lksa) {
        die("Akses ditolak. Anda hanya dapat menghapus Kotak Amal dari LKSA yang sama.");
    }
    // --- END LOGIKA OTORISASI LKSA ---

    // 2. Hapus file foto terkait (jika ada)
    if ($foto_ka) {
        $file_path = __DIR__ . "/../assets/img/" . $foto_ka;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // 3. Hapus Permanen data Kotak Amal
    $sql = "DELETE FROM KotakAmal WHERE ID_KotakAmal = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_kotak_amal);
    
    if ($stmt->execute()) {
        header("Location: arsip_kotak_amal.php"); // Redirect kembali ke halaman arsip
    } else {
        die("Error saat menghapus Kotak Amal: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: arsip_kotak_amal.php");
}

$conn->close();
exit;
?>