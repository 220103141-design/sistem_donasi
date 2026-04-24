<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];
$jabatan_session = $_SESSION['jabatan']; // Ambil jabatan untuk logika tampilan tombol

// --- Helper functions for formatting ---
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
$filter_month = $_GET['filter_month'] ?? ''; 
$search_param = "%" . $search_query . "%";

// LOGIKA MAPPING BULAN UNTUK DROPDOWN
$bulan_map = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// LOGIKA MAPPING BULAN UNTUK PENCARIAN
$found_month_number = null;
$lower_query = strtolower($search_query);

foreach ($bulan_map as $number => $name) {
    if (strpos($lower_query, strtolower($name)) !== false) {
        $found_month_number = $number;
        break;
    }
}
// END LOGIKA MAPPING

// Daftar kolom yang diizinkan untuk pencarian
$allowed_columns = ['ID_KotakAmal', 'Nama_Toko', 'Alamat_Toko', 'Nama_Pemilik', 'Jadwal_Pengambilan'];
$column_labels = [
    'All' => 'Semua Kolom',
    'ID_KotakAmal' => 'ID Kotak Amal',
    'Nama_Toko' => 'Nama Tempat',
    'Alamat_Toko' => 'Alamat Lengkap',
    'Nama_Pemilik' => 'Nama Pemilik',
    'Jadwal_Pengambilan' => 'Jadwal Ambil'
];


// Query untuk mengambil data Kotak Amal AKTIF
$sql = "SELECT ka.*, MAX(dka.Tgl_Ambil) AS Tgl_Terakhir_Ambil
        FROM KotakAmal ka
        LEFT JOIN Dana_KotakAmal dka ON ka.ID_KotakAmal = dka.ID_KotakAmal
        WHERE ka.Status = 'Active'";
        
$params = [];
$types = "";

// 1. Cek Filter Bulan/Teks
if (!empty($filter_month)) {
    $sql .= " AND MONTH(ka.Jadwal_Pengambilan) = ?";
    $params[] = $filter_month;
    $types .= "s";
}

// 2. Cek Pencarian Teks (Sekunder/Pelengkap)
if (!empty($search_query)) {
    if (empty($filter_month) && $found_month_number) {
        $sql .= " AND MONTH(ka.Jadwal_Pengambilan) = ?";
        $params[] = $found_month_number;
        $types .= "s";
    } elseif (empty($filter_month)) {
        $sql .= " AND (ka.ID_KotakAmal LIKE ? OR ka.Nama_Toko LIKE ? OR ka.Alamat_Toko LIKE ? OR ka.Nama_Pemilik LIKE ? OR ka.Jadwal_Pengambilan LIKE ?)";
        $search_param_like = "%" . $search_query . "%";
        for ($i = 0; $i < 5; $i++) {
            $params[] = $search_param_like;
            $types .= "s";
        }
    }
}

// 3. Cek Filter LKSA
if ($_SESSION['jabatan'] != 'Pimpinan') {
    $sql .= " AND ka.ID_LKSA = ?";
    $params[] = $id_lksa;
    $types .= "s";
}

$sql .= " GROUP BY ka.ID_KotakAmal ORDER BY ka.Nama_Toko ASC";

