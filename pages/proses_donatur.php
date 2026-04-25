<?php
session_start();
include '../config/database.php';

// Fungsi untuk mengunggah foto (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $nama_donatur) {
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

    // Format nama: donatur_nama_uniqid.ext
    // 1. Hapus karakter non-alfanumerik/spasi
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_donatur); 
    // 2. Ganti spasi dengan underscore
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_jabatan = "donatur"; // Gunakan "donatur" sebagai prefix

    // 3. Gabungkan dan tambahkan uniqid() singkat (5 karakter terakhir)
    $unique_filename = strtolower($safe_jabatan . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['filename' => $unique_filename];
    } else {
        return ['error' => "Maaf, terjadi kesalahan saat mengunggah file Anda."];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $id_lksa = $_POST['id_lksa'] ?? '';
    $id_user = $_POST['id_user'] ?? '';
    $nama_donatur = $_POST['nama_donatur'] ?? '';
    $no_wa = $_POST['no_wa'] ?? '';
    $email = $_POST['email'] ?? '';
    $alamat_lengkap = $_POST['alamat_lengkap'] ?? '';
    $status_donasi = $_POST['status_donasi'] ?? '';
    $tgl_rutinitas = $_POST['tgl_rutinitas'] ?? null; // <-- DIPERBARUI: Mengambil Tgl_Rutinitas dari POST
    
    // MENGAMBIL DATA WILAYAH BARU DARI HIDDEN FIELDS (Nama kolom sesuai database)
    $id_provinsi = $_POST['ID_Provinsi'] ?? null;
    $id_kabupaten = $_POST['ID_Kabupaten'] ?? null;
    $id_kecamatan = $_POST['ID_Kecamatan'] ?? null;
    $id_kelurahan = $_POST['ID_Kelurahan'] ?? null;

    // ==================================================================================
    // PERBAIKAN KRITIS: LOGIKA PEMBUATAN ID MENGGUNAKAN MAX(ID) UNTUK MENGHINDARI DUPLIKAT
    // ==================================================================================
    $tgl_id = date('ymd');
    $prefix = "LKSA_NH_" . $tgl_id . "_";
    
    // Query untuk mendapatkan ID Donatur tertinggi hari ini
    $max_id_sql = "SELECT MAX(ID_donatur) AS max_id FROM Donatur WHERE ID_donatur LIKE '{$prefix}%'";
    $result = $conn->query($max_id_sql);
    $row = $result->fetch_assoc();
    $max_id = $row['max_id'];
    
    // Menentukan counter berikutnya
    if ($max_id) {
        // Ambil 3 digit terakhir (counter: e.g., '003')
        $last_counter = (int)substr($max_id, -3);
        $counter = $last_counter + 1;
    } else {
        // Belum ada data hari ini
        $counter = 1;
    }

    $id_donatur = $prefix . str_pad($counter, 3, '0', STR_PAD_LEFT);
    // ==================================================================================
    
    $foto_path = null;
    if (!empty($_FILES['foto']['name'])) {
        $upload_result = handle_upload($_FILES['foto'], $nama_donatur); 
        if (isset($upload_result['error'])) {
            die($upload_result['error']);
        }
        $foto_path = $upload_result['filename'];
    }

    $status_data_active = 'Active'; 

    // [START FIX] Konversi string kosong ('') menjadi NULL untuk kolom DATE
    if ($tgl_rutinitas === '') {
        $tgl_rutinitas = null;
    }
    // [END FIX]

    // PERUBAHAN KRITIS: QUERY INSERT DENGAN 15 KOLOM (Termasuk 4 Kolom Wilayah)
    $sql = "INSERT INTO Donatur (ID_donatur, ID_LKSA, ID_user, Nama_Donatur, NO_WA, Alamat_Lengkap, Email, Foto, Status, Tgl_Rutinitas, Status_Data, ID_Provinsi, ID_Kota_Kab, ID_Kecamatan, ID_Kelurahan_Desa) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    // Binding parameters (Total 15 parameter: 15 string 's')
    $stmt->bind_param("sssssssssssssss", 
        $id_donatur, 
        $id_lksa, 
        $id_user, 
        $nama_donatur, 
        $no_wa, 
        $alamat_lengkap, 
        $email, 
        $foto_path, 
        $status_donasi, 
        $tgl_rutinitas, // <-- MENGGUNAKAN NILAI BARU (bisa string 'YYYY-MM-DD' atau null)
        $status_data_active,
        // Kolom Wilayah Baru (Sesuai nama kolom di database Anda)
        $id_provinsi,
        $id_kabupaten,
        $id_kecamatan,
        $id_kelurahan
    );

    if ($stmt->execute()) {
        header("Location: donatur.php?status=success");
        exit;
    } else {
        // Jika masih ada error (meskipun sudah menggunakan MAX ID), mungkin ada konflik lain.
        die("Error saat menyimpan donatur: " . $stmt->error);
    }
} else {
    header("Location: tambah_donatur.php");
    exit;
}
$conn->close();
?>