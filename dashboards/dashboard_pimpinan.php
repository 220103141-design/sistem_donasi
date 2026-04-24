<?php
include 'config/database.php';
// Memastikan helpers tersedia
include 'config/db_helpers.php'; 

// Menentukan bulan dan tahun saat ini sebagai default
$selected_month = isset($_POST['bulan']) ? $_POST['bulan'] : date('m');
$selected_year = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');

// --- Menggunakan fungsi helper untuk kueri sederhana dan kueri berparameter ---

// Kueri dengan 2 parameter (bulan dan tahun) - Tetap menggunakan prepared statement lokal karena 2 parameter
$sql_bulan = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan WHERE MONTH(Tgl) = ? AND YEAR(Tgl) = ?";
$stmt_bulan = $conn->prepare($sql_bulan);
$stmt_bulan->bind_param("ss", $selected_month, $selected_year);
$stmt_bulan->execute();
$total_sumbangan_bulan_ini = $stmt_bulan->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_bulan->close();


// Kueri dengan 1 parameter (tahun) - Tetap menggunakan prepared statement lokal
$sql_tahun = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan WHERE YEAR(Tgl) = ?";
$stmt_tahun = $conn->prepare($sql_tahun);
$stmt_tahun->bind_param("s", $selected_year);
$stmt_tahun->execute();
$total_sumbangan_tahun_ini = $stmt_tahun->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_tahun->close();


// Menghitung total donatur
$total_donatur = fetch_simple_value($conn, "SELECT COUNT(*) AS total FROM Donatur");

// Menghitung total kotak amal
$total_kotak_amal = fetch_simple_value($conn, "SELECT COUNT(*) AS total FROM KotakAmal");

// Menghitung total sumbangan ZIS dari Donatur
$total_sumbangan_donatur = fetch_simple_value($conn, "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan");

// Menghitung total dana yang diambil dari Kotak Amal
$total_dana_kotak_amal = fetch_simple_value($conn, "SELECT SUM(JmlUang) AS total FROM Dana_KotakAmal");

// Menghitung total sumbangan keseluruhan (Donatur + Kotak Amal)
$total_sumbangan = $total_sumbangan_donatur + $total_dana_kotak_amal;

// LOGIC BARU UNTUK SIDEBAR
$id_user = $_SESSION['id_user'] ?? '';
$user_info_sql = "SELECT Nama_User, Foto, Jabatan FROM User WHERE Id_user = ?";
$stmt_user_info = $conn->prepare($user_info_sql);
$stmt_user_info->bind_param("s", $id_user);
$stmt_user_info->execute();
$user_info = $stmt_user_info->get_result()->fetch_assoc();
$stmt_user_info->close();

$nama_user = $user_info['Nama_User'] ?? 'Pengguna';
$foto_user = $user_info['Foto'] ?? '';
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/"; // Definisikan $base_url
$foto_path = $foto_user ? $base_url . 'assets/img/' . $foto_user : $base_url . 'assets/img/yayasan.png'; // Use Yayasan logo as default if none

$sidebar_total_lksa = fetch_simple_value($conn, "SELECT COUNT(*) AS total FROM LKSA");
$sidebar_total_user = fetch_simple_value($conn, "SELECT COUNT(*) AS total FROM User");

// Menetapkan variabel $sidebar_stats untuk digunakan di header.php
// Menggunakan warna baru: #14B8A6 (Teal/Lksa) dan #1F2937 (Dark Navy/User)
$sidebar_stats = '
<div class="sidebar-stats-card card-lksa" style="border-left-color: #14B8A6;">
    <h4>Total LKSA Terdaftar</h4>
    <p>' . number_format($sidebar_total_lksa) . '</p>
</div>
<div class="sidebar-stats-card card-user" style="border-left-color: #1F2937;">
    <h4>Total Pengguna Sistem</h4>
    <p>' . number_format($sidebar_total_user) . '</p>
</div>
'; // Menghapus Total Sumbangan ZIS Global dan Total Dana Kotak Amal Global

include 'includes/header.php'; // <-- LOKASI BARU
?>
<p>Anda memiliki akses penuh ke seluruh data dan fitur di sistem.</p>
<h2>Ringkasan Statistik Global</h2>
<div class="stats-grid">
    <div class="stats-card card-donatur">
        <i class="fas fa-hand-holding-heart"></i>
        <div class="stats-card-content">
            <h3>Jumlah Donatur</h3>
            <span class="value"><?php echo number_format($total_donatur); ?></span>
        </div>
    </div>
    <div class="stats-card card-user">
        <i class="fas fa-users"></i>
        <div class="stats-card-content">
            <h3>Total Pengguna Sistem</h3>
            <span class="value"><?php echo number_format($sidebar_total_user); ?></span>
        </div>
    </div>
    <div class="stats-card card-sumbangan">
        <i class="fas fa-sack-dollar"></i>
        <div class="stats-card-content">
            <h3>Total Sumbangan Donatur</h3>
            <span class="value">Rp <?php echo number_format($total_sumbangan_donatur); ?></span>
        </div>
    </div>
    <div class="stats-card card-kotak-amal">
        <i class="fas fa-box"></i>
        <div class="stats-card-content">
            <h3>Total Kotak Amal</h3>
            <span class="value"><?php echo number_format($total_kotak_amal); ?></span>
        </div>
    </div>
</div>

<h2>Sumbangan Berdasarkan Periode</h2>
<form method="POST" action="">
    <div style="display: flex; gap: 10px; margin-bottom: 20px; justify-content: flex-end; align-items: center;">
        <label for="bulan">Pilih Bulan:</label>
        <select name="bulan" id="bulan">
            <?php
            $bulan_indonesia = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];
            foreach ($bulan_indonesia as $num => $name) {
                $selected = ($num == $selected_month) ? 'selected' : '';
                echo "<option value='$num' $selected>$name</option>";
            }
            ?>
        </select>

        <label for="tahun">Pilih Tahun:</label>
        <select name="tahun" id="tahun">
            <?php
            $current_year = date('Y');
            for ($i = $current_year; $i >= $current_year - 5; $i--) {
                $selected = ($i == $selected_year) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            }
            ?>
        </select>
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="stats-card card-sumbangan">
        <i class="fas fa-calendar-alt"></i>
        <div class="stats-card-content">
            <h3>Sumbangan Bulan Terpilih (<?php echo $bulan_indonesia[$selected_month]; ?>)</h3>
            <span class="value">Rp <?php echo number_format($total_sumbangan_bulan_ini); ?></span>
        </div>
    </div>
    <div class="stats-card card-sumbangan">
        <i class="fas fa-chart-line"></i>
        <div class="stats-card-content">
            <h3>Sumbangan Tahun Terpilih (<?php echo $selected_year; ?>)</h3>
            <span class="value">Rp <?php echo number_format($total_sumbangan_tahun_ini); ?></span>
        </div>
    </div>
</div>

<div class="stats-card-total-large">
    <i class="fas fa-donate"></i>
    <h3>Total Sumbangan Keseluruhan (Donatur + Kotak Amal)</h3>
    <span class="value">Rp <?php echo number_format($total_sumbangan); ?></span>
</div>

<?php
include 'includes/footer.php';
$conn->close();
?>