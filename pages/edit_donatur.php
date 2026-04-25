<?php
session_start();
include '../config/database.php';

// Ambil data sesi dengan penanganan untuk mencegah Undefined array key warning
$jabatan_user = $_SESSION['jabatan'] ?? '';
$id_donatur_session = $_SESSION['id_donatur'] ?? '';

// Tentukan jenis pengguna
$is_admin_or_employee = in_array($jabatan_user, ['Pimpinan', 'Kepala LKSA', 'Pegawai']);
$is_donatur_logged_in = !empty($id_donatur_session);

// --- 1. Authorization and ID Determination ---
if (!$is_admin_or_employee && !$is_donatur_logged_in) {
    die("Akses ditolak.");
}

$id_donatur_to_edit = '';
if ($is_donatur_logged_in) {
    $id_donatur_to_edit = $id_donatur_session; 
    
    if (isset($_GET['id']) && $_GET['id'] !== $id_donatur_to_edit) {
        header("Location: edit_donatur.php?id=" . $id_donatur_to_edit);
        exit;
    }

} else {
    $id_donatur_to_edit = $_GET['id'] ?? '';
}

if (empty($id_donatur_to_edit)) {
    die("ID donatur tidak ditemukan.");
}

// Set ID donatur yang akan digunakan di query dan form
$id_donatur = $id_donatur_to_edit;

// Set sidebar_stats ke string kosong agar tidak ada error jika diakses oleh admin
$sidebar_stats = ''; 

// Memanggil header.php untuk layout admin/karyawan. Untuk donatur, ini akan menampilkan header minimal.
if (!$is_donatur_logged_in) {
    include '../includes/header.php';
}
// Ambil data donatur dari database
$sql = "SELECT * FROM Donatur WHERE ID_donatur = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_donatur);
$stmt->execute();
$result = $stmt->get_result();
$data_donatur = $result->fetch_assoc();

if (!$data_donatur) {
    die("Data donatur tidak ditemukan.");
}

