<?php
include 'config/database.php';
// Memastikan helpers tersedia
include 'config/db_helpers.php';

$id_lksa = $_SESSION['id_lksa'];

// --- Menentukan periode filter ---
$selected_month = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$selected_year = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// Definisikan operator perbandingan untuk bulan/tahun
$month_condition = " AND MONTH(Tgl) = ? AND YEAR(Tgl) = ?";
$month_condition_ka = " AND MONTH(Tgl_Ambil) = ? AND YEAR(Tgl_Ambil) = ?";
$year_condition = " AND YEAR(Tgl) = ?";
$year_condition_ka = " AND YEAR(Tgl_Ambil) = ?";

// Tentukan mode filter
$filter_mode = $_POST['filter_mode'] ?? 'month'; // 'month', 'year', 'all'

$sql_params = [];
$sql_types = "";
$period_display = "";

// Helper untuk terjemahan bulan
$bulan_indonesia = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', 
    '04' => 'April', '05' => 'Mei', '06' => 'Juni', 
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September', 
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

switch ($filter_mode) {
    case 'month':
        $month_name_id = $bulan_indonesia[$selected_month] ?? $selected_month;
        $period_display = "Bulan " . $month_name_id . " " . $selected_year;
        $sql_filter_sumbangan = $month_condition;
        $sql_filter_dana_ka = $month_condition_ka;
        $sql_params = [$selected_month, $selected_year];
        $sql_types = "ss";
        break;
    case 'year':
        $period_display = "Tahun " . $selected_year;
        $sql_filter_sumbangan = $year_condition;
        $sql_filter_dana_ka = $year_condition_ka;
        $sql_params = [$selected_year];
        $sql_types = "s";
        break;
    case 'all':
    default:
        $period_display = "Keseluruhan Waktu (All Time)";
        $sql_filter_sumbangan = "";
        $sql_filter_dana_ka = "";
        $sql_params = [];
        $sql_types = "";
        break;
}

// --- Kueri Berdasarkan Periode Filter ---

// 1. Total Sumbangan ZIS LKSA (Filterable)
$sql_sumbangan = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan WHERE ID_LKSA = ?";
$sql_sumbangan .= $sql_filter_sumbangan;

$stmt_sumbangan = $conn->prepare($sql_sumbangan);
$params_sumbangan = array_merge([$id_lksa], $sql_params);
$types_sumbangan = "s" . $sql_types;

if ($stmt_sumbangan) {
    $stmt_sumbangan->bind_param($types_sumbangan, ...$params_sumbangan);
    $stmt_sumbangan->execute();
    $total_sumbangan_lksa_filtered = $stmt_sumbangan->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_sumbangan->close();
} else {
    $total_sumbangan_lksa_filtered = 0;
}


// 2. Total Dana Kotak Amal LKSA (Filterable)
$sql_dana_ka = "SELECT SUM(JmlUang) AS total FROM Dana_KotakAmal WHERE Id_lksa = ?";
$sql_dana_ka .= $sql_filter_dana_ka;

$stmt_dana_ka = $conn->prepare($sql_dana_ka);
$params_dana_ka = array_merge([$id_lksa], $sql_params);
$types_dana_ka = "s" . $sql_types;

if ($stmt_dana_ka) {
    $stmt_dana_ka->bind_param($types_dana_ka, ...$params_dana_ka);
    $stmt_dana_ka->execute();
    $total_dana_kotak_amal_lksa_filtered = $stmt_dana_ka->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_dana_ka->close();
} else {
    $total_dana_kotak_amal_lksa_filtered = 0;
}


// --- Kueri Statistik Tetap (All Time/Not Filterable on this dashboard view) ---

// 3. Total Pegawai & Petugas KA (Operasional) - ALL TIME
$sql_pegawai = "SELECT COUNT(*) AS total FROM User WHERE Id_lksa = ? AND Jabatan IN ('Pegawai', 'Petugas Kotak Amal')";
$total_pegawai_operasional = fetch_single_param_value($conn, $sql_pegawai, $id_lksa);

// 4. Total Donatur LKSA - ALL TIME
$sql_donatur = "SELECT COUNT(*) AS total FROM Donatur WHERE ID_LKSA = ?";
$total_donatur_lksa = fetch_single_param_value($conn, $sql_donatur, $id_lksa);

// 5. Jumlah Donasi ZIS Menunggu Verifikasi - ALL TIME
$sql_verifikasi = "SELECT COUNT(*) AS total FROM Sumbangan WHERE ID_LKSA = ? AND Status_Verifikasi != 'Terverifikasi'";
$total_menunggu_verifikasi = fetch_single_param_value($conn, $sql_verifikasi, $id_lksa);


// LOGIC UNTUK SIDEBAR
$id_user = $_SESSION['id_user'] ?? '';
$user_info_sql = "SELECT Nama_User, Foto FROM User WHERE Id_user = ?";
$stmt_user_info = $conn->prepare($user_info_sql);
$stmt_user_info->bind_param("s", $id_user);
$stmt_user_info->execute();
$user_info = $stmt_user_info->get_result()->fetch_assoc();
$stmt_user_info->close();

$nama_user = $user_info['Nama_User'] ?? 'Pengguna';
$foto_user = $user_info['Foto'] ?? '';
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/"; // Definisikan $base_url
$foto_path = $foto_user ? $base_url . 'assets/img/' . $foto_user : $base_url . 'assets/img/yayasan.png'; // Use Yayasan logo as default if none

// Total Pegawai dan Petugas KA (Untuk Sidebar)
$sidebar_total_pegawai = $total_pegawai_operasional; 
$sidebar_total_donatur_lksa = $total_donatur_lksa; 

