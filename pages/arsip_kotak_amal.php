<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

$jabatan = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// --- FUNGSI UNTUK FORMAT TANGGAL KE INDONESIA ---
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

// --- LOGIKA PENCARIAN ---
$search_query = $_GET['search'] ?? '';
$filter_by = $_GET['filter_by'] ?? 'All'; 
$search_param = "%" . $search_query . "%";

// Daftar kolom yang diizinkan untuk pencarian (WHITELIST)
$allowed_columns = [
    'ID_KotakAmal', 'Nama_Toko', 'Alamat_Toko', 'Nama_Pemilik', 
    'ID_Provinsi', 'ID_Kabupaten', 'ID_Kecamatan', 'ID_Kelurahan',
    'Jadwal_Pengambilan' 
];
// Label untuk ditampilkan di dropdown
$column_labels = [
    'All' => 'Semua Kolom',
    'ID_KotakAmal' => 'ID Kotak Amal',
    'Nama_Toko' => 'Nama Tempat',
    'Alamat_Toko' => 'Alamat Lengkap',
    'Nama_Pemilik' => 'Nama Pemilik',
    'ID_Provinsi' => 'Provinsi',
    'ID_Kabupaten' => 'Kota/Kabupaten',
    'ID_Kecamatan' => 'Kecamatan',
    'ID_Kelurahan' => 'Kelurahan/Desa',
    'Jadwal_Pengambilan' => 'Jadwal Ambil'
];

// Mengambil data yang Status = 'Archived'
$sql = "SELECT ka.*
        FROM KotakAmal ka
        WHERE ka.Status = 'Archived'";

$params = [];
$types = "";

// 1. Cek Pencarian Teks
if (!empty($search_query)) {
    if ($filter_by !== 'All' && in_array($filter_by, $allowed_columns)) {
        $sql .= " AND ka." . $filter_by . " LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } else {
        $sql .= " AND (ka.ID_KotakAmal LIKE ? OR ka.Nama_Toko LIKE ? OR ka.Alamat_Toko LIKE ? OR ka.Nama_Pemilik LIKE ? OR ka.ID_Provinsi LIKE ? OR ka.ID_Kabupaten LIKE ? OR ka.ID_Kecamatan LIKE ? OR ka.ID_Kelurahan LIKE ? OR ka.Jadwal_Pengambilan LIKE ?)";
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_param;
            $types .= "s";
        }
    }
}

