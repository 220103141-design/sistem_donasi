<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak. Hanya Pimpinan dan Kepala LKSA yang berhak mengarsipkan pengguna.");
}

$id_user_to_archive = $_GET['id'] ?? '';
$jabatan_session = $_SESSION['jabatan'] ?? '';
$id_lksa_session = $_SESSION['id_lksa'] ?? '';

if ($id_user_to_archive) {
    // 1. Fetch data pengguna yang akan diarsipkan
    $sql_check = "SELECT Jabatan, Id_lksa FROM User WHERE Id_user = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $id_user_to_archive);
    $stmt_check->execute();
    $user_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$user_data) {
        die("Pengguna tidak ditemukan.");
    }

    $jabatan_to_archive = $user_data['Jabatan'];
    $id_lksa_to_archive = $user_data['Id_lksa'];

    // Define Ranks (Semakin tinggi angka, semakin tinggi jabatan)
    $rank_order = [
        'Pimpinan' => 3,
        'Kepala LKSA' => 2,
        'Pegawai' => 1,
        'Petugas Kotak Amal' => 1
    ];

    $session_rank = $rank_order[$jabatan_session] ?? 0;
    $archive_rank = $rank_order[$jabatan_to_archive] ?? 0;

    // --- LOGIKA HIERARKI & LKSA ---
    $is_pimpinan_pusat = ($jabatan_session == 'Pimpinan' && $id_lksa_session == 'Pimpinan_Pusat');
    $is_same_lksa = ($id_lksa_session == $id_lksa_to_archive);

    if (!$is_pimpinan_pusat && !$is_same_lksa) {
        die("Akses ditolak. Anda hanya dapat mengarsipkan pengguna dari LKSA yang sama.");
    }
    
    // Cegah user mengarsipkan dirinya sendiri
    if ($id_user_to_archive == $_SESSION['id_user']) {
        die("Akses ditolak. Anda tidak dapat mengarsipkan akun Anda sendiri.");
    }
    
    // Periksa apakah jabatan yang diarsip lebih rendah dari jabatan session
    if ($session_rank <= $archive_rank) { 
        // Pengecualian: Pimpinan Pusat BISA mengarsipkan Pimpinan Cabang/Kepala LKSA
        if (!($is_pimpinan_pusat && ($jabatan_to_archive == 'Pimpinan' || $jabatan_to_archive == 'Kepala LKSA'))) {
            die("Akses ditolak. Anda tidak dapat mengarsipkan pengguna dengan jabatan setingkat atau lebih tinggi dari Anda.");
        }
    }
    // --- END LOGIKA HIERARKI & LKSA ---


    // Soft Delete (Arsip): Mengubah Status menjadi 'Archived'
    $sql = "UPDATE User SET Status = 'Archived' WHERE Id_user = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_user_to_archive);
    
    if ($stmt->execute()) {
        header("Location: users.php");
    } else {
        die("Error saat mengarsipkan pengguna: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: users.php");
}

$conn->close();
exit;
?>