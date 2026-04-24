<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];

// --- FUNGSI BARU UNTUK FORMAT TANGGAL KE INDONESIA ---
function format_tanggal_indo($date_string) {
    if (!$date_string) return '-';
    // Cek apakah string adalah format tanggal YYYY-MM-DD
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
    // Jika bukan tanggal, kembalikan string aslinya (misalnya: nama hari 'Senin')
    return $date_string;
}
// ----------------------------------------------------

// Ambil input pencarian dan filter
$search_query = $_GET['search'] ?? '';
$filter_by = $_GET['filter_by'] ?? 'All'; // Default: Cari di semua kolom
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


// PERUBAHAN: Menambahkan klausa WHERE untuk pencarian dinamis
$sql = "SELECT ka.*, MAX(dka.ID_Kwitansi_KA) AS is_collected_today
        FROM KotakAmal ka
        LEFT JOIN Dana_KotakAmal dka ON ka.ID_KotakAmal = dka.ID_KotakAmal AND dka.Tgl_Ambil = CURDATE()
        WHERE ka.Status = 'Active'";
        
$params = [];
$types = "";

// 1. Cek Pencarian Teks
if (!empty($search_query)) {
    if ($filter_by !== 'All' && in_array($filter_by, $allowed_columns)) {
        // Pencarian spesifik pada kolom yang diizinkan
        $sql .= " AND ka." . $filter_by . " LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } else {
        // Pencarian di semua kolom (Default/Fallback)
        $sql .= " AND (ka.ID_KotakAmal LIKE ? OR ka.Nama_Toko LIKE ? OR ka.Alamat_Toko LIKE ? OR ka.Nama_Pemilik LIKE ? OR ka.ID_Provinsi LIKE ? OR ka.ID_Kabupaten LIKE ? OR ka.ID_Kecamatan LIKE ? OR ka.ID_Kelurahan LIKE ? OR ka.Jadwal_Pengambilan LIKE ?)";
        
        // Bind parameter untuk 9 kolom
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_param;
            $types .= "s";
        }
    }
}

// 2. Cek Filter LKSA
if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    $sql .= " AND ka.Id_lksa = ?";
    $params[] = $id_lksa;
    $types .= "s";
}

// 3. Cek Filter Bulan dan Tahun (Hanya berlaku untuk Jadwal Pengambilan sebagai YYYY-MM-DD)
$filter_month = $_GET['filter_month'] ?? '';
$filter_year = $_GET['filter_year'] ?? '';

if (!empty($filter_month) && !empty($filter_year)) {
    $sql .= " AND MONTH(ka.Jadwal_Pengambilan) = ?";
    $params[] = $filter_month;
    $types .= "s";

    $sql .= " AND YEAR(ka.Jadwal_Pengambilan) = ?";
    $params[] = $filter_year;
    $types .= "s";
}


$sql .= " GROUP BY ka.ID_KotakAmal";

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // Menggunakan splat operator untuk dynamic binding
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$result = $stmt->get_result();