// MENETAPKAN $sidebar_stats MENJADI STRING KOSONG (SESUAI PERMINTAAN USER)
$sidebar_stats = '';
?>

<style>
/* Override CSS untuk membuat tampilan lebih compact */
.dashboard-title {
    font-size: 1.4em !important; /* Dikecilkan dari 1.6em */
}
.content p {
    font-size: 0.9em; /* Dikecilkan untuk paragraf pengantar */
}
.stats-card h3 {
    font-size: 0.85em !important; /* Dikecilkan untuk judul kartu */
}
.stats-card .value {
    font-size: 1.6em !important; /* Dikecilkan untuk angka besar */
}
/* Memastikan elemen filter juga terlihat compact */
#filter_mode, #bulan, #tahun, form label {
    font-size: 0.9em;
}
.form-grid {
    gap: 10px; /* Merapatkan grid */
}
</style>

<?php
include 'includes/header.php'; 
?>

<p>Selamat datang, **<?php echo htmlspecialchars($nama_user); ?>** (Kepala LKSA <?php echo htmlspecialchars($id_lksa); ?>). Anda mengelola operasional, verifikasi, dan keuangan cabang ini.</p>

<h2 class="dashboard-title"><i class="fas fa-filter"></i> Filter Data Keuangan</h2>
<form method="POST" action="" style="margin-bottom: 30px; background-color: #f8f8f8; padding: 20px; border-radius: 10px;">
    <div style="display: flex; gap: 15px; align-items: center; justify-content: flex-start; flex-wrap: wrap;">
        
        <label for="filter_mode" style="font-weight: 600;">Pilih Filter:</label>
        <select name="filter_mode" id="filter_mode" onchange="this.form.submit()">
            <option value="month" <?php echo ($filter_mode == 'month') ? 'selected' : ''; ?>>Per Bulan</option>
            <option value="year" <?php echo ($filter_mode == 'year') ? 'selected' : ''; ?>>Per Tahun</option>
            <option value="all" <?php echo ($filter_mode == 'all') ? 'selected' : ''; ?>>Keseluruhan Waktu</option>
        </select>
        
        <div id="month_selector" style="display: <?php echo ($filter_mode == 'month') ? 'flex' : 'none'; ?>; gap: 10px; align-items: center;">
            <label for="bulan">Bulan:</label>
            <select name="bulan" id="bulan">
                <?php
                // Menggunakan array terjemahan bulan yang sudah didefinisikan di atas
                foreach ($bulan_indonesia as $num => $name) {
                    $selected = ($num == $selected_month) ? 'selected' : '';
                    echo "<option value='{$num}' $selected>{$name}</option>";
                }
                ?>
            </select>
        </div>

        <div id="year_selector" style="display: <?php echo (in_array($filter_mode, ['month', 'year'])) ? 'flex' : 'none'; ?>; gap: 10px; align-items: center;">
            <label for="tahun">Tahun:</label>
            <select name="tahun" id="tahun">
                <?php
                $current_year = date('Y');
                for ($i = $current_year; $i >= $current_year - 5; $i--) {
                    $selected = ($i == $selected_year) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> Tampilkan Data</button>
    </div>
</form>

<script>
document.getElementById('filter_mode').addEventListener('change', function() {
    const mode = this.value;
    const monthSelector = document.getElementById('month_selector');
    const yearSelector = document.getElementById('year_selector');

    if (mode === 'month') {
        monthSelector.style.display = 'flex';
        yearSelector.style.display = 'flex';
    } else if (mode === 'year') {
        monthSelector.style.display = 'none';
        yearSelector.style.display = 'flex';
    } else {
        monthSelector.style.display = 'none';
        yearSelector.style.display = 'none';
    }
});
</script>

<h2 class="dashboard-title"><i class="fas fa-wallet"></i> Keuangan LKSA (<?php echo htmlspecialchars($period_display); ?>)</h2>
<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="stats-card card-sumbangan">
        <i class="fas fa-sack-dollar"></i>
        <div class="stats-card-content">
            <h3>Total Sumbangan ZIS</h3>
            <span class="value">Rp <?php echo number_format($total_sumbangan_lksa_filtered); ?></span>
        </div>
    </div>
    <div class="stats-card card-kotak-amal">
        <i class="fas fa-box-open"></i>
        <div class="stats-card-content">
            <h3>Total Dana Kotak Amal</h3>
            <span class="value">Rp <?php echo number_format($total_dana_kotak_amal_lksa_filtered); ?></span>
        </div>
    </div>
</div>

<h2 class="dashboard-title"><i class="fas fa-check-double"></i> Status Manajerial & Operasional (All Time)</h2>
<div class="stats-grid" style="grid-template-columns: 1fr 1fr 1fr;">
    <div class="stats-card card-total" style="border-color: #EF4444;">
        <i class="fas fa-exclamation-triangle" style="color: #EF4444;"></i>
        <div class="stats-card-content">
            <h3>Donasi Menunggu Verifikasi</h3>
            <span class="value" style="color: #EF4444;"><?php echo number_format($total_menunggu_verifikasi); ?></span>
        </div>
    </div>
    <div class="stats-card card-donatur">
        <i class="fas fa-hand-holding-heart"></i>
        <div class="stats-card-content">
            <h3>Total Donatur Terdaftar</h3>
            <span class="value"><?php echo number_format($total_donatur_lksa); ?></span>
        </div>
    </div>
    <div class="stats-card card-user">
        <i class="fas fa-user-tie"></i>
        <div class="stats-card-content">
            <h3>Total Staf Operasional</h3>
            <span class="value"><?php echo number_format($total_pegawai_operasional); ?></span>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
$conn->close();
?>