// Filter LKSA
if ($jabatan != 'Pimpinan' || $id_lksa != 'Pimpinan_Pusat') {
    $sql .= " AND ka.Id_lksa = ?";
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
?>

<style>
    :root {
        --primary-color: #1F2937; 
        --secondary-color: #06B6D4; 
        --accent-kotak-amal: #F97316; /* Orange */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --border-color: #E5E7EB;
        --bg-light: #F9FAFB;
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
        background-color: #047857; /* Deep Emerald Green */
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
    
    /* PUSATKAN KONTEN DAN ATUR LEBAR */
    .arsip-wrapper {
        max-width: 1000px; 
        margin: 15px auto; /* Memastikan konten terpusat */
    }
    .top-nav-actions {
        margin-bottom: 10px;
        text-align: right; /* Posisikan tombol kembali di kanan */
        padding: 0 0 10px 0;
        border-bottom: 1px solid var(--border-color); /* Garis pemisah */
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
    
    .table-container {
        width: 100%; 
        overflow-x: auto;
        margin-top: 0;
    }
    
    /* HILANGKAN JUDUL DAN TOMBOL KEMBALI */
    .dashboard-title, .float-back-btn, .content > p {
        display: none;
    }
    
    /* GAYA KHUSUS TABEL */
    .responsive-table {
        border-spacing: 0;
        border-radius: 0 0 12px 12px; /* Sudut bawah bulat */
        overflow: hidden;
        border: 1px solid var(--border-color);
        border-top: none; /* Menyambung ke form */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        width: 100%; 
    }
    .responsive-table th {
        background-color: var(--accent-kotak-amal) !important; 
        color: white;
        font-weight: 700;
        padding: 12px 15px; 
        font-size: 0.9em;
        white-space: nowrap; 
    }
    .responsive-table td {
        padding: 12px 15px; 
        font-size: 0.9em;
        white-space: nowrap;
    }
    .responsive-table tbody tr {
        transition: background-color 0.2s, box-shadow 0.2s;
    }
    .responsive-table tbody tr:hover {
        background-color: #FEF3C7; 
    }
    .alamat-col {
        white-space: normal !important; 
        max-width: 250px; 
        width: 250px; 
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* --- SIMPLIFIED SEARCH & FILTER BAR PERBAIKAN FINAL --- */
    .search-form {
        max-width: 1000px; 
        margin: 0 auto; /* PENTING: Pusatkan form */
    }
    .search-control-group-simple {
        display: flex;
        align-items: stretch;
        gap: 0; 
        padding: 0;
        margin: 0;
        width: 100%; 
        background-color: #fff;
        border-radius: 12px 12px 0 0; /* Sudut atas bulat */
        border: 1px solid var(--border-color);
        border-bottom: none; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    /* WRAPPER DROPDOWN */
    .search-select-wrapper { 
        position: relative;
        flex-shrink: 0;
        width: 180px;
        border-right: 1px solid var(--border-color);
    }
    /* Dropdown Style */
    .search-select-simple {
        padding: 12px 15px; 
        border: none; 
        background-color: #fff; 
        width: 100%;
        height: 100%;
        font-weight: 600;
        color: var(--primary-color);
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        cursor: pointer;
    }
    /* INPUT PENCARIAN */
    .search-input-simple {
        padding: 12px 15px;
        border: none;
        font-size: 1em;
        background-color: white;
        flex-grow: 1;
        min-width: 200px;
        border-right: 1px solid var(--border-color);
    }
    
    /* ACTIONS GROUP (Untuk Tombol) */
    .search-actions-group {
        display: flex;
        gap: 0;
        align-items: center;
        flex-shrink: 0;
    }
    
    /* Tombol Cari */
    .btn-search-simple {
        background-color: var(--accent-kotak-amal);
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 0 12px 0 0; /* Sudut kanan atas bulat */
        font-weight: 700;
        cursor: pointer;
        line-height: 1.5;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Tombol Reset (Hanya Tampil jika ada query) */
    .btn-reset-simple {
        background-color: var(--danger-color);
        color: white;
        width: 45px; 
        padding: 0;
        border: none;
        border-radius: 0;
        font-weight: 700;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border-left: 1px solid #5A626A;
        transition: background-color 0.2s;
    }
    .btn-reset-simple:hover { 
        background-color: #DC2626; 
    }
    .btn-reset-simple i {
        font-size: 1.1em;
        margin: 0;
    }
    .btn-reset-simple span {
        display: none; 
    }

    /* MEDIA QUERIES for search form (minimalist mode) */
    @media (max-width: 600px) {
        .search-form {
            padding: 0 10px;
            margin-top: 15px;
        }
        .search-control-group-simple {
            flex-direction: column;
            border-radius: 12px;
        }
        .search-select-wrapper {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--border-color); 
        }
        .search-input-simple {
            min-width: 100%;
            border-right: none;
            border-bottom: 1px solid var(--border-color);
        }
        .search-actions-group {
            width: 100%;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-search-simple {
            width: 100%;
            border-radius: 12px;
        }
        .btn-reset-simple {
            display: none;
        }
    }
</style>

<div class="arsip-wrapper">

    <div class="top-nav-actions">
        <a href="kotak-amal.php" class="btn top-nav-btn"><i class="fas fa-arrow-left"></i> Kembali ke Aktif</a>
    </div>

    <form method="GET" action="" class="search-form">
        <div class="search-control-group-simple">
            
            <div class="search-select-wrapper">
                <select name="filter_by" id="filter_by" class="search-select-simple">
                    <?php 
                    foreach ($column_labels as $value => $label) {
                        $selected = ($filter_by == $value) ? 'selected' : '';
                        echo "<option value=\"$value\" $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            
            <input type="text" name="search" placeholder="Cari di kolom terpilih..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input-simple">
            
            <div class="search-actions-group">
                <button type="submit" class="btn-search-simple" title="Cari"><i class="fas fa-search"></i> Cari</button>
                <?php if (!empty($search_query)) { ?>
                    <a href="arsip_kotak_amal.php" class="btn-reset-simple" title="Reset Pencarian"><i class="fas fa-times"></i><span>Reset</span></a>
                <?php } ?>
            </div>
        </div>
    </form>
    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th style="width: 12%;">ID Kotak Amal</th>
                    <th style="width: 20%;">Nama Tempat</th>
                    <th style="width: 15%;">Nama Pemilik</th>
                    <th style="width: 25%;">Alamat Detail</th>
                    <th style="width: 18%;">Wilayah (Kab/Kec/Kel)</th>
                    <th style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { 
                    
                    // Mengambil alamat detail
                    $full_address = htmlspecialchars($row['Alamat_Toko'] ?? '-');
                    $first_comma_pos = strpos($full_address, ',');
                    $detail_address = $first_comma_pos !== false ? substr($full_address, 0, $first_comma_pos) : $full_address;
                    
                    $wilayah_address = htmlspecialchars($row['ID_Kelurahan'] ?? '-');
                    $wilayah_address .= !empty($row['ID_Kecamatan']) ? ', ' . htmlspecialchars($row['ID_Kecamatan']) : '';
                    $wilayah_address .= !empty($row['ID_Kabupaten']) ? ', ' . htmlspecialchars($row['ID_Kabupaten']) : '';
                    
                ?>
                    <tr class="table-row-archived">
                        <td data-label="ID Kotak Amal"><?php echo $row['ID_KotakAmal']; ?></td>
                        <td data-label="Nama Toko"><?php echo $row['Nama_Toko']; ?></td>
                        <td data-label="Nama Pemilik"><?php echo $row['Nama_Pemilik']; ?></td>
                        
                        <td data-label="Alamat Detail" class="alamat-col" title="<?php echo $detail_address; ?>">
                            <?php echo $detail_address; ?>
                        </td>
                        <td data-label="Wilayah">
                            <small style="display: block; color: #6B7280;"><?php echo $wilayah_address; ?></small>
                        </td>
                        
                        <td data-label="Aksi" style="white-space: nowrap;">
                            <a href="proses_restore_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-action-icon btn-restore" title="Pulihkan" onclick="return confirm('Apakah Anda yakin ingin memulihkan Kotak Amal ini?');">
                                <i class="fas fa-undo"></i> Pulihkan
                            </a>
                            <a href="proses_hapus_permanen_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" 
                               class="btn btn-action-icon btn-delete-permanent" 
                               title="Hapus Permanen" 
                               onclick="return confirm('PERINGATAN! Anda akan menghapus Kotak Amal ini secara permanen. Tindakan ini tidak dapat dibatalkan. Lanjutkan?');">
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