// Persiapan path foto dengan fallback ke yayasan.png
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$foto_donatur = $data_donatur['Foto'] ?? '';
$foto_path = $foto_donatur ? $base_url . 'assets/img/' . $foto_donatur : $base_url . 'assets/img/yayasan.png'; 
?>
<?php if ($is_donatur_logged_in) { ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Donatur</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --donatur-accent: #10B981; /* Emerald Green */ 
            --donatur-secondary-bg: #E0F2F1; /* Light Green-Cyan */
            --logout-danger: #EF4444;
            --text-dark: #1E3A8A; /* Deep Blue */
        }
        body {
            background-image: url('../assets/img/bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Open Sans', sans-serif; /* Font Body */
            color: #34495e;
        }
        .form-container {
            max-width: 900px; 
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 20px; 
            box-shadow: 0 15px 50px rgba(0,0,0,0.1); 
            transition: all 0.3s ease;
        }
        .form-section {
            padding: 15px 0; 
            margin-bottom: 30px;
            border-top: 1px solid #f0f0f0;
        }
        .form-section:first-of-type {
            border-top: none;
        }
        .form-section h2 {
            /* PERBAIKAN FONT SIZE H2 */
            border-bottom: 2px solid var(--donatur-accent);
            padding-bottom: 10px;
            color: var(--donatur-accent);
            font-size: 1.5em; /* Dikecilkan dari 1.8em */
            margin-bottom: 20px;
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 30px;
        }
        .form-group label {
            /* PERBAIKAN FONT SIZE LABEL */
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px; /* Dikecilkan dari 5px */
            display: block;
            font-size: 0.9em; /* Dikecilkan dari 0.95em */
        }
        .form-group input[type="text"], 
        .form-group input[type="email"], 
        .form-group select, 
        .form-group textarea,
        .form-group input[type="date"] {
            /* PERBAIKAN PADDING INPUT */
            padding: 12px; /* Dikecilkan dari 14px */
            border: 1px solid #e0e0e0;
            border-radius: 10px; 
            width: 100%;
            box-sizing: border-box;
            background-color: #fafafa; 
            transition: border-color 0.3s, box-shadow 0.3s, background-color 0.3s;
            font-size: 0.95em; /* Font input standar */
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--donatur-accent);
            outline: none;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.4); 
            background-color: #fff;
        }
        .form-actions {
            display: flex; /* Tambahkan kembali flex */
            gap: 15px; /* Tambahkan gap */
            justify-content: flex-end;
            margin-top: 40px;
        }
        .btn {
            padding: 12px 30px;
            border: none; /* Pastikan border hilang */
            cursor: pointer;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            color: white;
            font-size: 1em; /* Mempertahankan ukuran tombol */
            display: inline-block;
        }
        .btn-success { background-color: var(--donatur-accent); }
        .btn-cancel { background-color: #95a5a6; }
        .btn-success:hover { background-color: #059669; transform: translateY(-3px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); }
        .btn-cancel:hover { background-color: #7f8c8d; transform: translateY(-3px); box-shadow: 0 6px 15px rgba(149, 165, 166, 0.3); }

        /* Style untuk Foto Profil yang diperbarui */
        .foto-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 2px dashed var(--donatur-accent); 
            border-radius: 15px;
            background-color: var(--donatur-secondary-bg); 
            margin-top: 20px;
        }
        .foto-preview {
            width: 120px; 
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff; 
            box-shadow: 0 0 15px rgba(0,0,0,0.2); 
            margin-bottom: 20px;
        }
        .upload-group {
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        .upload-group input[type="file"] {
            border: none;
            padding: 10px 0;
            background-color: transparent;
        }

        /* PERBAIKAN FONT SIZE H1 HEADER */
        .header {
            display: flex;
            justify-content: space-between; /* Tambahkan kembali space-between */
            align-items: center;
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px; /* Tambahkan kembali margin-bottom */
        }

        .header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.3em; /* Dikecilkan dari 1.5em */
            font-weight: 700;
            margin: 0;
            color: var(--text-dark);
        }
        
        /* PERBAIKAN FONT SIZE H1 CONTAINER */
        .form-container h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.0em; /* Dikecilkan dari default */
            color: var(--text-dark);
            margin-bottom: 30px;
        }
        
        .content {
            padding: 20px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="text-align: left;"><i class="fas fa-edit" style="color: var(--donatur-accent);"></i> Edit Profil Donatur</h1>
            <a href="dashboard_donatur.php" class="btn btn-cancel" style="background-color: #95a5a6; color: white;">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

<div class="content" style="padding: 0; background: none; box-shadow: none;">
    <div class="form-container">
        <h1><?php echo 'Perbarui Data Profil Anda'; ?></h1>
        <form action="proses_edit_donatur.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_donatur" value="<?php echo htmlspecialchars($data_donatur['ID_donatur']); ?>">
            <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data_donatur['Foto']); ?>">
            
            <div class="form-section">
                <h2><i class="fas fa-user-circle"></i> Foto Profil</h2>
                <div class="foto-container">
                    <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Donatur" class="foto-preview">
                    
                    <div class="upload-group">
                        <label>Unggah Foto Baru (Max 5MB, JPG/PNG/GIF):</label>
                        <input type="file" name="foto" accept="image/*">
                        <small style="color: #7f8c8d; display: block; margin-top: 5px;">Kosongkan jika tidak ingin mengubah foto.</small>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-info-circle"></i> Informasi Data Diri</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Donatur:</label>
                        <input type="text" name="nama_donatur" value="<?php echo htmlspecialchars($data_donatur['Nama_Donatur']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp:</label>
                        <input type="text" name="no_wa" value="<?php echo htmlspecialchars($data_donatur['NO_WA']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($data_donatur['Email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Status Donasi:</label>
                        <select name="status_donasi" id="statusDonasi" required>
                            <option value="Rutin" <?php echo ($data_donatur['Status'] == 'Rutin') ? 'selected' : ''; ?>>Rutin</option>
                            <option value="Insidental" <?php echo ($data_donatur['Status'] == 'Insidental') ? 'selected' : ''; ?>>Insidental</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="tglRutinitasGroup" style="display: <?php echo ($data_donatur['Status'] == 'Rutin') ? 'block' : 'none'; ?>;">
                        <label>Tanggal Rutinitas:</label>
                        <input type="date" name="tgl_rutinitas" value="<?php echo htmlspecialchars($data_donatur['Tgl_Rutinitas']); ?>" <?php echo ($data_donatur['Status'] == 'Rutin') ? 'required' : ''; ?>>
                        <small style="color: #7f8c8d; display: block; margin-top: 5px;">Diisi jika status donasi adalah Rutin.</small>
                    </div>

                </div>
            </div>
            
            <div class="form-section" style="border-top: 1px solid #f0f0f0;">
                <h2><i class="fas fa-map-marker-alt"></i> Detail Alamat</h2>
                <div class="form-group">
                    <label>Alamat Lengkap:</label>
                    <textarea name="alamat_lengkap" rows="4" cols="50"><?php echo htmlspecialchars($data_donatur['Alamat_Lengkap']); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?php echo 'dashboard_donatur.php'; ?>" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('statusDonasi');
    const tglRutinitasGroup = document.getElementById('tglRutinitasGroup');
    // Pastikan elemen ditemukan sebelum memproses lebih lanjut
    const tglRutinitasInput = tglRutinitasGroup ? tglRutinitasGroup.querySelector('input[name="tgl_rutinitas"]') : null;

    if (!statusSelect || !tglRutinitasGroup || !tglRutinitasInput) return;

    function toggleTglRutinitas() {
        if (statusSelect.value === 'Rutin') {
            tglRutinitasGroup.style.display = 'block';
            tglRutinitasInput.required = true;
        } else {
            tglRutinitasGroup.style.display = 'none';
            tglRutinitasInput.required = false;
        }
    }

    // Panggil fungsi untuk mengatur status awal saat DOM dimuat
    // Status awal sudah diatur oleh PHP, ini hanya untuk memastikan konsistensi
    toggleTglRutinitas(); 

    // Tambahkan event listener untuk perubahan pada dropdown
    statusSelect.addEventListener('change', toggleTglRutinitas);
});
</script>

