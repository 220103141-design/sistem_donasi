<?php
session_start();
include '../config/database.php';

if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

// Fungsi untuk mengunggah foto (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $jabatan, $nama_user) {
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

    // Format nama: jabatan_nama_uniqid.ext
    // 1. Hapus karakter non-alfanumerik/spasi
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_user); 
    // 2. Ganti spasi dengan underscore
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_jabatan = str_replace(' ', '_', trim($jabatan));

    // 3. Gabungkan dan tambahkan uniqid() singkat (5 karakter terakhir)
    $unique_filename = strtolower($safe_jabatan . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['filename' => $unique_filename];
    } else {
        return ['error' => "Maaf, terjadi kesalahan saat mengunggah file Anda."];
    }
}

function generate_user_id($conn, $jabatan, $id_lksa) {
    $parts = explode('_NH_', $id_lksa);
    $daerah = $parts[0];

    $prefix_user = '';
    switch ($jabatan) {
        case 'Pimpinan': // Pimpinan Cabang/Regional
            $prefix_user = "PIMPINAN_" . $daerah . "_NH_";
            break;
        case 'Kepala LKSA':
            $prefix_user = "KEPALA_LKSA_" . $daerah . "_NH_";
            break;
        case 'Pegawai':
            $prefix_user = "PEGAWAI_" . $daerah . "_NH_";
            break;
        case 'Petugas Kotak Amal':
            $prefix_user = "PETUGAS_KA_" . $daerah . "_NH_";
            break;
        default:
            $prefix_user = "USER_" . $daerah . "_NH_";
            break;
    }

    $count_sql = "SELECT COUNT(*) AS total FROM User WHERE `Id_user` LIKE '{$prefix_user}%'";
    $count_result = $conn->query($count_sql);
    $count_row = $count_result->fetch_assoc();
    $counter = $count_row['total'] + 1;
    $id_user = $prefix_user . str_pad($counter, 3, '0', STR_PAD_LEFT);
    
    return $id_user;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $nama_user = $_POST['nama_user'] ?? '';
    $jabatan = $_POST['jabatan'] ?? '';
    $id_lksa = $_POST['id_lksa'] ?? '';
    $foto_path = null;

    $check_lksa_sql = "SELECT Id_lksa FROM LKSA WHERE Id_lksa = ?";
    $check_lksa_stmt = $conn->prepare($check_lksa_sql);
    $check_lksa_stmt->bind_param("s", $id_lksa);
    $check_lksa_stmt->execute();
    $check_lksa_result = $check_lksa_stmt->get_result();
    
    if ($check_lksa_result->num_rows === 0) {
        die("Error: ID LKSA tidak ditemukan. Silakan masukkan ID LKSA yang valid.");
    }
    $check_lksa_stmt->close();
    
    // Proses unggah foto (MENGGUNAKAN FUNGSI BARU)
    if (!empty($_FILES['foto']['name'])) {
        $upload_result = handle_upload($_FILES['foto'], $jabatan, $nama_user);
        if (isset($upload_result['error'])) {
            die($upload_result['error']);
        }
        $foto_path = $upload_result['filename'];
    }

    if ($action == 'tambah') {
        $password = $_POST['password'] ?? '';
        $id_user = generate_user_id($conn, $jabatan, $id_lksa);
        $status_active = 'Active'; // Tambahkan status default

        // --- PERBAIKAN KRITIS: HASH PASSWORD SEBELUM DISIMPAN ---
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // PERUBAHAN: Menambahkan kolom Status
        $sql = "INSERT INTO User (Id_user, Nama_User, Password, Jabatan, Id_lksa, Foto, Status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Menggunakan $hashed_password (hash aman) saat binding
        $stmt->bind_param("sssssss", $id_user, $nama_user, $hashed_password, $jabatan, $id_lksa, $foto_path, $status_active);
        
        if ($stmt->execute()) {
            
            // --- LOGIKA UPDATE NAMA PIMPINAN/KEPALA LKSA ---
            if ($jabatan == 'Pimpinan' || $jabatan == 'Kepala LKSA') {
                $update_pimpinan_sql = "UPDATE LKSA SET Nama_Pimpinan = ? WHERE Id_lksa = ?";
                $update_pimpinan_stmt = $conn->prepare($update_pimpinan_sql);
                $update_pimpinan_stmt->bind_param("ss", $nama_user, $id_lksa);
                $update_pimpinan_stmt->execute();
                $update_pimpinan_stmt->close();
            }
            // --- END LOGIKA UPDATE NAMA PIMPINAN/KEPALA LKSA ---

            header("Location: users.php");
        } else {
            echo "Error: " . $stmt->error;
        }

    } elseif ($action == 'edit') {
        $id_user = $_POST['id_user'] ?? '';
        $foto_lama = $_POST['foto_lama'] ?? '';
        
        $sql = "UPDATE User SET Nama_User = ?, Jabatan = ?, Id_lksa = ?, Foto = ? WHERE Id_user = ?";
        
        $final_foto_path = $foto_path ?: $foto_lama;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $nama_user, $jabatan, $id_lksa, $final_foto_path, $id_user);

        if ($stmt->execute()) {
            // Hapus foto lama hanya jika foto baru berhasil diunggah
            // --- PERBAIKAN: Mengganti hardcode path dengan path relatif ---
            if ($foto_path && $foto_lama) {
                $file_path_lama = __DIR__ . "/../assets/img/" . $foto_lama;
                if (file_exists($file_path_lama)) {
                    unlink($file_path_lama);
                }
            }
            
            // Perluasan logika edit: Jika jabatan diubah menjadi Pimpinan/Kepala LKSA, update nama di tabel LKSA
            if ($jabatan == 'Pimpinan' || $jabatan == 'Kepala LKSA') {
                $update_pimpinan_sql = "UPDATE LKSA SET Nama_Pimpinan = ? WHERE Id_lksa = ?";
                $update_pimpinan_stmt = $conn->prepare($update_pimpinan_sql);
                $update_pimpinan_stmt->bind_param("ss", $nama_user, $id_lksa);
                $update_pimpinan_stmt->execute();
                $update_pimpinan_stmt->close();
            }
            
            // --- PERUBAHAN KRITIS: REDIRECT KE DETAIL/PREVIEW PENGGUNA ---
            header("Location: detail_pengguna.php?id=" . $id_user);
            exit;
            
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    $stmt->close();
}

$conn->close();
exit;
?>