<?php
session_start();
include '../config/database.php';

if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak.");
}

// Fungsi untuk mengunggah file logo (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $nama_lksa) {
    // --- PERBAIKAN: Mengganti hardcode path dengan path relatif yang dinamis ---
    $target_dir = __DIR__ . '/../assets/img/';
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif");

    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => "Maaf, hanya file JPG, JPEG, PNG, & GIF yang diizinkan."];
    }

    if ($file["size"] > 5000000) { // 5MB
        return ['error' => "Maaf, ukuran file terlalu besar."];
    }

    // Format nama: lksa_nama_uniqid.ext
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_lksa); 
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_type = "lksa";

    $unique_filename = strtolower($safe_type . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['filename' => $unique_filename];
    } else {
        return ['error' => "Maaf, terjadi kesalahan saat mengunggah file Anda."];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'tambah';
    $id_lksa = $_POST['id_lksa'] ?? null;
    $nama_lksa = $_POST['nama_lksa'] ?? '';
    
    // NEW: Mengambil detail alamat manual untuk kolom 'Alamat'
    $alamat_detail_manual = $_POST['alamat_detail_manual'] ?? ''; 
    
    // Mengambil NAMA LENGKAP (yang digabungkan di JS)
    $alamat_lengkap_final = $_POST['alamat_lengkap_final'] ?? ''; 
    
    // Mengambil Nama Wilayah untuk ID dan Kolom Database
    $nama_kabupaten = $_POST['nama_kabupaten_for_id'] ?? '';
    $id_provinsi_nama = $_POST['ID_Provinsi_nama'] ?? '';
    $id_kecamatan_nama = $_POST['ID_Kecamatan_nama'] ?? '';
    $id_kelurahan_nama = $_POST['ID_Kelurahan_nama'] ?? '';
    
    $nomor_wa_lksa = $_POST['nomor_wa_lksa'] ?? '';
    $email_lksa = $_POST['email_lksa'] ?? '';
    $logo_path = null;

    if ($action == 'tambah') {
        
        // Logika untuk membuat ID LKSA yang unik (BERDASARKAN NAMA KABUPATEN)
        $keywords_to_remove = ['KABUPATEN', 'KOTA'];
        $clean_name = $nama_kabupaten;
        foreach ($keywords_to_remove as $keyword) {
            $clean_name = preg_replace('/\b' . $keyword . '\b/i', '', $clean_name);
        }
        $clean_name = trim($clean_name);
        $prefix = preg_replace('/[^a-zA-Z0-9]/', '', str_replace(' ', '_', $clean_name));
        $prefix = strtoupper(substr($prefix, 0, 10)); 
        
        $counter_sql = "SELECT COUNT(*) AS total FROM LKSA WHERE Id_lksa LIKE '{$prefix}_NH_%'";
        $result = $conn->query($counter_sql);
        $row = $result->fetch_assoc();
        $counter = $row['total'] + 1;
        $id_lksa = $prefix . "_NH_" . str_pad($counter, 3, '0', STR_PAD_LEFT);
        
        // Menangani unggahan logo
        if (!empty($_FILES['logo']['name'])) {
            $upload_result = handle_upload($_FILES['logo'], $nama_lksa);
            if (isset($upload_result['error'])) {
                die($upload_result['error']);
            }
            $logo_path = $upload_result['filename'];
        }

        // Langkah 1: Masukkan data LKSA baru (Menyimpan detail alamat dan nama wilayah)
        $lksa_sql = "INSERT INTO LKSA (Id_lksa, Nama_LKSA, Alamat, ID_Provinsi_Nama, ID_Kabupaten_Nama, ID_Kecamatan_Nama, ID_Kelurahan_Nama, Nomor_WA, Email, Logo, Tanggal_Daftar, Nama_Pimpinan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $lksa_stmt = $conn->prepare($lksa_sql);
        $tgl_daftar = date('Y-m-d');
        $nama_pimpinan_default = ""; 

        // Binding parameters: Alamat menggunakan detail manual
        $lksa_stmt->bind_param("ssssssssssss", 
            $id_lksa, 
            $nama_lksa, 
            $alamat_detail_manual, // <-- Menggunakan Detail Manual
            $id_provinsi_nama, 
            $nama_kabupaten, 
            $id_kecamatan_nama, 
            $id_kelurahan_nama, 
            $nomor_wa_lksa, 
            $email_lksa, 
            $logo_path, 
            $tgl_daftar, 
            $nama_pimpinan_default
        );
        
        if (!$lksa_stmt->execute()) {
            die("Error saat menambahkan LKSA: " . $lksa_stmt->error);
        }
        $lksa_stmt->close();

    } elseif ($action == 'edit') {
        
        $logo_lama = $_POST['logo_lama'] ?? null;
        $final_logo_path = $logo_lama;

        // Menangani unggahan logo baru
        if (!empty($_FILES['logo']['name'])) {
            $upload_result = handle_upload($_FILES['logo'], $nama_lksa);
            if (isset($upload_result['error'])) {
                die($upload_result['error']);
            }
            $final_logo_path = $upload_result['filename'];

            // Hapus logo lama jika ada
            if ($logo_lama) {
                 $file_path_lama = __DIR__ . "/../assets/img/" . $logo_lama;
                if (file_exists($file_path_lama)) {
                    unlink($file_path_lama);
                }
            }
        }
        
        // Perbarui data LKSA (Nama_LKSA dan Alamat bisa diubah di sini)
        $update_sql = "UPDATE LKSA SET Nama_LKSA = ?, Alamat = ?, Nomor_WA = ?, Email = ?, Logo = ? WHERE Id_lksa = ?";
        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt === false) {
             die("Error saat menyiapkan kueri UPDATE: " . $conn->error);
        }
        
        // Catatan: Di halaman edit_lksa.php, Alamat detail tidak dipisah berdasarkan wilayah,
        // sehingga kita menggunakan Alamat Lengkap yang tidak dapat diubah (atau dikosongkan jika field tidak ada).
        // Kita menggunakan nilai Alamat yang sudah ada dari form edit.
        $alamat_dari_edit_form = $_POST['alamat_lksa'] ?? $data_lksa['Alamat'] ?? ''; // Mengambil dari form edit_lksa.php

        $update_stmt->bind_param("ssssss", $nama_lksa, $alamat_dari_edit_form, $nomor_wa_lksa, $email_lksa, $final_logo_path, $id_lksa);

        if (!$update_stmt->execute()) {
            die("Error saat memperbarui LKSA: " . $update_stmt->error);
        }
        $update_stmt->close();
        
        // --- PERUBAHAN REDIRECT UNTUK EDIT ---
        $redirect_url = 'detail_lksa.php?id=' . $id_lksa;
        header("Location: " . $redirect_url);
        exit;
    }
    
    // Redirect default untuk action 'tambah' (jika tidak ada logic exit di atas)
    header("Location: lksa.php");
    exit;

}

$conn->close();
?>