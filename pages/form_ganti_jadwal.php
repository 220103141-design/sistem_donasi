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

// Ambil data Kotak Amal
$sql = "SELECT Nama_Toko, Jadwal_Pengambilan FROM KotakAmal WHERE ID_KotakAmal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kotak_amal);
$stmt->execute();
$result = $stmt->get_result();
$data_ka = $result->fetch_assoc();
$stmt->close();

if (!$data_ka) {
    die("Data Kotak Amal tidak ditemukan.");
}

// --- LOGIKA FORMAT TANGGAL (FIXED: Mengganti strftime() yang deprecated) ---
// Mengambil tanggal dari DB
$jadwal_db = $data_ka['Jadwal_Pengambilan'] ?? null;
$jadwal_saat_ini = 'Belum Diatur';

if ($jadwal_db && $jadwal_db !== '0000-00-00') {
    $timestamp = strtotime($jadwal_db);
    $bulan_indonesia = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    
    $day = date('d', $timestamp);
    $month_en = date('F', $timestamp);
    $year = date('Y', $timestamp);
    
    $month_id = $bulan_indonesia[$month_en] ?? $month_en;

    // Format tanggal ke d F Y (Tanggal, Nama Bulan Penuh, Tahun)
    $jadwal_saat_ini = $day . ' ' . $month_id . ' ' . $year;
}
// --- END LOGIKA FORMAT TANGGAL ---

?>
<style>
    :root {
        --primary-color: #1F2937; /* Deep Navy */
        --accent-color: #06B6D4; /* Cyan/Aqua */
        --schedule-color: #6366F1; /* Indigo untuk Jadwal */
        --success-color: #059669; 
        --cancel-color: #6B7280;
        --border-color: #D1D5DB;
        --input-focus: #6366F1; 
    }
    
    /* --- PEROMBAKAN TATA LETAK UNTUK PEMUSATAN --- */
    .content {
        /* Memastikan container luar tidak mengganggu centering */
        display: flex !important; 
        flex-direction: column;
        justify-content: center; /* Memusatkan Vertikal */
        align-items: center; /* Memusatkan Horizontal */
        min-height: 80vh; /* Pastikan area konten cukup tinggi */
        padding: 20px !important; 
        box-shadow: none !important;
        background: none !important;
        width: 100%;
        margin-top: 0 !important;
    }

    .form-card {
        max-width: 450px; /* Lebar yang fokus */
        width: 100%;
        padding: 40px; 
        background-color: #fff;
        border-radius: 12px; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        border: 1px solid var(--border-color); 
        border-top: 5px solid var(--schedule-color); /* Aksen Warna Jadwal */
        
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

    .info-box-status {
        background-color: #EEF2FF; /* Light Indigo Background */
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        border: 1px solid #C7D2FE;
        text-align: center;
    }
    .info-box-status strong {
        color: var(--primary-color);
        font-weight: 700;
        display: block;
        font-size: 1.1em; 
        margin-bottom: 5px;
    }
    .info-box-status small {
        color: var(--schedule-color);
        font-weight: 600;
        font-size: 0.9em;
    }

    .form-group label {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 8px; 
        display: block;
        font-size: 1em; 
    }

    /* Input Tanggal */
    .form-group input[type="date"] {
        width: 100%;
        padding: 12px; 
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 1em; 
        background-color: white;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-group input[type="date"]:focus {
        border-color: var(--input-focus);
        outline: none;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
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
    .btn-save-schedule {
        background-color: var(--schedule-color);
        color: white;
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
        <h1>Ganti Jadwal Pengambilan</h1>
        <p class="subtitle">Atur tanggal rutin berikutnya untuk **<?php echo htmlspecialchars($data_ka['Nama_Toko']); ?>**.</p>
        
        <div class="info-box-status">
            <small><i class="fas fa-calendar-check"></i> JADWAL SAAT INI</small>
            <strong><?php echo htmlspecialchars($jadwal_saat_ini); ?></strong>
        </div>

        <form action="proses_ganti_jadwal.php" method="POST">
            <input type="hidden" name="id_kotak_amal" value="<?php echo htmlspecialchars($id_kotak_amal); ?>">

            <div class="form-section">
                <div class="form-group">
                    <label>Pilih Tanggal Pengambilan Berikutnya:</label>
                    <input type="date" name="jadwal_baru" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" >
                    <small style="color: var(--cancel-color); display: block; margin-top: 5px;">Pilih tanggal mulai hari besok dan seterusnya.</small>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="dana-kotak-amal.php" class="btn btn-action btn-cancel"><i class="fas fa-arrow-left"></i> Batal</a>
                <button type="submit" class="btn btn-action btn-save-schedule"><i class="fas fa-save"></i> Simpan Jadwal Baru</button>
            </div>
        </form>
    </div>
</div>
<?php
include '../includes/footer.php';
$conn->close();
?>