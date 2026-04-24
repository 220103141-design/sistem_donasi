<?php
session_start();
include '../config/database.php';
// Set $sidebar_stats agar tidak ada error di header
$sidebar_stats = ''; 
include '../includes/header.php';

// Authorization check: Pimpinan, Kepala LKSA, dan Petugas Kotak Amal
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kotak_amal = $_GET['id'] ?? '';
if (empty($id_kotak_amal)) {
    die("ID Kotak Amal tidak ditemukan.");
}

// Ambil data Kotak Amal (TERMASUK KOLOM Google_Maps_Link)
$sql = "SELECT ka.*, ka.Google_Maps_Link FROM KotakAmal ka WHERE ka.ID_KotakAmal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kotak_amal);
$stmt->execute();
$result = $stmt->get_result();
$data_ka = $result->fetch_assoc();
$stmt->close();

if (!$data_ka) {
    die("Data Kotak Amal tidak ditemukan.");
}

$latitude = $data_ka['Latitude'] ?? 0;
$longitude = $data_ka['Longitude'] ?? 0;

// --- LOGIKA PETA BARU: PRIORITASKAN LINK TERSIMPAN ---
$link_tersimpan = $data_ka['Google_Maps_Link'] ?? ''; 
$alamat_toko = $data_ka['Alamat_Toko'] ?? 'Lokasi Kotak Amal';
$keterangan = $data_ka['Ket'] ?? '';

$map_link = '';
$direct_map_link = '';

$encoded_address = urlencode($alamat_toko);

// Cek apakah nilai Google_Maps_Link adalah URL yang valid
if (!empty($link_tersimpan) && (filter_var($link_tersimpan, FILTER_VALIDATE_URL) !== FALSE)) {
    // 1. Link tersedia. Gunakan untuk link langsung.
    $direct_map_link = $link_tersimpan;

    // Cek apakah link ini format Embed yang valid (mengandung /embed/ atau output=embed)
    if (strpos($link_tersimpan, '/embed/') !== FALSE || strpos($link_tersimpan, 'output=embed') !== FALSE) {
        // A. Link adalah format embed, gunakan langsung untuk iframe.
        $map_link = $link_tersimpan;
    } else {
        // B. Link adalah format direct URL (penyebab "refused to connect"). Fallback ke pencarian alamat untuk iframe.
        $map_link = "https://maps.google.com/maps?q={$encoded_address}&t=&z=15&ie=UTF8&iwloc=&output=embed";
    }

} else {
    // 2. Link tidak tersedia. Gunakan Fallback ke pencarian berdasarkan alamat.
    $map_link = "https://maps.google.com/maps?q={$encoded_address}&t=&z=15&ie=UTF8&iwloc=&output=embed";
    $direct_map_link = "https://www.google.com/maps/search/" . $encoded_address;
}
// --- AKHIR LOGIKA PETA BARU ---


