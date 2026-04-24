<?php
session_start();
include '../config/database.php';
// Set $sidebar_stats agar tidak ada error di header
$sidebar_stats = ''; 
include '../includes/header.php';

// Authorization check: Pimpinan, Kepala LKSA, dan Pegawai
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

$id_donatur_to_view = $_GET['id'] ?? '';
if (empty($id_donatur_to_view)) {
    die("ID Donatur tidak ditemukan.");
}

// Ambil data Donatur, termasuk nama user yang menginput (ID_user JOIN User.Id_user)
// Serta data wilayah yang tersimpan dalam kolom (ID_Provinsi, ID_Kota_Kab, dsb.)
$sql = "SELECT d.*, 
               d.ID_Provinsi AS Provinsi, 
               d.ID_Kota_Kab AS Kabupaten, 
               d.ID_Kecamatan AS Kecamatan, 
               d.ID_Kelurahan_Desa AS Desa, 
               u.Nama_User AS Dibuat_Oleh
        FROM Donatur d 
        LEFT JOIN User u ON d.ID_user = u.Id_user
        WHERE d.ID_donatur = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_donatur_to_view);
$stmt->execute();
$result = $stmt->get_result();
$data_donatur = $result->fetch_assoc();
$stmt->close();

if (!$data_donatur) {
    die("Data Donatur tidak ditemukan.");
}

// --- PERBAIKAN JALUR GAMBAR: Menggunakan jalur RELATIF ke folder assets/img/ ---
// Jika ada foto, gunakan nama file dari DB. Jika tidak, gunakan yayasan.png.
$image_src_detail = $data_donatur['Foto'] ? "../assets/img/" . htmlspecialchars($data_donatur['Foto']) : "../assets/img/yayasan.png";

// Format Tanggal Rutinitas
$tgl_rutinitas = $data_donatur['Tgl_Rutinitas'] && $data_donatur['Tgl_Rutinitas'] !== '0000-00-00' ? date('d M Y', strtotime($data_donatur['Tgl_Rutinitas'])) : '-';

// WA Link Logic
$wa_number = $data_donatur['NO_WA'] ?? '-';
$wa_link = '#';

