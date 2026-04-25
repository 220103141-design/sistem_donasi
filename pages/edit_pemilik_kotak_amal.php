<?php
session_start();
include '../config/database.php';

// Verifikasi sesi pemilik kotak amal
if (!isset($_SESSION['is_pemilik_kotak_amal'])) {
    die("Akses ditolak. Silakan login sebagai pemilik kotak amal.");
}

$id_kotak_amal_to_edit = $_SESSION['id_kotak_amal'];
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";

// Ambil data kotak amal dari database
$sql = "SELECT * FROM KotakAmal WHERE ID_KotakAmal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kotak_amal_to_edit);
$stmt->execute();
$result = $stmt->get_result();
$data_ka = $result->fetch_assoc();
$stmt->close();

if (!$data_ka) {
    die("Data Kotak Amal tidak ditemukan.");
}

// Persiapan untuk layout Pemilik Kotak Amal yang minimal
$foto_ka = $data_ka['Foto'] ?? '';
$foto_path = $foto_ka ? $base_url . 'assets/img/' . $foto_ka : $base_url . 'assets/img/kotak_amal_makmur_deb0a.jpg'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Kotak Amal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ka-accent: #F97316; /* Orange */
            --ka-secondary-bg: #FEF3C7; /* Light Amber/Yellow */
            --text-dark: #1F2937; 
        }
        body {
            background-image: url('../assets/img/bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Open Sans', sans-serif;
            color: #34495e;
        }
        .form-container {
            max-width: 900px; 
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 20px; 
            box-shadow: 0 15px 50px rgba(0,0,0,0.1); 
        }
        .form-section h2 {
            border-bottom: 2px solid var(--ka-accent);
            padding-bottom: 10px;
            color: var(--ka-accent);
            font-size: 1.5em;
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
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
            display: block;
            font-size: 0.9em;
        }
        .form-group input[type="text"], 
        .form-group input[type="email"], 
        .form-group select, 
        .form-group textarea {
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 10px; 
            width: 100%;
            box-sizing: border-box;
            background-color: #fafafa; 
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--ka-accent);
            outline: none;
            box-shadow: 0 0 8px rgba(249, 115, 22, 0.4); 
            background-color: #fff;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
        }
        .btn-success { background-color: var(--ka-accent); }
        .btn-cancel { background-color: #95a5a6; }
        .foto-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 2px dashed var(--ka-accent); 
            border-radius: 15px;
            background-color: var(--ka-secondary-bg); 
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="text-align: left;"><i class="fas fa-edit" style="color: var(--ka-accent);"></i> Edit Data Kotak Amal</h1>
            <a href="dashboard_pemilik_kotak_amal.php" class="btn btn-cancel" style="background-color: #95a5a6; color: white;">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

<div class="content" style="padding: 0; background: none; box-shadow: none;">
    <div class="form-container">
        <h1>Perbarui Data Lokasi Kotak Amal Anda</h1>
        <form action="proses_edit_pemilik_kotak_amal.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_kotak_amal" value="<?php echo htmlspecialchars($data_ka['ID_KotakAmal']); ?>">
            <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data_ka['Foto']); ?>">
            
            <div class="form-section">
                <h2><i class="fas fa-box"></i> Informasi Lokasi</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Toko/Lokasi:</label>
                        <input type="text" name="nama_toko" value="<?php echo htmlspecialchars($data_ka['Nama_Toko']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Jadwal Pengambilan:</label>
                        <input type="text" name="jadwal_pengambilan" value="<?php echo htmlspecialchars($data_ka['Jadwal_Pengambilan']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat Toko (Tidak Dapat Diubah):</label>
                    <textarea name="alamat_toko" rows="3" cols="50" readonly style="background-color: #e9ecef; color: #6c757d;"><?php echo htmlspecialchars($data_ka['Alamat_Toko']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Keterangan Tambahan:</label>
                    <textarea name="keterangan" rows="3" cols="50"><?php echo htmlspecialchars($data_ka['Ket']); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-user-circle"></i> Data Kontak Pemilik</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Pemilik:</label>
                        <input type="text" name="nama_pemilik" value="<?php echo htmlspecialchars($data_ka['Nama_Pemilik']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp Pemilik:</label>
                        <input type="text" name="wa_pemilik" value="<?php echo htmlspecialchars($data_ka['WA_Pemilik']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Pemilik:</label>
                    <input type="email" name="email_pemilik" value="<?php echo htmlspecialchars($data_ka['Email']); ?>">
                </div>
            </div>
            
            <div class="form-section">
                <h2><i class="fas fa-camera"></i> Foto Kotak Amal</h2>
                <div class="foto-container">
                    <img src="../assets/img/<?php echo htmlspecialchars($data_ka['Foto'] ?? 'yayasan.png'); ?>" alt="Foto Kotak Amal" class="foto-preview">
                    
                    <div class="upload-group">
                        <label>Unggah Foto Baru (Max 5MB, JPG/PNG/GIF):</label>
                        <input type="file" name="foto" accept="image/*">
                        <small style="color: #7f8c8d; display: block; margin-top: 5px;">Kosongkan jika tidak ingin mengubah foto.</small>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="dashboard_pemilik_kotak_amal.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

    </div></body></html>
<?php
$conn->close();
?>