// Eksekusi Kueri Kotak Amal
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$result_ka = $stmt->get_result();
// HAPUS SEMUA QUERY HISTORY
?>
<style>
    :root {
        --primary-color: #1F2937; 
        --secondary-color: #10B981; /* Cyan -> Emerald Green */
        --accent-kotak-amal: #0c9c6f; /* Orange -> Medium Emerald */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --border-color: #E5E7EB;
        --bg-light: #F9FAFB;
        --cancel-color: #6B7280; /* Neutral Gray */
    }
    
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

    /* --- SIMPLIFIED SEARCH BAR (Fokus pada Bulan) --- */
    .search-control-group-simple {
        display: flex;
        align-items: stretch;
        gap: 0; 
        margin-bottom: 25px;
        max-width: 600px; /* Batasi lebar form */
        border-radius: 12px; 
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    /* WRAPPER BULAN (Tombol Filter) */
    .month-filter-wrapper { 
        position: relative;
        flex-shrink: 0;
        width: 45px; 
        height: 44px; 
        border-right: 1px solid var(--border-color);
        background-color: #F8F9FA;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    /* Ikon Filter */
    .filter-icon {
        position: static; 
        z-index: 5; 
        font-size: 1.1em;
        color: var(--accent-kotak-amal);
    }
    /* Dropdown Bulan (Overlay Transparan) */
    .month-select-simple {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0; 
        z-index: 15; 
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    
    /* Input Pencarian */
    .search-input-simple {
        padding: 12px 15px;
        border: none;
        font-size: 1em;
        background-color: white;
        flex-grow: 1;
        min-width: 200px;
        border-right: 1px solid var(--border-color);
    }
    
    /* Tombol Cari */
    .btn-search-simple {
        background-color: var(--accent-kotak-amal);
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 0;
        font-weight: 700;
        line-height: 1.5;
        height: 100%;
        display: flex;
        align-items: center;
    }
    
    /* Tombol Reset (Icon Only) */
    .btn-reset-simple {
        background-color: var(--cancel-color); /* Neutral Gray */
        color: white;
        width: 40px; 
        padding: 0;
        border: none;
        border-radius: 8px; /* Sudut halus */
        height: 44px; /* Tinggi disesuaikan dengan input */
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
        margin-left: 10px; /* Jarak visual dari search bar */
    }
    .btn-reset-simple:hover { 
        background-color: #5A626A; /* Warna lebih gelap saat hover */
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15); /* Efek terangkat */
    }
    .btn-reset-simple i {
        font-size: 1.1em;
        margin: 0;
    }
    .btn-reset-simple span {
        display: none; 
    }
    
    /* TABLE STYLES SPECIFIC FOR THIS PAGE */
    .table-container {
        overflow-x: auto; 
        margin-top: 20px;
    }

    .responsive-table th {
        background-color: var(--accent-kotak-amal);
        color: white;
    }
    .responsive-table td {
        white-space: nowrap; 
    }
    .tgl-terakhir {
        font-size: 0.85em;
        color: #6B7280;
    }
    .tgl-recent {
        color: var(--success-color);
        font-weight: 600;
    }
    .status-alert {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .status-jadwal-success {
        background-color: #D1FAE5;
        color: #047857;
        border: 1px solid #10B981;
    }
    .status-tugas-success {
        background-color: #D1FAE5;
        color: #047857;
        border: 1px solid #10B981;
    }
    .status-tugas-batal {
        background-color: #FEE2E2;
        color: #B91C1C;
        border: 1px solid #EF4444;
    }

</style>

<?php if (isset($_GET['status']) && $_GET['status'] == 'jadwal_success') { ?>
    <div class="status-alert status-jadwal-success">
        <i class="fas fa-check-circle"></i> Jadwal pengambilan berikutnya berhasil diperbarui!
    </div>
<?php } elseif (isset($_GET['status']) && $_GET['status'] == 'tugas_dibuat') { ?>
    <div class="status-alert status-tugas-success">
        <i class="fas fa-file-signature"></i> Surat Tugas berhasil dibuat. Petugas Kotak Amal kini dapat melanjutkan pengambilan.
    </div>
<?php } elseif (isset($_GET['status']) && $_GET['status'] == 'tugas_dibatalkan') { ?>
    <div class="status-alert status-tugas-batal">
        <i class="fas fa-times-circle"></i> Surat Tugas berhasil dibatalkan.
    </div>
<?php } ?>

<h1 class="dashboard-title">Manajemen Pengambilan Kotak Amal</h1>
<p>Filter data Kotak Amal berdasarkan Jadwal Pengambilan Bulan.</p>

<form method="GET" action="" class="search-form" id="filter-form">
    <div style="display: flex; gap: 10px;">
        <div class="search-control-group-simple">
            
            <div class="month-filter-wrapper">
                <i class="fas fa-filter filter-icon"></i>
                <select name="filter_month" id="filter_month" class="month-select-simple">
                    <option value=""></option> <?php 
                    foreach ($bulan_map as $num => $name) {
                        $selected = ($num == $filter_month) ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    }
                    ?>
                </select>
            </div>
            
            <input type="text" name="search" id="search_input" placeholder="Cari Tahun atau Teks Lain..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input-simple">
            
            <button type="submit" class="btn-search-simple" title="Cari"><i class="fas fa-search"></i> Cari</button>
        </div>

        <?php if (!empty($search_query) || !empty($filter_month)) { ?>
            <a href="dana-kotak-amal.php" class="btn-reset-simple" title="Reset Pencarian">
                <i class="fas fa-times"></i>
                <span>Reset</span>
            </a>
        <?php } ?>
    </div>
</form>

<h2>Daftar Kotak Amal Aktif</h2>
<div class="table-container">
<table class="responsive-table">
    <thead>
        <tr>
            <th>ID KA</th>
            <th>Nama Tempat</th>
            <th>Nama Pemilik (WA)</th>
            <th>Lokasi</th>
            <th>Jadwal Ambil</th>
            <th>Terakhir Ambil</th>
            <th>Status Tugas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_ka->num_rows > 0) { ?>
            <?php while ($row = $result_ka->fetch_assoc()) { 
                $tgl_terakhir_ambil = $row['Tgl_Terakhir_Ambil'] ? format_tanggal_indo($row['Tgl_Terakhir_Ambil']) : 'Belum Pernah';
                $is_recent = (strtotime($row['Tgl_Terakhir_Ambil'] ?? '1970-01-01') >= strtotime('-7 days'));
                
                // --- LOGIKA STATUS SURAT TUGAS AKTIF ---
                $sql_check_st = "SELECT st.ID_Surat_Tugas, u.Nama_User AS Nama_Pembuat 
                                 FROM SuratTugas st
                                 JOIN User u ON st.ID_user = u.Id_user COLLATE utf8mb4_general_ci
                                 WHERE st.ID_KotakAmal = ? AND st.Status_Tugas = 'Aktif'"; 
                $stmt_check_st = $conn->prepare($sql_check_st);
                $stmt_check_st->bind_param("s", $row['ID_KotakAmal']);
                $stmt_check_st->execute();
                $result_check_st = $stmt_check_st->get_result();
                $active_st = $result_check_st->fetch_assoc();
                $stmt_check_st->close();
                
                $active_st_id = $active_st['ID_Surat_Tugas'] ?? null;
                $nama_pembuat = $active_st['Nama_Pembuat'] ?? null;
                $task_is_active = !empty($active_st_id);
                // --- END LOGIKA STATUS SURAT TUGAS AKTIF ---
            ?>
                <tr>
                    <td><?php echo $row['ID_KotakAmal']; ?></td>
                    <td><?php echo $row['Nama_Toko']; ?></td>
                    <td>
                        <?php echo $row['Nama_Pemilik']; ?>
                        <span class="tgl-terakhir">(WA: <?php echo $row['WA_Pemilik'] ?? '-'; ?>)</span>
                    </td>
                    
                    <td class="location-cell">
                        <a href="detail_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn-lokasi">
                            <i class="fas fa-map-marker-alt"></i> Lihat Lokasi
                        </a>
                    </td>
                    
                    <td><?php echo format_tanggal_indo($row['Jadwal_Pengambilan']); ?></td>
                    <td>
                        <span class="tgl-terakhir <?php echo $is_recent ? 'tgl-recent' : ''; ?>">
                            <?php echo $tgl_terakhir_ambil; ?>
                        </span>
                    </td>
                    
                    <td>
                        <?php if ($task_is_active) { ?>
                            <a href="detail_surat_tugas.php?id_tugas=<?php echo $active_st_id; ?>" 
                               class="status-alert status-tugas-success" 
                               style="padding: 4px 8px; margin:0; font-size: 0.8em; display: inline-block; text-decoration: none;"
                               title="Dibuat oleh: <?php echo htmlspecialchars($nama_pembuat); ?>">
                                Tugas Aktif <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php } else { ?>
                            -
                        <?php } ?>
                    </td>
                    
                    <td data-label="Aksi">
                        <?php 
                        if ($jabatan_session == 'Pimpinan' || $jabatan_session == 'Kepala LKSA') {
                            // --- VIEW ATASAN: Create/Cancel Task ---
                            if ($task_is_active) { ?>
                                <a href="proses_batal_surat_tugas.php?id_tugas=<?php echo $active_st_id; ?>" class="btn btn-danger btn-action-icon" title="Batalkan Tugas">
                                    <i class="fas fa-times-circle"></i> Batalkan
                                </a>
                            <?php } else { ?>
                                <a href="proses_buat_surat_tugas.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-primary btn-action-icon" style="background-color: #0c9c6f;" title="Buat Surat Tugas">
                                    <i class="fas fa-file-signature"></i> Buat Surat Tugas
                                </a>
                            <?php }
                        } elseif ($jabatan_session == 'Petugas Kotak Amal') {
                            // --- VIEW PETUGAS: Claim Task ---
                            if ($task_is_active) { ?>
                                <a href="catat_pengambilan_ka.php?id=<?php echo $row['ID_KotakAmal']; ?>&id_tugas=<?php echo $active_st_id; ?>" class="btn btn-success btn-action-icon" title="Mulai Pengambilan">
                                    <i class="fas fa-money-bill-wave"></i> Ambil
                                </a>
                            <?php } else { ?>
                                <span class="btn btn-cancel btn-action-icon" style="opacity: 0.6; cursor: default;" title="Menunggu Atasan membuat tugas">Menunggu Tugas</span>
                            <?php }
                        } else { ?>
                            <span class="btn btn-cancel btn-action-icon" style="opacity: 0.6; cursor: default;">Akses Dibatasi</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="8" class="no-data">Tidak ada Kotak Amal aktif yang ditemukan.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthSelect = document.getElementById('filter_month');
        const searchInput = document.getElementById('search_input');
        const filterForm = document.getElementById('filter-form');
        
        const initialMonth = monthSelect.value;
        if (initialMonth !== "" && searchInput.value === "") {
             const selectedText = monthSelect.options[monthSelect.selectedIndex].text;
             searchInput.value = selectedText;
        }

        monthSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            const selectedText = this.options[this.selectedIndex].text;
            
            if (selectedValue !== "") {
                searchInput.value = selectedText;
            } else {
                searchInput.value = '';
            }
            
            filterForm.submit();
        });
    });
</script>

<?php
$stmt->close(); 
include '../includes/footer.php';
$conn->close();
?>