if ($wa_number && $wa_number != '-') {
    $clean_wa = preg_replace('/[^0-9]/', '', $wa_number);
    $wa_link_number = (substr($clean_wa, 0, 1) === '0') ? '62' . substr($clean_wa, 1) : $clean_wa;
    $wa_link = 'https://wa.me/' . $wa_link_number;
}
?>
<style>
    /* Variabel Warna Elegan (Disamakan dengan Donatur - Emerald Green) */
    :root {
        --donatur-primary: #334155; /* Dark Slate - Teks utama */
        --donatur-accent: #10B981; /* Emerald Green - Aksen utama */
        --profile-bg: #F0FDF4; /* Very Light Green - Background profil */
        --profile-border: #047857; /* Dark Emerald - Border profil */
        --btn-edit: #047857; /* Dark Emerald untuk Edit */
        --btn-cancel: #6B7280; /* Gray untuk Kembali/Batal */
        /* Warna Aksen Data Row - Muted Deep Tones */
        --row-contact: #10B981; 
        --row-schedule: #6366F1; /* Indigo untuk Jadwal Rutin */
        --row-location: #EF4444; /* Red untuk Alamat */
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
    .donatur-header-profile { 
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
    .donatur-header-profile:hover {
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
        background-color: #059669; /* Darker Dark Emerald */
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
        color: var(--donatur-primary);
        font-size: 1.4em;
        margin-top: 0;
        border-bottom: 2px solid #F3F4F6;
        padding-bottom: 8px;
        margin-bottom: 20px;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
    }

    /* --- GAYA DATA ROW --- */
    .data-row {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        padding: 8px 0; 
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
        width: 150px; /* Lebar tetap untuk label column */
    }
    .data-label-icon {
        font-size: 1.1em;
        color: var(--donatur-accent); 
    }
    .data-label {
        font-weight: 600; 
        color: #6B7280; 
        font-size: 0.9em; 
    }
    
    .data-value {
        font-weight: 600; 
        color: var(--donatur-primary); 
        font-size: 0.95em; 
        line-height: 1.4;
        text-align: left; 
        margin-left: 15px; 
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
         color: var(--donatur-primary);
    }

    .header-profile-id {
         color: #6B7280;
         font-size: 0.85em;
         margin-bottom: 0;
    }
    
    /* Teks Alamat Panjang */
    .alamat-value {
        white-space: pre-wrap; 
    }
    
    /* Media Query untuk Mobile */
    @media (max-width: 768px) {
        .dashboard-title {
            flex-direction: column;
            align-items: flex-start;
        }
        .detail-card {
            flex-direction: column;
            gap: 20px;
        }
        .donatur-header-profile, .profile-info, .location-info {
            flex: 1 1 100%;
            max-width: 100%; 
            min-width: 100%;
            padding: 20px;
            margin-top: 0 !important;
        }
        .data-label-group {
             width: 40%;
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
                <i class="fas fa-hand-holding-heart" style="color: var(--donatur-accent);"></i> 
                Profil Donatur ZIS: <?php echo htmlspecialchars($data_donatur['Nama_Donatur']); ?>
            </span>
        </h1>
        
        <div class="detail-card">
            
            <div class="donatur-header-profile">
                <img src="<?php echo htmlspecialchars($image_src_detail); ?>" alt="Foto Donatur" class="header-profile-img">
                <div class="header-profile-text">
                    <p class="header-profile-name"><?php echo htmlspecialchars($data_donatur['Nama_Donatur']); ?></p>
                    <small class="header-profile-id"><?php echo htmlspecialchars($data_donatur['ID_donatur']); ?> · LKSA <?php echo htmlspecialchars($data_donatur['ID_LKSA']); ?></small>
                </div>
                
                <a href="edit_donatur.php?id=<?php echo $data_donatur['ID_donatur']; ?>" 
                   class="btn btn-edit-data-simple" 
                   title="Edit Data Donatur">
                    <i class="fas fa-edit"></i> Edit Data
                </a>
            </div>
            
            <div class="profile-info">
                <h2 style="color: var(--row-contact);"><i class="fas fa-address-card"></i> Data Kontak & Status</h2>
                
                <div> 
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-whatsapp data-label-icon"></i>
                            <span class="data-label">Nomor WhatsApp</span>
                        </div>
                        <span class="data-value">
                            <?php echo htmlspecialchars($data_donatur['NO_WA'] ?? 'Belum Tercatat'); ?>
                            <?php if ($wa_number && $wa_number != '-') { ?>
                                <a href="<?php echo $wa_link; ?>" target="_blank" style="color: var(--row-contact); margin-left: 5px;">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            <?php } ?>
                        </span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-envelope data-label-icon"></i>
                            <span class="data-label">Email</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_donatur['Email'] ?? 'Belum Tercatat'); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-hand-holding data-label-icon" style="color: var(--row-contact);"></i>
                            <span class="data-label">Status Donasi</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_donatur['Status'] ?? '-'); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-calendar-alt data-label-icon" style="color: var(--row-schedule);"></i>
                            <span class="data-label">Tgl. Rutinitas</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($tgl_rutinitas); ?></span>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label-group">
                            <i class="fas fa-user-tag data-label-icon"></i>
                            <span class="data-label">Dibuat Oleh</span>
                        </div>
                        <span class="data-value"><?php echo htmlspecialchars($data_donatur['Dibuat_Oleh'] ?? 'N/A'); ?></span>
                    </div>
                </div> 
            </div>
            
            <div class="location-info">
                <h2 style="color: var(--row-location);"><i class="fas fa-map-marker-alt"></i> Alamat & Wilayah</h2>
                
                <div class="data-row">
                    <div class="data-label-group">
                        <i class="fas fa-road data-label-icon" style="color: var(--row-location);"></i>
                        <span class="data-label">Alamat Lengkap</span>
                    </div>
                    <span class="data-value alamat-value"><?php echo htmlspecialchars($data_donatur['Alamat_Lengkap'] ?? 'Belum Tercatat'); ?></span>
                </div>
                
                <div class="data-row">
                    <div class="data-label-group">
                        <i class="fas fa-city data-label-icon"></i>
                        <span class="data-label">Wilayah (P-K-K-K)</span>
                    </div>
                    <span class="data-value">
                        <?php echo htmlspecialchars($data_donatur['Provinsi'] ?? '-'); ?>, 
                        <?php echo htmlspecialchars($data_donatur['Kabupaten'] ?? '-'); ?>,
                        <?php echo htmlspecialchars($data_donatur['Kecamatan'] ?? '-'); ?>, 
                        <?php echo htmlspecialchars($data_donatur['Desa'] ?? '-'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <a href="donatur.php" class="btn btn-cancel" style="margin-top: 30px;"><i class="fas fa-arrow-left"></i> Kembali ke Manajemen Donatur</a>

    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>