?>
<style>
    /* Mengambil warna utama dari style.css */
    :root {
        --primary-color: #1F2937; 
        --secondary-color: #10B981; /* Cyan -> Emerald Green */
        --accent-kotak-amal: #0c9c6f; /* Orange -> Medium Emerald */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --border-color: #E5E7EB;
        --bg-light: #F9FAFB;
        --cancel-color: #6B7280;
    }
    
    /* Global Styles */
    .btn-action-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border-radius: 5px;
        font-size: 0.9em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .btn-lokasi {
        background-color: var(--secondary-color);
        color: white;
        padding: 6px 12px;
        font-size: 0.8em;
        font-weight: 700;
        text-decoration: none;
        border-radius: 5px;
    }
    .btn-lokasi:hover {
        background-color: #0c9c6f;
    }

    /* --- SEARCH & FILTER BAR --- */
    .search-control-group-simple {
        display: flex;
        align-items: stretch; /* Membuat semua item memiliki tinggi yang sama */
        gap: 0;
        margin-bottom: 20px;
        max-width: 900px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .search-select-wrapper { 
        position: relative;
        flex-shrink: 0;
        width: 150px;
        border-right: 1px solid var(--border-color);
        background-color: #F9FAFB;
    }
    .filter-icon { /* Ikon Filter */
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-color); 
        font-size: 1.1em;
        pointer-events: none;
        z-index: 2;
    }
    .search-select-simple {
        padding: 12px 15px 12px 35px; /* Tambahkan padding kiri untuk ikon */
        border: none;
        font-size: 0.9em; 
        background-color: transparent; 
        width: 100%;
        height: 100%;
        font-weight: 600;
        color: var(--primary-color);
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        cursor: pointer;
    }
    .search-input-simple {
        padding: 12px 15px;
        border: none;
        font-size: 1em;
        background-color: white;
        flex-grow: 1;
        min-width: 150px;
    }
    .btn-search-simple {
        background-color: var(--accent-kotak-amal); 
        color: white;
        padding: 12px 20px;
        border: none;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s;
        line-height: 1.5;
        border-radius: 0;
    }
    .btn-search-simple:hover { background-color: #047857; }
    .btn-reset-simple {
        background-color: var(--cancel-color);
        color: white;
        padding: 12px 20px;
        border-left: 1px solid #5A626A;
        border-radius: 0;
    }
    .btn-reset-simple:hover { background-color: #5A626A; }

    /* --- TABLE STYLES --- */
    .table-container {
        overflow-x: auto;
    }
    #kotak-amal-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px; 
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        border-radius: 10px; 
        overflow: hidden;
        border: 1px solid var(--border-color);
        font-size: 0.95em; 
    }

    #kotak-amal-table th, #kotak-amal-table td {
        text-align: left;
        padding: 12px 15px; 
        border-bottom: 1px solid var(--border-color); 
        white-space: nowrap;
    }

    #kotak-amal-table thead tr {
        background-color: var(--accent-kotak-amal); 
        color: var(--text-light);
        font-weight: 600;
        border-bottom: 2px solid var(--accent-kotak-amal);
    }
    
    #kotak-amal-table tbody tr:nth-child(even) {
        background-color: #FDFDFD; 
    }
    
    #kotak-amal-table tbody tr:hover {
        background-color: #FEF3C7; /* Warna hover orange lembut */
    }
    .alamat-col {
        white-space: normal !important; 
        max-width: 250px; 
        width: 250px; 
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* GAYA WA */
    .wa-link {
        display: inline-block;
        margin-left: 8px;
        color: #25D366;
        font-size: 1.1em;
        transition: color 0.2s;
    }
    .wa-link:hover {
        color: #1DA851;
    }
</style>

<h1 class="dashboard-title">Manajemen Kotak Amal</h1>
<p>Kelola data kotak amal.</p>

<a href="tambah_kotak_amal.php" class="btn btn-success">Tambah Kotak Amal</a>
<a href="arsip_kotak_amal.php" class="btn btn-cancel" style="background-color: var(--accent-kotak-amal); margin-left: 10px;">Lihat Arsip Kotak Amal</a>


<form method="GET" action="" class="search-form" style="margin-top: 20px;">
    <div class="search-control-group-simple">
        
        <div class="search-select-wrapper">
            <i class="fas fa-filter filter-icon"></i>
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
        
        <button type="submit" class="btn-search-simple" title="Cari"><i class="fas fa-search"></i></button>
        <?php if (!empty($search_query) || !empty($filter_month) || !empty($filter_year)) { ?>
            <a href="kotak-amal.php" class="btn-reset-simple" title="Reset Pencarian"><i class="fas fa-times"></i></a>
        <?php } ?>
    </div>
</form>

<div class="table-container">
<table id="kotak-amal-table">
    <thead>
        <tr>
            <th>ID Kotak Amal</th>
            <th>Nama Tempat</th>
            <th>Alamat Lengkap</th>
            <th>Provinsi</th>
            <th>Kab/Kota</th>
            <th>Kecamatan</th>
            <th>Kel/Desa</th>
            <th>Nama Pemilik</th>
            <th>No. WA Pemilik</th>
            <th>Jadwal Ambil</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { 
            // WA Link Logic START
            $wa_number = $row['WA_Pemilik'] ?? '-';
            $wa_link = '#';
            
            if ($wa_number && $wa_number != '-') {
                $clean_wa = preg_replace('/[^0-9]/', '', $wa_number);
                $wa_link_number = (substr($clean_wa, 0, 1) === '0') ? '62' . substr($clean_wa, 1) : $clean_wa;
                $wa_link = 'https://wa.me/' . $wa_link_number;
            }
            // WA Link Logic END
        ?>
            <tr>
                <td data-label="ID Kotak Amal"><?php echo $row['ID_KotakAmal']; ?></td>
                <td data-label="Nama Tempat"><?php echo $row['Nama_Toko']; ?></td>
                <td data-label="Alamat Lengkap" class="alamat-col">
                    <?php 
                        $full_address = htmlspecialchars($row['Alamat_Toko']);
                        $first_comma_pos = strpos($full_address, ',');
                        
                        if ($first_comma_pos !== false) {
                            $detail_address = substr($full_address, 0, $first_comma_pos);
                        } else {
                            $detail_address = $full_address;
                        }
                        
                        echo $detail_address;
                    ?>
                </td>
                <td data-label="Provinsi"><?php echo $row['ID_Provinsi'] ?? '-'; ?></td>
                <td data-label="Kab/Kota"><?php echo $row['ID_Kabupaten'] ?? '-'; ?></td>
                <td data-label="Kecamatan"><?php echo $row['ID_Kecamatan'] ?? '-'; ?></td>
                <td data-label="Kel/Desa"><?php echo $row['ID_Kelurahan'] ?? '-'; ?></td>
                <td data-label="Nama Pemilik"><?php echo $row['Nama_Pemilik']; ?></td>
                <td data-label="No. WA">
                    <?php echo $row['WA_Pemilik'] ?? '-'; ?>
                    <?php if ($wa_number && $wa_number != '-') { ?>
                        <a href="<?php echo $wa_link; ?>" target="_blank" class="wa-link" title="Chat via WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    <?php } ?>
                </td>
                <td data-label="Jadwal Ambil"><?php echo format_tanggal_indo($row['Jadwal_Pengambilan']); ?></td>
                
                <td data-label="Aksi" style="white-space: nowrap;">
                    <a href="detail_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-primary btn-action-icon" title="Lihat Profil & Lokasi">
                        <i class="fas fa-eye"></i>
                    </a>
                    
                    <a href="proses_arsip_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-danger btn-action-icon" title="Arsipkan" onclick="return confirm('Apakah Anda yakin ingin mengarsipkan Kotak Amal ini?');">
                        <i class="fas fa-archive"></i>
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