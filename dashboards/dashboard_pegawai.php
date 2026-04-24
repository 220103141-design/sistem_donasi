<?php
include 'config/database.php';
// Memastikan helpers tersedia
include 'config/db_helpers.php';

$id_user = $_SESSION['id_user'];
$id_lksa = $_SESSION['id_lksa'];

// 1. Query untuk Total Sumbangan yang di-Input oleh Pegawai saat ini
$sql_pegawai = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan WHERE ID_user = ?";
$total_sumbangan_pegawai = fetch_single_param_value($conn, $sql_pegawai, $id_user);

// 2. Query baru untuk Total Sumbangan KESELURUHAN LKSA
$sql_lksa = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan WHERE Id_lksa = ?";
$total_sumbangan_lksa = fetch_single_param_value($conn, $sql_lksa, $id_lksa);

// LOGIC BARU UNTUK SIDEBAR
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

// Total donatur yang didaftarkan oleh pegawai ini
$sql_donatur_didaftarkan = "SELECT COUNT(*) AS total FROM Donatur WHERE ID_user = ?";
$total_donatur_didaftarkan = fetch_single_param_value($conn, $sql_donatur_didaftarkan, $id_user);

// Menetapkan variabel $sidebar_stats untuk digunakan di header.php (Mempertahankan total input pegawai)
$sidebar_stats = '
<div class="sidebar-stats-card card-donatur" style="border-left-color: #10B981;">
    <h4>Total Donatur Didaftarkan</h4>
    <p>' . number_format($total_donatur_didaftarkan) . '</p>
</div>

<div class="sidebar-stats-card card-sumbangan" style="border-left-color: #7C3AED;">
    <h4>Total Sumbangan ZIS Diinput</h4>
    <p>Rp ' . number_format($total_sumbangan_pegawai) . '</p>
</div>
';

include 'includes/header.php'; // <-- LOKASI BARU
?>
<p>Fokus Anda adalah mengelola donasi Zakat, Infaq, dan Sedekah.</p>

<h2>Ringkasan Donasi ZIS LKSA Anda</h2>
<div class="stats-grid">
    <div class="stats-card card-lksa" style="border-color: #06B6D4;">
        <i class="fas fa-building"></i>
        <div class="stats-card-content">
            <h3>Total Sumbangan Keseluruhan LKSA</h3>
            <span class="value" style="color: #06B6D4;">Rp <?php echo number_format($total_sumbangan_lksa); ?></span>
        </div>
    </div>
</div>

<h2>Ringkasan Sumbangan yang Anda Input</h2>
<div class="stats-grid">
    <div class="stats-card card-sumbangan">
        <i class="fas fa-sack-dollar"></i>
        <div class="stats-card-content">
            <h3>Total Sumbangan Pribadi</h3>
            <span class="value">Rp <?php echo number_format($total_sumbangan_pegawai); ?></span>
        </div>
    </div>
    
    <div class="stats-card card-donatur">
        <i class="fas fa-user-plus"></i>
        <div class="stats-card-content">
            <h3>Total Donatur Didaftarkan</h3>
            <span class="value"> <?php echo number_format($total_donatur_didaftarkan); ?></span>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
$conn->close();
?>