<?php
// Pastikan file ini di-include dari index.php
include 'includes/header.php';
include 'config/database.php';

$total_lksa = $conn->query("SELECT COUNT(*) AS total FROM LKSA")->fetch_assoc()['total'];
$total_user = $conn->query("SELECT COUNT(*) AS total FROM User")->fetch_assoc()['total'];
$total_donatur = $conn->query("SELECT COUNT(*) AS total FROM Donatur")->fetch_assoc()['total'];
$total_sumbangan = $conn->query("SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan")->fetch_assoc()['total'];

?>
<h1 class="dashboard-title">Sistem Informasi ZIS dan Kotak Amal</h1>
<p class="welcome-text">Selamat Datang, Pimpinan</p>
<p>Anda memiliki akses penuh ke seluruh data dan fitur di sistem.</p>
<h2>Ringkasan Statistik Global</h2>
<div class="stats-grid">
    <div class="stats-card card-lksa">
        <i class="fas fa-building"></i>
        <h3>Jumlah LKSA</h3>
        <span class="value"><?php echo $total_lksa; ?></span>
    </div>
    <div class="stats-card card-user">
        <i class="fas fa-users"></i>
        <h3>Total Pengguna</h3>
        <span class="value"><?php echo $total_user; ?></span>
    </div>
    <div class="stats-card card-donatur">
        <i class="fas fa-hand-holding-heart"></i>
        <h3>Jumlah Donatur</h3>
        <span class="value"><?php echo $total_donatur; ?></span>
    </div>
    <div class="stats-card card-sumbangan">
        <i class="fas fa-sack-dollar"></i>
        <h3>Total Sumbangan</h3>
        <span class="value">Rp <?php echo number_format($total_sumbangan); ?></span>
    </div>
</div>
<?php
include 'includes/footer.php';
$conn->close();
?>