<?php
echo '</div></body></html>';
} else {
    // BLOK KODE UNTUK ADMIN/PEGAWAI (jika tidak login sebagai Donatur)
    
    // Tentukan path foto relatif dengan fallback ke yayasan.png
    $image_src_relative_admin = $data_donatur['Foto'] ? "../assets/img/" . htmlspecialchars($data_donatur['Foto']) : "../assets/img/yayasan.png";

?>
    <style>
        :root {
            --donatur-accent: #10B981; /* Emerald Green */ 
            --donatur-secondary-bg: #E0F2F1; /* Light Green-Cyan */
            --text-dark: #1E3A8A; /* Deep Blue */
        }
        /* Style untuk Foto Profil yang diperbarui */
        .foto-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 2px dashed var(--donatur-accent); 
            border-radius: 15px;
            background-color: var(--donatur-secondary-bg); 
            margin-top: 20px;
        }
        .foto-preview {
            width: 120px; 
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff; 
            box-shadow: 0 0 15px rgba(0,0,0,0.2); 
            margin-bottom: 20px;
        }
        .upload-group {
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        .upload-group input[type="file"] {
            border: none;
            padding: 10px 0;
            background-color: transparent;
        }
    </style>

    <div class="main-content-area" style="flex-grow: 1;">
        <h1 class="dashboard-title">Edit Data Donatur (Admin View)</h1>
        
        <div class="content" style="padding: 0; background: none; box-shadow: none;">
            <div class="form-container">
                <h1>Edit Data Donatur</h1>
                <form action="proses_edit_donatur.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_donatur" value="<?php echo htmlspecialchars($data_donatur['ID_donatur']); ?>">
                    <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data_donatur['Foto']); ?>">
                    
                    <div class="form-section">
                        <h2><i class="fas fa-user-circle"></i> Foto Profil</h2>
                        <div class="foto-container">
                            <img src="<?php echo htmlspecialchars($image_src_relative_admin); ?>" alt="Foto Donatur" class="foto-preview">
                            
                            <div class="upload-group">
                                <label>Unggah Foto Baru (Max 5MB, JPG/PNG/GIF):</label>
                                <input type="file" name="foto" accept="image/*">
                                <small style="color: #7f8c8d; display: block; margin-top: 5px;">Kosongkan jika tidak ingin mengubah foto.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2><i class="fas fa-info-circle"></i> Informasi Data Diri</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nama Donatur:</label>
                                <input type="text" name="nama_donatur" value="<?php echo htmlspecialchars($data_donatur['Nama_Donatur']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Nomor WhatsApp:</label>
                                <input type="text" name="no_wa" value="<?php echo htmlspecialchars($data_donatur['NO_WA']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($data_donatur['Email']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status Donasi:</label>
                                <select name="status_donasi" id="statusDonasi" required>
                                    <option value="Rutin" <?php echo ($data_donatur['Status'] == 'Rutin') ? 'selected' : ''; ?>>Rutin</option>
                                    <option value="Insidental" <?php echo ($data_donatur['Status'] == 'Insidental') ? 'selected' : ''; ?>>Insidental</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="tglRutinitasGroup" style="display: <?php echo ($data_donatur['Status'] == 'Rutin') ? 'block' : 'none'; ?>;">
                                <label>Tanggal Rutinitas:</label>
                                <input type="date" name="tgl_rutinitas" value="<?php echo htmlspecialchars($data_donatur['Tgl_Rutinitas']); ?>" <?php echo ($data_donatur['Status'] == 'Rutin') ? 'required' : ''; ?>>
                                <small style="color: #7f8c8d; display: block; margin-top: 5px;">Diisi jika status donasi adalah Rutin.</small>
                            </div>

                        </div>
                    </div>
                    
                    <div class="form-section" style="border-top: 1px solid #f0f0f0;">
                        <h2><i class="fas fa-map-marker-alt"></i> Detail Alamat</h2>
                        <div class="form-group">
                            <label>Alamat Lengkap:</label>
                            <textarea name="alamat_lengkap" rows="4" cols="50"><?php echo htmlspecialchars($data_donatur['Alamat_Lengkap']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="donatur.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('statusDonasi');
            const tglRutinitasGroup = document.getElementById('tglRutinitasGroup');
            const tglRutinitasInput = tglRutinitasGroup ? tglRutinitasGroup.querySelector('input[name="tgl_rutinitas"]') : null;

            if (!statusSelect || !tglRutinitasGroup || !tglRutinitasInput) return;

            function toggleTglRutinitas() {
                if (statusSelect.value === 'Rutin') {
                    tglRutinitasGroup.style.display = 'block';
                    tglRutinitasInput.required = true;
                } else {
                    tglRutinitasGroup.style.display = 'none';
                    tglRutinitasInput.required = false;
                }
            }

            toggleTglRutinitas(); 

            statusSelect.addEventListener('change', toggleTglRutinitas);
        });
        </script>
    </div>
<?php
    include '../includes/footer.php';
}
$conn->close();
?>