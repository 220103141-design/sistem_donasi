<?php
session_start();
include '../config/database.php';
$sidebar_stats = '';
include '../includes/header.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['kwitansi'] ?? '';
if (empty($id_kwitansi)) {
    die("ID Kwitansi tidak ditemukan.");
}

// Ambil data Kwitansi dan info Kotak Amal
$sql = "SELECT dka.JmlUang, ka.ID_KotakAmal, ka.Nama_Toko, ka.Nama_Pemilik, ka.WA_Pemilik, dka.Tgl_Ambil
        FROM Dana_KotakAmal dka
        JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
        WHERE dka.ID_Kwitansi_KA = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kwitansi);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data pengambilan tidak ditemukan.");
}

// --- LOGIKA WA LINK ---
$wa_number = $data['WA_Pemilik'] ?? '';
$nama_toko = $data['Nama_Toko'];
$nominal_raw = $data['JmlUang'] ?? 0;
$nominal = number_format($nominal_raw, 0, ',', '.');
$tgl = date('d/m/Y', strtotime($data['Tgl_Ambil']));

// Format Nomor WA ke standar Internasional (62...)
$wa_link = '#';
if ($wa_number) {
    $clean_wa = preg_replace('/[^0-9]/', '', $wa_number);
    $wa_link_number = (substr($clean_wa, 0, 1) === '0') ? '62' . substr($clean_wa, 1) : $clean_wa;
    
    $pesan_wa = urlencode(
        "Assalamualaikum Bapak/Ibu {$data['Nama_Pemilik']},\n\n" .
        "Kami dari LKSA Nur Hidayah mengucapkan terima kasih atas partisipasi Anda dalam program Kotak Amal.\n\n" .
        "Berikut rincian pengambilan dana:\n" .
        "No. Kwitansi: {$id_kwitansi}\n" .
        "Lokasi: {$nama_toko}\n" .
        "Tanggal: {$tgl}\n" .
        "Nominal yang diambil: Rp {$nominal}\n\n" .
        "Semoga berkah. Terima kasih atas kerja samanya.\n\n" .
        "Hormat Kami,\n" .
        "Petugas LKSA Nur Hidayah"
    );
    $wa_link = "https://wa.me/{$wa_link_number}?text={$pesan_wa}";
}

// --- LOGIKA GANTI JADWAL LINK ---
$jadwal_link = "form_ganti_jadwal.php?id=" . $data['ID_KotakAmal'];

?>
<style>
    :root {
        --primary-color: #1F2937; 
        --accent-color: #06B6D4; /* Cyan/Aqua */
        --success-color: #059669; 
        --wa-color: #25D366;
        --schedule-color: #6366F1; /* Indigo */
        --cancel-color: #6B7280;
        --border-color: #D1D5DB;
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

    .confirmation-card {
        max-width: 400px; /* Ramping dan Fokus */
        width: 100%;
        padding: 40px; 
        background-color: #fff;
        border-radius: 12px; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        border: 1px solid var(--border-color); 
        border-top: 5px solid var(--success-color); /* Aksen Hijau untuk Sukses */
        text-align: center;
        margin: 0; 
    }
    /* --- END PEROMBAKAN TATA LETAK --- */


    .confirmation-card h1 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8em; 
        color: var(--success-color);
        font-weight: 700;
        margin-bottom: 5px;
    }

    /* NEW STYLE: Judul Konfirmasi Kwitansi */
    .kwitansi-status {
        font-family: 'Open Sans', sans-serif;
        font-size: 0.95em;
        color: var(--primary-color);
        margin-top: 10px;
        margin-bottom: 30px;
        line-height: 1.4;
    }
    .kwitansi-status .id-kwitansi {
        font-weight: 700;
        color: var(--success-color);
        display: block; /* Memastikan ID Kwitansi di baris baru */
        font-size: 1.1em;
        margin-top: 5px;
    }


    .info-box-wrapper {
        background-color: #F9FAFB; 
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
    }
    .info-box-wrapper strong {
        color: var(--primary-color);
        font-weight: 700;
        display: block;
        font-size: 0.95em;
        margin-top: 5px;
    }
    .info-box-wrapper small {
        color: var(--cancel-color);
        font-size: 0.8em;
    }

    /* Nominal Display (Focus Area) */
    .nominal-display {
        font-family: 'Montserrat', sans-serif;
        font-size: 2.0em; 
        font-weight: 800;
        color: var(--success-color);
        margin: 15px 0 25px 0;
        border-bottom: 2px solid #F3F4F6;
        padding-bottom: 10px;
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 30px;
    }
    .action-buttons a {
        padding: 12px;
        font-size: 1.0em;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-wa {
        background-color: var(--wa-color); 
        color: white;
    }
    .btn-schedule {
        background-color: var(--schedule-color); 
        color: white;
    }
    .btn-cancel {
        background-color: var(--cancel-color) !important;
        color: white;
    }
    .action-buttons a:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
</style>

<div class="content" style="padding: 0; background: none; box-shadow: none;">
    <div class="confirmation-card">
        <h1><i class="fas fa-check-circle"></i> Pengambilan Berhasil!</h1>
        
        <p class="kwitansi-status">
            Data kwitansi berhasil disimpan dengan ID: 
            <span class="id-kwitansi"><?php echo htmlspecialchars($id_kwitansi); ?></span>
        </p>

        <div class="info-box-wrapper">
            <small><i class="fas fa-map-marker-alt" style="color: var(--accent-color);"></i> LOKASI</small>
            <span class="location-detail">
                <?php echo htmlspecialchars($data['Nama_Toko']); ?> (Pemilik: <?php echo htmlspecialchars($data['Nama_Pemilik']); ?>)
            </span>
        </div>

        <div class="nominal-display">
            Rp <?php echo $nominal; ?>
        </div>
        
        <p style="font-weight: 600; color: var(--primary-color); margin-bottom: 20px;">Tindakan Selanjutnya:</p>
        
        <div class="action-buttons">
            <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-wa">
                <i class="fab fa-whatsapp"></i> Kirim Kwitansi & Ucapan Terima Kasih
            </a>
            
            <a href="cetak_berita_acara_word.php?kwitansi=<?php echo htmlspecialchars($id_kwitansi); ?>" 
               target="_blank" 
               class="btn-schedule" 
               style="background-color: #3498DB;">
                <i class="fas fa-file-word"></i> Download Berita Acara (Word)
            </a>
            
            <a href="<?php echo $jadwal_link; ?>" class="btn-schedule">
                <i class="fas fa-calendar-alt"></i> Update Pengambilan Selanjutnya
            </a>

            <a href="dana-kotak-amal.php" class="btn btn-cancel">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pengambilan
            </a>
        </div>
    </div>
</div>
<?php
include '../includes/footer.php';
$conn->close();
?>