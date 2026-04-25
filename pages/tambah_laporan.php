<?php
session_start();
include '../config/database.php';

$id_user_pelapor = '';
$pelapor_type = '';
$id_lksa = $_SESSION['id_lksa'] ?? ''; 
$nama_pelapor = ''; 
$cancel_url = ''; // URL tujuan tombol kembali

// --- 1. Cek User Internal (Pimpinan, Kepala LKSA, Pegawai, Petugas Kotak Amal)
if (isset($_SESSION['id_user'])) {
    $id_user_pelapor = $_SESSION['id_user'];
    $pelapor_type = 'USER';
    $nama_pelapor = $_SESSION['nama_user'] ?? 'Pengguna Internal'; 
    $id_lksa = $_SESSION['id_lksa'] ?? 'LSA_PUSAT';
    $cancel_url = '../index.php';
} 
// --- 2. Cek Donatur
elseif (isset($_SESSION['id_donatur'])) {
    $id_user_pelapor = $_SESSION['id_donatur'];
    $pelapor_type = 'DONATUR';
    // Ambil Nama dan ID LKSA Donatur dari DB
    $stmt = $conn->prepare("SELECT Nama_Donatur, ID_LKSA FROM Donatur WHERE ID_Donatur = ?");
    $stmt->bind_param("s", $id_user_pelapor);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $nama_pelapor = $res['Nama_Donatur'] ?? 'Donatur';
    $id_lksa = $res['ID_LKSA'] ?? 'LSA_PUSAT';
    $cancel_url = 'dashboard_donatur.php';
} 
// --- 3. Cek Pemilik Kotak Amal
elseif (isset($_SESSION['id_kotak_amal'])) {
    $id_user_pelapor = $_SESSION['id_kotak_amal'];
    $pelapor_type = 'PEMILIK_KA';
    // Ambil Nama dan ID LKSA Pemilik KA dari DB
    $stmt = $conn->prepare("SELECT Nama_Pemilik, ID_LKSA FROM KotakAmal WHERE ID_KotakAmal = ?");
    $stmt->bind_param("s", $id_user_pelapor);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $nama_pelapor = $res['Nama_Pemilik'] ?? 'Pemilik Kotak Amal';
    $id_lksa = $res['ID_LKSA'] ?? 'LSA_PUSAT';
    $cancel_url = 'dashboard_pemilik_kotak_amal.php';
}

// Otorisasi final
if (empty($id_user_pelapor)) {
    die("Akses ditolak. Silakan login sebagai pengguna sistem atau donatur/pemilik kotak amal.");
}

// Include header jika user internal, jika tidak, hanya layout minimal
$is_internal = ($pelapor_type == 'USER');
if ($is_internal) {
    $sidebar_stats = ''; 
    include '../includes/header.php';
} else {
    // --- Layout Minimal untuk Donatur/Pemilik KA
    $base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
    echo "<!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Lapor Masalah</title>
            <link rel='stylesheet' href='../assets/css/style.css'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
            <link href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap' rel='stylesheet'>
            <style>
                body { background-image: url('../assets/img/bg.png'); background-size: cover; background-attachment: fixed; font-family: 'Open Sans', sans-serif; }
                .form-container { max-width: 700px; margin: 50px auto; padding: 40px; background-color: #fff; border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.1); }
                .btn { padding: 12px 25px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; color: white; border: none; font-size: 1em; display: inline-block; }
                .btn-submit { background-color: #e67e22; }
                .btn-submit:hover { background-color: #d35400; transform: translateY(-3px); }
                .btn-cancel { background-color: #95a5a6; }
                .btn-cancel:hover { background-color: #7f8c8d; transform: translateY(-3px); }
                .form-group label { font-weight: 600; color: #2c3e50; margin-bottom: 4px; display: block; font-size: 0.9em; }
                .form-group input, .form-group textarea { padding: 12px; border: 1px solid #e0e0e0; border-radius: 10px; width: 100%; box-sizing: border-box; background-color: #fafafa; font-size: 0.95em; }
                .form-actions { display: flex; justify-content: space-between; gap: 15px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container' style='padding-top: 50px;'>
    ";
}

?>
<div class="content" style="padding: 0; background: none; box-shadow: none;">
    <div class="form-container">
        <h1><i class="fas fa-bullhorn" style="color: #e67e22;"></i> Lapor ke Atasan / Admin</h1>
        <p>Anda melapor sebagai: <strong><?php echo htmlspecialchars($nama_pelapor); ?> (<?php echo htmlspecialchars($pelapor_type); ?>)</strong></p>
        <p>Gunakan formulir ini untuk melaporkan masalah atau menyampaikan pesan kepada manajemen LKSA.</p>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>
            <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: 600;">
                Laporan berhasil dikirim! Terima kasih atas informasinya.
            </div>
        <?php } ?>
        
        <form action="proses_laporan.php" method="POST">
            <input type="hidden" name="id_pelapor" value="<?php echo htmlspecialchars($id_user_pelapor); ?>">
            <input type="hidden" name="pelapor_type" value="<?php echo htmlspecialchars($pelapor_type); ?>">
            <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($id_lksa); ?>">
            
            <div class="form-section">
                <div class="form-group">
                    <label>Subjek Laporan:</label>
                    <input type="text" name="subjek" required placeholder="Contoh: Masalah Sistem Input Donasi">
                </div>
                <div class="form-group">
                    <label>Pesan/Detail Laporan:</label>
                    <textarea name="pesan" rows="6" required placeholder="Jelaskan masalah atau pesan Anda secara rinci di sini."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?php echo htmlspecialchars($cancel_url); ?>" class="btn btn-cancel"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>

                <button type="submit" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<?php
if ($is_internal) {
    include '../includes/footer.php';
} else {
    echo "</div></body></html>";
}
$conn->close();
?>