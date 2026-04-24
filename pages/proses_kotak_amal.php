<?php
session_start();
include '../config/database.php';

// Fungsi untuk mengunggah file foto (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $nama_toko) {
    // ... (Fungsi handle_upload tidak berubah) ...
    $target_dir = __DIR__ . '/../assets/img/';
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif");

    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => "Maaf, hanya file JPG, JPEG, PNG, & GIF yang diizinkan."];
    }

    if ($file["size"] > 5000000) { // 5MB
        return ['error' => "Maaf, ukuran file terlalu besar."];
    }

    // Format nama: kotak_amal_nama_toko_uniqid.ext
    // 1. Hapus karakter non-alfanumerik/spasi
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_toko); 
    // 2. Ganti spasi dengan underscore
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_type = "kotak_amal";

    // 3. Gabungkan dan tambahkan uniqid() singkat (5 karakter terakhir)
    $unique_filename = strtolower($safe_type . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
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
    $nama_toko = $_POST['nama_toko'] ?? '';
    
    // MENGGUNAKAN FIELD ALAMAT GABUNGAN
    $alamat_toko = $_POST['alamat_toko'] ?? ''; 

    // Mengambil data wilayah yang dikirim dari hidden field (Nama Wilayah)
    $nama_pemilik = $_POST['nama_pemilik'] ?? '';
    $wa_pemilik = $_POST['wa_pemilik'] ?? '';
    $email_pemilik = $_POST['email_pemilik'] ?? '';
    $jadwal_pengambilan = $_POST['jadwal_pengambilan'] ?? ''; 
    
    // VARIABEL BARU: Google Maps Link
    $google_maps_link = $_POST['google_maps_link'] ?? '';
    
    // Revert 'keterangan' to original field name
    $keterangan = $_POST['keterangan'] ?? '';
    
    // PERUBAHAN KRITIS: Latitude dan Longitude diset ke 0.0
    $latitude = 0.0;
    $longitude = 0.0;
    
    // VARIABEL WILAYAH BARU (DIAMBIL DARI FIELD HIDDEN)
    $provinsi_name = $_POST['ID_Provinsi'] ?? ''; 
    $kabupaten_name = $_POST['ID_Kabupaten'] ?? ''; 
    $kecamatan_name = $_POST['ID_Kecamatan'] ?? ''; 
    $kelurahan_name = $_POST['ID_Kelurahan'] ?? ''; 
    
    $foto_path = null;
    
    // Menangani unggahan foto
    if (!empty($_FILES['foto']['name'])) {
        $upload_result = handle_upload($_FILES['foto'], $nama_toko);
        if (isset($upload_result['error'])) {
            die($upload_result['error']);
        }
        $foto_path = $upload_result['filename'];
    }

    // Membuat ID Kotak Amal
    $tgl_id = date('ymd');
    $counter_sql = "SELECT COUNT(*) AS total FROM KotakAmal WHERE ID_KotakAmal LIKE 'KA_LKSA_NH_{$tgl_id}_%'";
    $result = $conn->query($counter_sql);
    $row = $result->fetch_assoc();
    $counter = $row['total'] + 1;
    $id_kotak_amal = "KA_LKSA_NH_" . $tgl_id . "_" . str_pad($counter, 3, '0', STR_PAD_LEFT);

    // Kueri SQL untuk memasukkan data kotak amal (17 Kolom)
    $sql = "INSERT INTO KotakAmal (ID_KotakAmal, Id_lksa, Nama_Toko, Alamat_Toko, ID_Provinsi, ID_Kabupaten, ID_Kecamatan, ID_Kelurahan, Nama_Pemilik, WA_Pemilik, Email, Google_Maps_Link, Jadwal_Pengambilan, Ket, Foto, Latitude, Longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    // Tipe parameter: 15 string (s) + 2 double (d) = 17 karakter
    $stmt->bind_param("sssssssssssssssdd", 
        $id_kotak_amal, 
        $id_lksa, 
        $nama_toko, 
        $alamat_toko, 
        $provinsi_name, 
        $kabupaten_name, 
        $kecamatan_name, 
        $kelurahan_name, 
        $nama_pemilik, 
        $wa_pemilik, 
        $email_pemilik, 
        $google_maps_link, // <-- Kolom Google_Maps_Link
        $jadwal_pengambilan, 
        $keterangan, 
        $foto_path, 
        $latitude, 
        $longitude 
    );

    if ($stmt->execute()) {
        header("Location: kotak-amal.php");
        exit;
    } else {
        die("Error saat menyimpan data kotak amal: " . $stmt->error);
    }
} else {
    header("Location: tambah_kotak_amal.php");
    exit;
}

$conn->close();
?>