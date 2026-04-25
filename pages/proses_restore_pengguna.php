<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak. Hanya Pimpinan dan Kepala LKSA yang berhak memulihkan pengguna.");
}

$id_user_to_restore = $_GET['id'] ?? '';
$jabatan_session = $_SESSION['jabatan'] ?? '';
$id_lksa_session = $_SESSION['id_lksa'] ?? '';

if ($id_user_to_restore) {
     // 1. Fetch data pengguna yang akan dipulihkan
    $sql_check = "SELECT Id_lksa FROM User WHERE Id_user = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $id_user_to_restore);
    $stmt_check->execute();
    $user_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$user_data) {
        die("Pengguna tidak ditemukan.");
    }
    $id_lksa_to_restore = $user_data['Id_lksa'];

    // --- LOGIKA LKSA ---
    $is_pimpinan_pusat = ($jabatan_session == 'Pimpinan' && $id_lksa_session == 'Pimpinan_Pusat');
    $is_same_lksa = ($id_lksa_session == $id_lksa_to_restore);

    if (!$is_pimpinan_pusat && !$is_same_lksa) {
        die("Akses ditolak. Anda hanya dapat memulihkan pengguna dari LKSA yang sama.");
    }
    // --- END LOGIKA LKSA ---

    // Restore: Mengubah Status menjadi 'Active'
    $sql = "UPDATE User SET Status = 'Active' WHERE Id_user = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("s", $id_user_to_restore);
    
    if ($stmt->execute()) {
        header("Location: arsip_users.php"); // Redirect kembali ke halaman arsip
    } else {
        die("Error saat mengembalikan pengguna: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    header("Location: arsip_users.php");
}

$conn->close();
exit;
?>