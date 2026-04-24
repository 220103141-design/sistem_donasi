<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];
$jabatan_session = $_SESSION['jabatan']; 

// --- Helper functions for formatting ---
function format_tanggal_indo($date_string) {
    if (!$date_string || $date_string === '0000-00-00') return '-';
    
    $timestamp = strtotime($date_string);
    
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

    return $day . ' ' . $month_id . ' ' . $year;
}
// ----------------------------------------------------

// LOGIKA MAPPING BULAN UNTUK DROPDOWN
$bulan_map = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// --- LOGIKA FILTER RIWAYAT PENGAMBILAN ---

// Ambil input filter riwayat
$hist_month = $_GET['hist_month'] ?? date('m'); // Default ke bulan saat ini
$hist_year = $_GET['hist_year'] ?? date('Y');
$filter_mode_hist = $_GET['filter_mode_hist'] ?? 'month'; // Default ke 'month'

$sql_filter_dana_ka_hist = "";
$params_hist = [];
$types_hist = "";
$period_display_hist = "";
$limit_hist = ""; 

$where_conditions_hist = [];

// 1. Filter LKSA (Selalu ada jika bukan Pimpinan Pusat)
if ($_SESSION['jabatan'] != 'Pimpinan') {
    $where_conditions_hist[] = " dka.Id_lksa = ?";
    $params_hist[] = $id_lksa;
    $types_hist .= "s";
}

// 2. Filter Waktu (Bulan/Tahun/Semua)
if ($filter_mode_hist == 'month' && !empty($hist_month)) {
    $month_name_id = $bulan_map[$hist_month] ?? $hist_month;
    $period_display_hist = "Bulan " . $month_name_id . " " . $hist_year;
    $where_conditions_hist[] = " MONTH(dka.Tgl_Ambil) = ? AND YEAR(dka.Tgl_Ambil) = ?";
    $params_hist[] = $hist_month;
    $params_hist[] = $hist_year;
    $types_hist .= "ss";
} elseif ($filter_mode_hist == 'year') {
    $period_display_hist = "Tahun " . $hist_year;
    $where_conditions_hist[] = " YEAR(dka.Tgl_Ambil) = ?";
    $params_hist[] = $hist_year;
    $types_hist .= "s";
} elseif ($filter_mode_hist == 'all') {
    $period_display_hist = "Keseluruhan Waktu";
}


// --- Query untuk Riwayat Pengambilan ---
$sql_history = "SELECT dka.*, ka.Nama_Toko, u.Nama_User
                FROM Dana_KotakAmal dka
                LEFT JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
                LEFT JOIN User u ON dka.Id_user = u.Id_user";
                
// Apply WHERE clause
if (!empty($where_conditions_hist)) {
    $sql_history .= " WHERE " . implode(" AND ", $where_conditions_hist);
}

$sql_history .= " ORDER BY dka.Tgl_Ambil DESC " . $limit_hist;

$stmt_history = $conn->prepare($sql_history);

if (!empty($params_hist)) {
    $stmt_history->bind_param($types_hist, ...$params_hist);
}

$stmt_history->execute();
$result_history = $stmt_history->get_result();

// --- LOGIKA LINK EXPORT DENGAN FILTER (BARU) ---
$export_link = "export_dana_kotak_amal.php?filter_mode_hist=" . urlencode($filter_mode_hist) . "&hist_month=" . urlencode($hist_month) . "&hist_year=" . urlencode($hist_year);

