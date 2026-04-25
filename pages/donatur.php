<?php
session_start();
include '../config/database.php';

// Authorization check: Semua yang terkait dengan donasi ZIS
$jabatan = $_SESSION['jabatan'] ?? '';
$id_lksa = $_SESSION['id_lksa'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

// --- FUNGSI UNTUK FORMAT TANGGAL ---
function format_tanggal_indo($date_string) {
    if (!$date_string || $date_string === '0000-00-00') return '-';
    return date('d-m-Y', strtotime($date_string));
}
// ------------------------------------------

// PERBAIKAN: Menambahkan kolom-kolom wilayah (ID_Provinsi, ID_Kota_Kab, dll. yang menyimpan nama wilayah)
$sql = "SELECT 
            d.*, 
            d.ID_Provinsi AS Provinsi, 
            d.ID_Kota_Kab AS Kabupaten, 
            d.ID_Kecamatan AS Kecamatan, 
            d.ID_Kelurahan_Desa AS Desa, 
            u.Nama_User 
        FROM Donatur d JOIN User u ON d.ID_user = u.Id_user 
        WHERE d.Status_Data = 'Active'";

$params = [];
$types = "";

if ($jabatan != 'Pimpinan' || $id_lksa != 'Pimpinan_Pusat') {
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " AND d.ID_LKSA = ?";
    $params[] = $id_lksa;
    $types .= "s";
}

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$sidebar_stats = '';

include '../includes/header.php';
?>
<style>
    /* Style Kustom untuk Manajemen Donatur (Sleek & Minimalis) */
    :root {
        --primary-color: #1F2937;
        --accent-donatur: #10B981; /* Emerald Green */
        --preview-color: #059669; /* Darker Emerald Green untuk Detail */
        --archive-color: #EF4444;
        --accent-secondary: #0c9c6f; 
        --border-color: #E5E7EB;
        --text-color: #374151;
        --bg-hover-soft: #ECFDF5;
    }
    
    /* Tombol Aksi Ikon Only (Kecil) */
    .btn-action-icon {
        padding: 4px 6px;
        width: 30px; 
        height: 30px; 
        margin: 0 1px; 
        border-radius: 4px; 
        font-size: 0.85em; 
        display: inline-flex;
        justify-content: center; 
        align-items: center;
        box-shadow: none; 
        border: 1px solid transparent; 
        text-decoration: none;
        transition: background-color 0.2s, opacity 0.2s;
    }
    .btn-action-icon span { display: none; }
    
    /* Warna Detail (Hijau) */
    .btn-preview-custom { background-color: var(--preview-color); color: white; }
    .btn-preview-custom:hover { opacity: 0.85; border: 1px solid white; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
    
    /* Warna Arsip (Merah) */
    .btn-archive-custom { background-color: var(--archive-color); color: white; }
    .btn-archive-custom:hover { opacity: 0.85; border: 1px solid white; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }

    /* --- TATA LETAK TABEL RAMPLNG --- */
    .responsive-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); 
        border-radius: 8px; 
        overflow: hidden;
        border: 1px solid var(--border-color);
        font-size: 0.9em; 
        color: var(--text-color);
        table-layout: auto;
    }
    .responsive-table thead tr {
        background-color: var(--accent-donatur);
        color: white;
    }
    .responsive-table th {
        font-weight: 600; 
        padding: 10px 15px; 
        white-space: nowrap;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }
    .responsive-table td {
        padding: 8px 15px; 
        border-bottom: 1px solid #F3F4F6;
        white-space: nowrap;
        vertical-align: middle;
    }
    .responsive-table tbody tr:hover {
        background-color: var(--bg-hover-soft);
        box-shadow: inset 3px 0 0 0 var(--accent-donatur); 
    }
    
    /* Tombol Navigasi Atas */
    .btn-add-donatur {
        background-color: var(--accent-donatur); 
        padding: 8px 15px; 
        border-radius: 6px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: white;
    }
    .btn-add-donatur:hover {
        background-color: #0c9c6f;
    }
    .btn-archive-link {
        background-color: var(--accent-secondary); 
        padding: 8px 15px; 
        border-radius: 6px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 10px;
        color: white;
    }
</style>

<h1 class="dashboard-title"><i class="fas fa-hand-holding-heart" style="color: var(--primary-color);"></i> Manajemen Donatur ZIS</h1>
<p>Kelola data donatur.</p>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>
    <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
        Data donatur berhasil disimpan!
    </div>
<?php } ?>

<div style="margin-bottom: 20px;">
    <a href="tambah_donatur.php" class="btn btn-add-donatur"><i class="fas fa-user-plus"></i> Tambah Donatur</a>
    <a href="arsip_donatur.php" class="btn btn-archive-link"><i class="fas fa-archive"></i> Lihat Arsip Donatur</a>
</div>

<div style="overflow-x: auto;">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>ID Donatur</th>
                <th>Nama Donatur</th>
                <th>Status Donasi</th>
                <th>Tgl. Rutinitas</th> 
                <th>Provinsi</th>
                <th>Kabupaten/Kota</th>
                <th>No. WA</th>
                <th>Dibuat Oleh</th>
                <th style="width: 80px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td data-label="ID Donatur"><?php echo $row['ID_donatur']; ?></td>
                    <td data-label="Nama Donatur"><?php echo $row['Nama_Donatur']; ?></td>
                    <td data-label="Status Donasi"><?php echo $row['Status']; ?></td>
                    <td data-label="Tgl. Rutinitas">
                        <?php 
                            if ($row['Status'] == 'Rutin') {
                                echo format_tanggal_indo($row['Tgl_Rutinitas']);
                            } else {
                                echo '-';
                            }
                        ?>
                    </td>
                    <td data-label="Provinsi"><?php echo $row['Provinsi'] ?? '-'; ?></td>
                    <td data-label="Kabupaten/Kota"><?php echo $row['Kabupaten'] ?? '-'; ?></td>
                    <td data-label="No. WA"><?php echo $row['NO_WA']; ?></td>
                    <td data-label="Dibuat Oleh"><?php echo $row['Nama_User']; ?></td>
                    
                    <td data-label="Aksi" style="white-space: nowrap; text-align: center;">
                        
                        <a href="detail_donatur.php?id=<?php echo $row['ID_donatur']; ?>" 
                           class="btn btn-action-icon btn-preview-custom" 
                           title="Lihat Detail Donatur">
                            <i class="fas fa-eye"></i>
                            <span>Detail</span>
                        </a>

                        <a href="proses_arsip_donatur.php?id=<?php echo $row['ID_donatur']; ?>" 
                           class="btn btn-action-icon btn-archive-custom" 
                           onclick="return confirm('Apakah Anda yakin ingin mengarsipkan donatur ini?');" 
                           title="Arsipkan">
                            <i class="fas fa-archive"></i>
                            <span>Arsipkan</span>
                        </a>
                    </td>
                    </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>