$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$foto_ka = $data_ka['Foto'] ?? '';
// Menggunakan gambar dari data, fallback ke yayasan.png
$foto_path = $foto_ka ? $base_url . 'assets/img/' . $foto_ka : $base_url . 'assets/img/yayasan.png'; 
?>
<style>
    /* Variabel Warna Elegan */
    :root {
        --ka-primary: #334155; /* Dark Slate - Teks utama */
        --ka-accent: #0c9c6f; /* Orange -> Medium Emerald - Aksen utama (Sesuai Kotak Amal) */
        --profile-bg: #FFFBEB; /* Very Light Cream - Background profil */
        --profile-border: #10B981; /* Amber -> Emerald Green - Border profil */
        --btn-edit: #0c9c6f; /* Orange -> Medium Emerald untuk Edit */
        --btn-cancel: #6B7280; /* Gray untuk Kembali/Batal */
        /* Warna Aksen Data Row - Muted Deep Tones (SEMUA HIJAU) */
        --row-contact: #047857; /* Deep Blue -> Dark Emerald */
        --row-schedule: #047857; /* Muted Gold/Brown -> Dark Emerald */
        --row-location: #047857; /* Forest Green -> Dark Emerald */
        --row-coordinate: #047857; /* Deep Purple -> Dark Emerald */
    }

    /* --- LAYOUT UTAMA --- */
    .header-content-wrapper { 
        display: flex; 
        flex-direction: column; 
        align-items: flex-start;
        width: 100%;
        margin-bottom: 20px;
    }
    
    .dashboard-title {
        font-size: 1.6em; 
        margin-bottom: 15px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
        padding-bottom: 10px;
        border-bottom: 2px solid #E5E7EB;
    }
    
    .detail-card {
        display: flex;
        gap: 30px; 
        flex-wrap: wrap;
        width: 100%;
    }
    
    /* KELOMPOK PROFIL & DATA KONTAK */
    .kotak-amal-header-profile { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        gap: 5px; 
        padding: 25px 30px; 
        border-radius: 12px;
        background: var(--profile-bg); 
        border: 1px solid var(--profile-border); 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        max-width: 300px; 
        text-align: center; 
        flex: 0 0 300px; 
        box-sizing: border-box;
        transition: transform 0.2s;
        position: relative; 
        order: -1; 
    }
    .kotak-amal-header-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    /* Tombol Edit Baru di dalam kartu profil */
    .btn-edit-data-simple {
        background-color: var(--btn-edit); 
        color: white;
        font-weight: 700;
        padding: 8px 15px;
        border-radius: 8px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        margin-top: 15px; 
    }
    .btn-edit-data-simple:hover {
        background-color: #047857; /* Darker Medium Emerald */
        transform: translateY(-2px);
    }

    .profile-info, .location-info {
        flex: 1 1 300px; 
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        background-color: #FFFFFF; 
        border: 1px solid #E5E7EB; 
        box-sizing: border-box;
    }
    
    .profile-info {
        border-left: 6px solid var(--row-contact); 
        margin-bottom: 20px;
    }

    .location-info {
        border-left: 6px solid var(--row-location);
        flex: 1 1 100%; 
    }

    /* Penyesuaian Header Internal Card */
    .profile-info h2, .location-info h2 {
        color: var(--ka-primary);
        font-size: 1.4em;
        margin-top: 0;
        border-bottom: 2px solid #F3F4F6;
        padding-bottom: 8px;
        margin-bottom: 20px;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
    }

    /* --- GAYA DATA ROW (PERBAIKAN SPASI) --- */
    .data-row {
        display: flex;
        /* Hapus justify-content: space-between */
        justify-content: flex-start;
        align-items: center;
        padding: 8px 0; /* Mengurangi padding vertikal */
        border-bottom: 1px dashed #F3F4F6; 
    }
    .data-row:last-child {
        border-bottom: none;
    }
    
    .data-label-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        width: 180px; /* Lebar tetap untuk label column */
    }
    .data-label-icon {
        font-size: 1.1em;
        color: var(--ka-accent); 
    }
    .data-label {
        font-weight: 600; 
        color: #6B7280; 
        font-size: 0.9em; 
    }
    
    .data-value {
        font-weight: 600; 
        color: var(--ka-primary); 
        font-size: 0.95em; 
        line-height: 1.4;
        text-align: left; /* Align value ke kiri (berdekatan dengan label) */
        margin-left: 15px; /* Margin pemisah antar kolom */
        max-width: none; /* Hilangkan batasan lebar */
        flex-grow: 1;
    }
    
    .header-profile-img { 
        width: 100px; 
        height: 100px; 
        object-fit: cover;
        border-radius: 50%; 
        border: 4px solid #fff; 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); 
        flex-shrink: 0;
        margin-bottom: 15px; 
    }
    
    .header-profile-name {
         font-family: 'Montserrat', sans-serif;
         font-size: 1.4em; 
         font-weight: 700;
         color: var(--ka-primary);
    }

    .header-profile-id {
         color: #6B7280;
         font-size: 0.85em;
         margin-bottom: 0;
    }

    .map-frame {
        width: 100%;
        height: 300px; 
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    /* Koordinat dihilangkan dari CSS */
    .coordinate-status {
        display: none;
    }
    
    /* Media Query untuk Mobile */
    @media (max-width: 768px) {
        .dashboard-title {
            flex-direction: column;
            align-items: flex-start;
        }
        .dashboard-title h1 {
            margin-bottom: 10px;
        }
        .detail-card {
            flex-direction: column;
            gap: 20px;
        }
        .kotak-amal-header-profile, .profile-info, .location-info {
            flex: 1 1 100%;
            max-width: 100%; 
            min-width: 100%;
            padding: 20px;
            margin-top: 0 !important;
        }
        .data-value {
            max-width: 55%; 
        }
    }
</style>

<div class="content">
    <div class="header-content-wrapper">
        <h1 class="dashboard-title">
            <span style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-search-location" style="color: var(--ka-accent);"></i> 
                Profil & Lokasi Kotak Amal: <?php echo htmlspecialchars($data_ka['Nama_Toko']); ?>
            </span>
        </h1>
        
        <div class="detail-card">
            
            <div class="kotak-amal-header-profile">
                <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Kotak Amal" class="header-profile-img">
                <div class="header-profile-text">
                    <p class="header-profile-name"><?php echo htmlspecialchars($data_ka['Nama_Toko']); ?></p>
                    <small class="header-profile-id"><?php echo htmlspecialchars($data_ka['ID_KotakAmal']); ?> · LKSA <?php echo htmlspecialchars($data_ka['Id_lksa']); ?></small>
                </div>
                
                <a href="edit_kotak_amal.php?id=<?php echo $data_ka['ID_KotakAmal']; ?>" 
                   class="btn btn-edit-data-simple" 
                   title="Edit Data Kotak Amal">
                    <i class="fas fa-edit"></i> Edit Data
                </a>
            </div>
            
            <div class="profile-info">
                <h2 style="color: var(--row-contact);"><i class="fas fa-address-card"></i> Data Toko & Kontak</h2>
                
                <div> 
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-user data-label-icon"></i>
                            <span class="data-label">Nama Pemilik</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_ka['Nama_Pemilik'] ?? 'Belum Tercatat'); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-whatsapp data-label-icon"></i>
                            <span class="data-label">Nomor WhatsApp</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_ka['WA_Pemilik'] ?? 'Belum Tercatat'); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-envelope data-label-icon"></i>
                            <span class="data-label">Email</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_ka['Email'] ?? 'Belum Tercatat'); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-calendar-alt data-label-icon" style="color: var(--row-schedule);"></i>
                            <span class="data-label">Jadwal Pengambilan</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_ka['Jadwal_Pengambilan'] ?? 'Tidak Rutin'); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-sticky-note data-label-icon"></i>
                            <span class="data-label">Keterangan Tambahan</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($keterangan); ?></span>
                    </div>
                </div> 
            </div>
            
            <div class="location-info">
                <h2 style="color: var(--row-location);"><i class="fas fa-map-marker-alt"></i> Alamat & Peta</h2>
                
                <div class="data-row">
                    <div class="data-label-group">
                        <i class="fas fa-map-pin data-label-icon"></i>
                        <span class="data-label">Alamat Lengkap</span>
                    </div>
                    <span class="data-value"><?php echo htmlspecialchars($data_ka['Alamat_Toko'] ?? 'Koordinat Belum Dicatat'); ?></span>
                </div>
                
                <div class="data-row">
                    <div class="data-label-group">
                        <i class="fas fa-link data-label-icon" style="color: var(--row-contact);"></i> 
                        <span class="data-label">Link Google Maps</span>
                    </div>
                    <?php if (!empty($link_tersimpan)): ?>
                        <span class="data-value">
                            <a href="<?php echo htmlspecialchars($direct_map_link); ?>" 
                               target="_blank" 
                               title="Buka lokasi di Google Maps"
                               style="color: var(--row-contact); font-weight: 700; text-decoration: none;">
                                Buka Peta <i class="fas fa-external-link-alt" style="font-size: 0.8em; margin-left: 5px;"></i>
                            </a>
                        </span>
                    <?php else: ?>
                        <span class="data-value" style="color: #6B7280;">Belum Ada Link Khusus</span>
                    <?php endif; ?>
                </div>
                <p style="margin-top: 25px; font-weight: 600; color: var(--ka-primary);">Tampilan Peta:</p>
                <iframe src="<?php echo htmlspecialchars($map_link); ?>" class="map-frame" allowfullscreen="" loading="lazy"></iframe>
                
                <a href="kotak-amal.php" class="btn btn-cancel" style="margin-top: 15px; width: 100%;"><i class="fas fa-arrow-left"></i> Kembali ke Manajemen Kotak Amal</a>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>