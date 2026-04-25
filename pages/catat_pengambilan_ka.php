<?php
session_start();
include '../config/database.php';
$sidebar_stats = '';
include '../includes/header.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kotak_amal = $_GET['id'] ?? '';
if (empty($id_kotak_amal)) {
    die("ID Kotak Amal tidak ditemukan.");
}

// Tambahkan pengambilan ID Surat Tugas <-- BARU
$id_surat_tugas = $_GET['id_tugas'] ?? ''; 
if (empty($id_surat_tugas)) {
    die("ID Surat Tugas tidak ditemukan. Silakan buat Surat Tugas terlebih dahulu melalui menu Pengambilan Kotak Amal.");
}

// Ambil data Kotak Amal
$sql = "SELECT Nama_Toko, Nama_Pemilik FROM KotakAmal WHERE ID_KotakAmal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kotak_amal);
$stmt->execute();
$result = $stmt->get_result();
$data_ka = $result->fetch_assoc();
$stmt->close();

if (!$data_ka) {
    die("Data Kotak Amal tidak ditemukan.");
}
?>
<style>
    :root {
        --primary-color: #1F2937; /* Deep Navy */
        --accent-color: #06B6D4; /* Cyan/Aqua - Lebih Profesional */
        --success-color: #059669; /* Darker Emerald for saving */
        --cancel-color: #6B7280;
        --border-color: #D1D5DB;
        --input-focus: #06B6D4; /* Focus Aqua */
    }
    
    /* --- PEROMBAKAN TATA LETAK UNTUK PEMUSATAN --- */
    .content {
        display: flex !important; 
        flex-direction: column;
        justify-content: center; 
        align-items: center; 
        min-height: 80vh; 
        padding: 20px !important; 
        box-shadow: none !important;
        background: none !important;
        width: 100%;
        margin-top: 0 !important;
    }

    .form-card {
        max-width: 400px; /* Diubah menjadi lebih ramping */
        width: 100%;
        padding: 40px; 
        background-color: #fff;
        border-radius: 12px; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        border: 1px solid var(--border-color); 
        border-top: 5px solid var(--accent-color); 
        
        margin: 0; 
    }
    /* --- END PEROMBAKAN TATA LETAK --- */


    .form-card h1 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8em; 
        color: var(--primary-color);
        font-weight: 700;
        text-align: center;
        margin-bottom: 5px;
    }

    .form-card p.subtitle {
        color: var(--cancel-color);
        text-align: center;
        margin-bottom: 25px;
        font-size: 0.95em;
    }

    .location-info-box {
        background-color: #F9FAFB; 
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        border: 1px solid #F3F4F6;
        text-align: center;
    }
    .location-info-box small {
        color: var(--cancel-color);
        font-size: 0.85em;
        display: block;
    }
    .location-info-box .location-detail {
        font-size: 1.1em;
        font-weight: 700;
        color: var(--primary-color);
        margin-top: 5px;
        display: block;
    }

    /* NEW STYLE: Status Otomatis */
    .auto-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 50px; /* Pill shape */
        background-color: #E0F2F1; /* Very light green */
        color: var(--success-color);
        font-weight: 600;
        font-size: 0.8em;
        margin-top: 10px;
    }
    .auto-status-badge i {
        margin-right: 6px;
        font-size: 1.1em;
    }

    .form-group label {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 12px; 
        display: block;
        font-size: 1.1em; 
        text-align: center;
    }

    /* Input Nominal Sangat Dominan */
    .form-group input#jumlah_uang {
        width: 100%;
        padding: 15px; 
        border: 2px solid #E5E7EB;
        border-radius: 10px;
        box-sizing: border-box;
        font-size: 2.5em; 
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        color: var(--success-color);
        text-align: center;
        background-color: white;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-group input#jumlah_uang:focus {
        border-color: var(--input-focus);
        outline: none;
        box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.2);
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 30px;
    }
    
    /* Tombol Aksi */
    .btn-action {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-success {
        background-color: var(--success-color);
    }
    .btn-cancel {
        background-color: var(--cancel-color);
    }
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>

<div class="content" style="padding: 0; background: none; box-shadow: none;">
    <div class="form-card">
        <h1>Catat Pengambilan Dana</h1>
        <p class="subtitle">Lokasi: <?php echo htmlspecialchars($data_ka['Nama_Toko']); ?></p>
        
        <div class="location-info-box">
            <small>
                <i class="fas fa-map-marker-alt" style="color: var(--accent-color);"></i>
                Pemilik: <?php echo htmlspecialchars($data_ka['Nama_Pemilik']); ?>
            </small>
            <div class="auto-status-badge">
                <i class="fas fa-file-signature"></i> Surat Tugas ID: <?php echo htmlspecialchars($id_surat_tugas); ?>
            </div>
            <div class="auto-status-badge">
                <i class="fas fa-calendar-check"></i> Tanggal Dicatat Otomatis (Hari Ini)
            </div>
        </div>

        <form action="proses_ambil_dana.php" method="POST">
            <input type="hidden" name="id_kotak_amal" value="<?php echo htmlspecialchars($id_kotak_amal); ?>">
            <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($_SESSION['id_user']); ?>">
            <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>">
            <input type="hidden" name="id_surat_tugas" value="<?php echo htmlspecialchars($id_surat_tugas); ?>"> <div class="form-section">
                <div class="form-group">
                    <label for="jumlah_uang">Jumlah Uang Tunai yang Diambil (Rp):</label>
                    <input type="number" id="jumlah_uang" name="jumlah_uang" required min="0" placeholder="0">
                </div>
            </div>
            
            <div class="form-actions">
                <a href="dana-kotak-amal.php" class="btn btn-action btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
                <button type="submit" class="btn btn-action btn-success"><i class="fas fa-save"></i> Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>
<?php
include '../includes/footer.php';
$conn->close();
?>