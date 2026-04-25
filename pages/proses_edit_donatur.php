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
    $id_donatur = $_POST['id_donatur'] ?? '';
    $nama_donatur = $_POST['nama_donatur'] ?? '';
    $no_wa = $_POST['no_wa'] ?? '';
    $email = $_POST['email'] ?? '';
    $alamat_lengkap = $_POST['alamat_lengkap'] ?? '';
    $status_donasi = $_POST['status_donasi'] ?? '';
    $foto_path = null;
    $foto_lama = $_POST['foto_lama'] ?? null;
    
    // Tangani unggahan foto baru
    if (!empty($_FILES['foto']['name'])) {
        $upload_result = handle_upload($_FILES['foto'], $nama_donatur); // Panggil fungsi dengan nama donatur
        if (isset($upload_result['error'])) {
            die($upload_result['error']);
        }
        $foto_path = $upload_result['filename'];

        // Hapus foto lama jika ada
        // --- PERBAIKAN: Mengganti hardcode path dengan path relatif ---
        if ($foto_lama) {
            $file_path_lama = __DIR__ . "/../assets/img/" . $foto_lama;
            if (file_exists($file_path_lama)) {
                unlink($file_path_lama);
            }
        }
    } else {
        // Jika tidak ada foto baru, gunakan foto lama
        $foto_path = $foto_lama;
    }

    // Kueri SQL untuk memperbarui data donatur
    $sql = "UPDATE Donatur SET Nama_Donatur = ?, NO_WA = ?, Email = ?, Alamat_Lengkap = ?, Status = ?, Foto = ? WHERE ID_donatur = ?";
    $stmt = $conn->prepare($sql);
    
    // Periksa jika prepare berhasil
    if ($stmt === false) {
        die("Error saat menyiapkan kueri: " . $conn->error);
    }
    
    $stmt->bind_param("sssssss", $nama_donatur, $no_wa, $email, $alamat_lengkap, $status_donasi, $foto_path, $id_donatur);

    if ($stmt->execute()) {
        
        // Perbarui variabel sesi nama donatur jika user yang login adalah Donatur
        if (isset($_SESSION['id_donatur']) && $_SESSION['id_donatur'] == $id_donatur) {
            $_SESSION['nama_donatur'] = $nama_donatur; // Simpan nama yang baru ke sesi
            // Redirect Donatur ke Dashboard mereka
            header("Location: dashboard_donatur.php?status=success"); // [cite: 8]
        } else {
            // === PERBAIKAN: Redirect Admin/Pegawai ke halaman DETAIL Donatur ===
            header("Location: detail_donatur.php?id=" . $id_donatur . "&status=success");
        }
        exit;
    } else {
        die("Error saat memperbarui data donatur: " . $stmt->error);
    }
} else {
    // Jika tidak ada data POST, tentukan tujuan redirect berdasarkan peran
    if (isset($_SESSION['id_donatur']) && !empty($_SESSION['id_donatur'])) {
        header("Location: dashboard_donatur.php"); // [cite: 8]
    } else {
        header("Location: donatur.php"); // [cite: 8]
    }
    exit;
}

$conn->close();
?>