?>
<style>
    :root {
        --primary-color: #1F2937; 
        --secondary-color: #10B981; /* Emerald Green */
        --accent-kotak-amal: #0c9c6f; /* Orange -> Medium Emerald */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --border-color: #E5E7EB;
        --bg-light: #F9FAFB;
        --cancel-color: #6B7280; /* Neutral Gray */
    }
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
    .btn-export {
        background-color: var(--success-color);
        color: white;
        padding: 8px 15px;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-export:hover {
        background-color: #047857; /* Darker Emerald on hover */
        transform: translateY(-2px);
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
</style>

<h1 class="dashboard-title"><i class="fas fa-history" style="color: var(--accent-kotak-amal);"></i> Riwayat Pengambilan Kotak Amal</h1>
<p>Daftar lengkap transaksi pengambilan dana kotak amal di LKSA Anda. Gunakan filter untuk memilih periode data yang ditampilkan dan diekspor.</p>

<form method="GET" action="" class="search-form" id="history-filter-form">
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; justify-content: space-between;">
        <div style="display: flex; gap: 15px; align-items: center; justify-content: flex-start; flex-wrap: wrap; background-color: #f8f8f8; padding: 15px; border-radius: 10px; border: 1px solid var(--border-color);">
            <label for="filter_mode_hist" style="font-weight: 600; font-size: 0.9em;">Pilih Filter Riwayat:</label>
            <select name="filter_mode_hist" id="filter_mode_hist">
                <option value="month" <?php echo ($filter_mode_hist == 'month') ? 'selected' : ''; ?>>Per Bulan</option>
                <option value="year" <?php echo ($filter_mode_hist == 'year') ? 'selected' : ''; ?>>Per Tahun</option>
                <option value="all" <?php echo ($filter_mode_hist == 'all') ? 'selected' : ''; ?>>Keseluruhan Waktu</option>
            </select>
            
            <div id="hist_month_selector" style="display: <?php echo ($filter_mode_hist == 'month') ? 'flex' : 'none'; ?>; gap: 10px; align-items: center;">
                <label for="hist_month" style="font-size: 0.9em;">Bulan:</label>
                <select name="hist_month" id="hist_month">
                    <?php
                    foreach ($bulan_map as $num => $name) {
                        $selected = ($num == $hist_month) ? 'selected' : '';
                        echo "<option value='{$num}' $selected>{$name}</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="hist_year_selector" style="display: <?php echo (in_array($filter_mode_hist, ['month', 'year'])) ? 'flex' : 'none'; ?>; gap: 10px; align-items: center;">
                <label for="hist_year" style="font-size: 0.9em;">Tahun:</label>
                <select name="hist_year" id="hist_year">
                    <?php
                    $current_year = date('Y');
                    for ($i = $current_year; $i >= $current_year - 5; $i--) {
                        $selected = ($i == $hist_year) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="background-color: var(--accent-kotak-amal);"><i class="fas fa-filter"></i> Terapkan</button>
            
        </div>
        
        <a href="<?php echo $export_link; ?>" class="btn btn-export" target="_blank">
            <i class="fas fa-file-export"></i> Export Data (<?php echo htmlspecialchars($period_display_hist); ?>)
        </a>
    </div>
</form>

<h3 style="margin-top: 10px;">Data Pengambilan (<?php echo htmlspecialchars($period_display_hist); ?>)</h3>
<div class="table-container">
<table class="responsive-table">
    <thead>
        <tr>
            <th>ID Kwitansi</th>
            <th>Nama Tempat</th>
            <th>Jumlah Uang</th>
            <th>Tanggal Ambil</th>
            <th>Petugas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_history->num_rows > 0) { ?>
            <?php while ($row_hist = $result_history->fetch_assoc()) { ?>
                <tr>
                    <td data-label="ID Kwitansi"><?php echo $row_hist['ID_Kwitansi_KA']; ?></td>
                    <td data-label="Nama Tempat"><?php echo $row_hist['Nama_Toko']; ?></td>
                    <td data-label="Jumlah Uang">Rp <?php echo number_format($row_hist['JmlUang']); ?></td>
                    <td data-label="Tanggal Ambil"><?php echo $row_hist['Tgl_Ambil']; ?></td>
                    <td data-label="Petugas"><?php echo $row_hist['Nama_User']; ?></td>
                    <td data-label="Aksi">
                        <a href="edit_dana_kotak_amal.php?id=<?php echo $row_hist['ID_Kwitansi_KA']; ?>" class="btn btn-primary btn-action-icon" style="background-color: #6B7280;" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="hapus_dana_kotak_amal.php?id=<?php echo $row_hist['ID_Kwitansi_KA']; ?>" class="btn btn-danger btn-action-icon" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data pengambilan ini?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="6" class="no-data">Tidak ada riwayat pengambilan dana kotak amal yang ditemukan untuk filter ini.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</div>

<script>
    // Fungsi untuk mengelola tampilan filter Riwayat (Hanya visibilitas)
    function toggleHistoryFilter(mode) {
        const monthSelector = document.getElementById('hist_month_selector');
        const yearSelector = document.getElementById('hist_year_selector');
        
        if (mode === 'month') {
            monthSelector.style.display = 'flex';
            yearSelector.style.display = 'flex';
        } else if (mode === 'year') {
            monthSelector.style.display = 'none';
            yearSelector.style.display = 'flex';
        } else {
             // 'all'
             monthSelector.style.display = 'none';
             yearSelector.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const historyFilterSelect = document.getElementById('filter_mode_hist');
        
        // Inisialisasi visibilitas saat DOM dimuat
        toggleHistoryFilter(historyFilterSelect.value);
        
        // Event listener untuk perubahan dropdown (hanya visibilitas)
        historyFilterSelect.addEventListener('change', function() {
            // Ganti visibilitas
            toggleHistoryFilter(this.value);
            // Submit otomatis jika mode adalah 'all'
            if (this.value === 'all') {
                document.getElementById('history-filter-form').submit();
            }
        });
        
        // Submit otomatis jika ada perubahan di Bulan/Tahun saat mode 'month' atau 'year' aktif.
        const histMonthSelect = document.getElementById('hist_month');
        const histYearSelect = document.getElementById('hist_year');
        const historyFilterForm = document.getElementById('history-filter-form');
        
        if (histMonthSelect) {
            histMonthSelect.addEventListener('change', function() {
                 if (historyFilterSelect.value === 'month') {
                     historyFilterForm.submit();
                 }
            });
        }
        
        if (histYearSelect) {
            histYearSelect.addEventListener('change', function() {
                 if (historyFilterSelect.value === 'month' || historyFilterSelect.value === 'year') {
                     historyFilterForm.submit();
                 }
            });
        }
    });
</script>

<?php
$stmt_history->close(); 
include '../includes/footer.php';
$conn->close();
?>