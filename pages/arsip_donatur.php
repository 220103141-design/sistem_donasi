<?php
session_start();
include '../config/database.php';

// Authorization check: Pimpinan, Kepala LKSA, Pegawai
$jabatan = $_SESSION['jabatan'] ?? '';
$id_lksa = $_SESSION['id_lksa'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

// --- FUNGSI BARU UNTUK FORMAT TANGGAL KE INDONESIA (Diambil dari kotak-amal.php) ---
function format_tanggal_indo($date_string) {
    if (!$date_string) return '-';
    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_string)) {
        $bulan_indonesia = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $parts = explode('-', $date_string);
        $day = $parts[2];
        $month = $bulan_indonesia[$parts[1]];
        $year = $parts[0];
        return $day . ' ' . $month . ' ' . $year;
    }
    return $date_string;
}
// -------------------------------------------------------------------------------------

// PERUBAHAN: Mengambil data yang Status_Data = 'Archived' dan data wilayah
$sql = "SELECT d.*, 
               u.Nama_User, 
               d.ID_Provinsi AS Provinsi, 
               d.ID_Kota_Kab AS Kabupaten, 
               d.ID_Kecamatan AS Kecamatan, 
               d.ID_Kelurahan_Desa AS Desa
        FROM Donatur d JOIN User u ON d.ID_user = u.Id_user 
        WHERE d.Status_Data = 'Archived'";

$params = [];
$types = "";

// FIX: Hanya Pimpinan Pusat yang tidak difilter
if ($jabatan != 'Pimpinan' || $id_lksa != 'Pimpinan_Pusat') {
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " AND d.ID_LKSA = ?";
    $params[] = $id_lksa;
    $types = "s";
}

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Set sidebar stats ke string kosong agar sidebar tetap tampil
$sidebar_stats = '';

include '../includes/header.php';
?>
<style>
    /* Style disamakan dengan arsip kotak amal */
    :root {
        --primary-color: #1F2937; 
        --accent-donatur: #10B981; 
        --header-color: #0c9c6f; /* Medium Emerald (Aksen Kotak Amal) */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --border-color: #E5E7EB;
        --bg-hover: #FEF3C7; /* Light Amber/Yellow hover */
    }

    .arsip-wrapper {
        max-width: 100%; 
        margin: 0 auto; 
    }
    
    .top-nav-actions {
        margin-bottom: 15px;
        text-align: right; 
        padding: 0 0 10px 0;
        border-bottom: 1px solid var(--border-color); 
    }
    .top-nav-btn {
        background-color: #6B7280; 
        color: white;
        padding: 8px 15px;
        font-weight: 600;
        font-size: 0.9em;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* Custom Style untuk tombol aksi kompak (PULIHKAN & HAPUS) */
    .btn-action-icon {
        padding: 6px 12px;
        margin: 0 4px;
        border-radius: 8px;
        font-size: 0.85em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .btn-restore {
        background-color: #047857; /* Dark Emerald */
        color: white;
    }
    .btn-delete-permanent {
        background-color: #B91C1C; /* Deep Red */
        color: white;
    }
    .btn-action-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    
    /* GAYA KHUSUS TABEL */
    .responsive-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border-radius: 12px; 
        overflow: hidden;
        border: 1px solid var(--border-color);
    }
    .responsive-table thead tr {
        background-color: var(--header-color) !important; 
        color: white;
        font-weight: 700;
    }
    .responsive-table th {
        padding: 12px 15px; 
        font-size: 0.9em;
        white-space: nowrap; 
    }
    .responsive-table td {
        padding: 12px 15px; 
        font-size: 0.9em;
        white-space: nowrap;
        vertical-align: middle;
        border-bottom: 1px solid #F3F4F6;
    }
    .responsive-table tbody tr:hover {
        background-color: var(--bg-hover); 
    }
    .alamat-col {
        white-space: normal !important; 
        max-width: 250px; 
        width: 250px; 
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="arsip-wrapper">
    <div class="top-nav-actions">
        <a href="donatur.php" class="btn top-nav-btn"><i class="fas fa-arrow-left"></i> Kembali ke Aktif</a>
    </div>

    <h1 class="dashboard-title"><i class="fas fa-archive" style="color: var(--header-color);"></i> Arsip Donatur ZIS</h1>
    <p style="color: #555; margin-top: -10px; margin-bottom: 20px;">Daftar donatur yang telah diarsipkan (soft delete). Anda dapat memulihkan dari sini.</p>

    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th style="width: 12%;">ID Donatur</th>
                    <th style="width: 20%;">Nama Donatur</th>
                    <th style="width: 15%;">No. WA</th>
                    <th style="width: 25%;">Alamat Detail</th>
                    <th style="width: 18%;">Wilayah (Kab/Kec/Kel)</th>
                    <th style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { 
                    
                    // Mengambil alamat detail (bagian pertama dari Alamat_Lengkap sebelum koma pertama)
                    $full_address = htmlspecialchars($row['Alamat_Lengkap'] ?? '-');
                    $first_comma_pos = strpos($full_address, ',');
                    $detail_address = $first_comma_pos !== false ? substr($full_address, 0, $first_comma_pos) : $full_address;
                    
                    // Menggabungkan nama wilayah (yang disimpan di kolom Nama Wilayah)
                    $wilayah_parts = [];
                    if (!empty($row['Desa'])) $wilayah_parts[] = $row['Desa'];
                    if (!empty($row['Kecamatan'])) $wilayah_parts[] = $row['Kecamatan'];
                    if (!empty($row['Kabupaten'])) $wilayah_parts[] = $row['Kabupaten'];
                    $wilayah_address = implode(', ', $wilayah_parts);
                    
                ?>
                    <tr class="table-row-archived">
                        <td data-label="ID Donatur"><?php echo $row['ID_donatur']; ?></td>
                        <td data-label="Nama Donatur"><?php echo $row['Nama_Donatur']; ?></td>
                        <td data-label="No. WA"><?php echo $row['NO_WA']; ?></td>
                        
                        <td data-label="Alamat Detail" class="alamat-col" title="<?php echo $detail_address; ?>">
                            <?php echo $detail_address; ?>
                        </td>
                        <td data-label="Wilayah">
                            <small style="display: block; color: #6B7280;"><?php echo $wilayah_address; ?></small>
                        </td>
                        
                        <td data-label="Aksi" style="white-space: nowrap;">
                            <a href="proses_restore_donatur.php?id=<?php echo $row['ID_donatur']; ?>" class="btn btn-action-icon btn-restore" title="Pulihkan" onclick="return confirm('Apakah Anda yakin ingin memulihkan donatur ini?');">
                                <i class="fas fa-undo"></i> Pulihkan
                            </a>
                            <a href="proses_hapus_permanen_donatur.php?id=<?php echo $row['ID_donatur']; ?>" class="btn btn-action-icon btn-delete-permanent" title="Hapus Permanen" onclick="return confirm('PERINGATAN! Anda yakin ingin MENGHAPUS PERMANEN donatur ini beserta RIWAYAT SUMBANGAN-nya? Tindakan ini tidak dapat dibatalkan.');">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php
include '../includes/footer.php';
$conn->close();
?>