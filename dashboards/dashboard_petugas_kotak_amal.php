<?php
include 'config/database.php';
// Memastikan helpers tersedia
include 'config/db_helpers.php';

$id_user = $_SESSION['id_user'];
$id_lksa = $_SESSION['id_lksa'];

// Query untuk mendapatkan total uang yang diambil
$sql_uang = "SELECT SUM(JmlUang) AS total FROM Dana_KotakAmal WHERE ID_user = ?";
$total_uang_diambil = fetch_single_param_value($conn, $sql_uang, $id_user);

// Query untuk mendapatkan total kotak amal yang dikelola (di LKSA)
$sql_kotak = "SELECT COUNT(*) AS total FROM KotakAmal WHERE ID_LKSA = ?";
$total_kotak_amal_dikelola = fetch_single_param_value($conn, $sql_kotak, $id_lksa);

// Ambil jadwal pengambilan untuk hari ini dengan status pengambilan
$current_day = date('l');
$hari_indonesia = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hari_ini = $hari_indonesia[$current_day];

// Kueri SQL yang diperbarui untuk menghindari duplikasi - Kueri Jadwal tetap di sini karena butuh 2 parameter dan kompleksitas JOIN/GROUP BY
$sql_jadwal = "SELECT ka.ID_KotakAmal, ka.Nama_Toko, ka.Alamat_Toko, MAX(dka.ID_Kwitansi_KA) AS is_collected_today
               FROM KotakAmal ka
               LEFT JOIN Dana_KotakAmal dka ON ka.ID_KotakAmal = dka.ID_KotakAmal AND dka.Tgl_Ambil = CURDATE()
               WHERE ka.ID_LKSA = ? AND FIND_IN_SET(?, ka.Jadwal_Pengambilan)
               GROUP BY ka.ID_KotakAmal
               ORDER BY ka.Nama_Toko ASC";
$stmt = $conn->prepare($sql_jadwal);
$stmt->bind_param("ss", $id_lksa, $hari_ini);
$stmt->execute();
$result_jadwal = $stmt->get_result();
$stmt->close();

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

// Menetapkan variabel $sidebar_stats untuk digunakan di header.php
$sidebar_stats = '
<div class="sidebar-stats-card card-kotak-amal" style="border-left-color: #F59E0B;">
    <h4>Total Kotak Amal LKSA</h4>
    <p>' . number_format($total_kotak_amal_dikelola) . '</p>
</div>

<div class="sidebar-stats-card card-kotak-amal" style="border-left-color: #F59E0B;">
    <h4>Total Dana Diambil Sendiri</h4>
    <p>Rp ' . number_format($total_uang_diambil) . '</p>
</div>
';

include 'includes/header.php'; // <-- LOKASI BARU
?>
<style>
    /* Style tambahan untuk tombol ikon yang sederhana */
    .btn-action-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border-radius: 5px;
        font-size: 0.9em;
    }
</style>
<p>Anda dapat mengelola data kotak amal dan pengambilan dananya.</p>
<h2>Ringkasan Kotak Amal</h2>
<div class="stats-grid">
    <div class="stats-card card-sumbangan">
        <i class="fas fa-money-bill-wave"></i>
        <h3>Total Uang Diambil</h3>
        <span class="value">Rp <?php echo number_format($total_uang_diambil); ?></span>
    </div>
    <div class="stats-card card-kotak-amal">
        <i class="fas fa-box"></i>
        <h3>Kotak Amal Dikelola</h3>
        <span class="value"><?php echo $total_kotak_amal_dikelola; ?></span>
    </div>
</div>

<h2>Jadwal Pengambilan Hari Ini (<?php echo $hari_ini; ?>)</h2>
<?php if ($result_jadwal->num_rows > 0) { ?>
    <table>
        <thead>
            <tr>
                <th>Nama Toko</th>
                <th>Alamat Toko</th>
                <th>Status</th>
                <th>Profil & Lokasi</th> <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result_jadwal->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Nama_Toko']); ?></td>
                    <td><?php echo htmlspecialchars($row['Alamat_Toko']); ?></td>
                    <td>
                        <?php if ($row['is_collected_today']) { ?>
                            <span style="color: green; font-weight: bold;">Sudah Diambil</span>
                        <?php } else { ?>
                            <span style="color: orange; font-weight: bold;">Belum Diambil</span>
                        <?php } ?> </td>
                    <td>
                        <a href="pages/detail_kotak_amal.php?id=<?php echo htmlspecialchars($row['ID_KotakAmal']); ?>" class="btn btn-primary btn-action-icon" title="Lihat Profil & Lokasi"><i class="fas fa-map-marked-alt"></i></a>
                    </td>
                    <td>
                        <?php if ($row['is_collected_today']) { ?>
                            <span style="color: #6B7280; font-weight: bold; font-size: 0.9em;">Selesai</span>
                        <?php } else { ?>
                            <a href="pages/dana-kotak-amal.php" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.9em; background-color: #10B981;">Lanjutkan Tugas</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } else { ?>
    <p>Tidak ada jadwal pengambilan untuk hari ini.</p>
<?php } ?>
<?php
include 'includes/footer.php';
$conn->close();
?>