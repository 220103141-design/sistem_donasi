<?php
session_start();
include '../config/database.php';

// Verifikasi sesi pemilik kotak amal
if (!isset($_SESSION['is_pemilik_kotak_amal'])) {
    die("Akses ditolak.");
}

// Fungsi untuk mengunggah file foto (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $nama_toko) {
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

    // Format nama: kotak_amal_nama_toko_uniqid.ext
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_toko); 
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_type = "kotak_amal";

    $unique_filename = strtolower($safe_type . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['filename' => $unique_filename];
    } else {
        // Cek error spesifik jika bisa
        $error_message = "Maaf, terjadi kesalahan saat mengunggah file Anda.";
        if (isset($file['error']) && $file['error'] != UPLOAD_ERR_OK) {
            $error_message .= " Kode error PHP: " . $file['error'];
        }
        return ['error' => $error_message];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $id_kotak_amal = $_POST['id_kotak_amal'] ?? '';
    $nama_toko = $_POST['nama_toko'] ?? '';
    $nama_pemilik = $_POST['nama_pemilik'] ?? '';
    $wa_pemilik = $_POST['wa_pemilik'] ?? '';
    $email_pemilik = $_POST['email_pemilik'] ?? '';
    $jadwal_pengambilan = $_POST['jadwal_pengambilan'] ?? ''; 
    $foto_lama = $_POST['foto_lama'] ?? null;
    $keterangan = $_POST['keterangan'] ?? ''; 
    $foto_path = $foto_lama;
    
    // Tentukan URL redirect setelah sukses
    $redirect_url = "dashboard_pemilik_kotak_amal.php?status=success"; // Menambahkan status sukses

    // Menangani unggahan foto baru
    if (!empty($_FILES['foto']['name'])) {
        $upload_result = handle_upload($_FILES['foto'], $nama_toko);
        if (isset($upload_result['error'])) {
            die("Error Unggah Foto: " . $upload_result['error']); // Pesan error yang lebih jelas
        }
        $foto_path = $upload_result['filename'];
        
        // Hapus foto lama jika ada
        // --- PERBAIKAN: Mengganti hardcode path dengan path relatif ---
        if ($foto_lama) {
            $file_path = __DIR__ . "/../assets/img/" . $foto_lama;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    // Kueri SQL untuk memperbarui data kotak amal. 
    // Alamat_Toko, Latitude, dan Longitude DITETAPKAN TIDAK BERUBAH.
    $sql = "UPDATE KotakAmal SET Nama_Toko = ?, Nama_Pemilik = ?, WA_Pemilik = ?, Email = ?, Jadwal_Pengambilan = ?, Foto = ?, Ket = ? WHERE ID_KotakAmal = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    // Perhatikan urutan dan tipe parameter (s untuk string)
    $stmt->bind_param("ssssssss", 
        $nama_toko, 
        $nama_pemilik, 
        $wa_pemilik, 
        $email_pemilik, 
        $jadwal_pengambilan, 
        $foto_path, 
        $keterangan,
        $id_kotak_amal
    );

    if ($stmt->execute()) {
        // Update session nama pemilik jika berubah
        if (isset($_SESSION['nama_pemilik'])) {
            $_SESSION['nama_pemilik'] = $nama_pemilik;
        }
        header("Location: " . $redirect_url);
        exit;
    } else {
        die("Error saat memperbarui data kotak amal. Cek koneksi DB atau batasan kolom: " . $stmt->error);
    }
} else {
    header("Location: dashboard_pemilik_kotak_amal.php");
    exit;
}

$conn->close();
?>