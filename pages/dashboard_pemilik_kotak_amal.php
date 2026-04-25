<?php
session_start();
include '../config/database.php';

// Verifikasi sesi pemilik kotak amal
if (!isset($_SESSION['is_pemilik_kotak_amal']) || !$_SESSION['is_pemilik_kotak_amal']) {
    header("Location: ../login/login.php");
    exit;
}

$id_kotak_amal = $_SESSION['id_kotak_amal'];

// Fetch Nama Pemilik Kotak Amal, Nama Toko, dan Foto
$sql_data = "SELECT Nama_Pemilik, Nama_Toko, Foto FROM KotakAmal WHERE ID_KotakAmal = '$id_kotak_amal'";
$result_data = $conn->query($sql_data);
$pemilik_data = $result_data->fetch_assoc();
$nama_pemilik = $pemilik_data['Nama_Pemilik'] ?? 'Pemilik Kotak Amal';
$nama_toko = $pemilik_data['Nama_Toko'] ?? 'Lokasi Kotak Amal';
$foto_kotak_amal = $pemilik_data['Foto'] ?? '';
$_SESSION['nama_pemilik'] = $nama_pemilik;

// Hitung Total Dana Kotak Amal (Seluruh Waktu)
// Menggunakan 'JmlUang' (sudah diperbaiki di langkah sebelumnya)
$sql_total = "SELECT SUM(JmlUang) AS total_dana FROM Dana_KotakAmal 
              WHERE ID_KotakAmal = '$id_kotak_amal'";
$result_total = $conn->query($sql_total);
$total_data = $result_total->fetch_assoc();
$total_dana = $total_data['total_dana'] ?? 0;

// Hitung Jumlah Pengambilan
$sql_count = "SELECT COUNT(ID_Kwitansi_KA) AS jumlah_pengambilan FROM Dana_KotakAmal 
              WHERE ID_KotakAmal = '$id_kotak_amal'";
$result_count = $conn->query($sql_count);
$count_data = $result_count->fetch_assoc();
$jumlah_pengambilan = $count_data['jumlah_pengambilan'] ?? 0;

// Ambil Riwayat Pengambilan Dana
$sql_riwayat = "SELECT dka.*, u.Nama_User FROM Dana_KotakAmal dka 
                LEFT JOIN User u ON dka.ID_user = u.Id_user
                WHERE dka.ID_KotakAmal = '$id_kotak_amal'
                ORDER BY dka.Tgl_Ambil DESC";
$result_riwayat = $conn->query($sql_riwayat);

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
// Menggunakan foto dari database, fallback ke yayasan.png
$foto_path = $foto_kotak_amal ? $base_url . 'assets/img/' . $foto_kotak_amal : $base_url . 'assets/img/yayasan.png';

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik Kotak Amal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* NEW STYLES FOR KOTAK AMAL LAYOUT (Sidebar Enabled) */
        /* Warna Aksen Baru: #1D4ED8 (Royal Blue) - Diperbarui untuk Kotak Amal */
        :root {
            --ka-accent: #1D4ED8; /* Royal Blue */
            --ka-secondary-bg: #DBEAFE; /* Light Blue */
            --logout-danger: #EF4444; /* Red */
            --text-dark: #1F2937; /* Deep Navy */
        }

        body {
            background-image: url('../assets/img/bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Open Sans', sans-serif;
        }
        .container {
            max-width: 1400px;
            padding: 20px;
        }
        
        /* Layout Utama dengan Sidebar */
        .content { 
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            margin-top: 20px; 
            display: flex;
            gap: 40px; 
            align-items: flex-start;
        }

        .sidebar-wrapper { 
            width: 280px; 
            flex-shrink: 0;
            padding: 20px 0; 
            text-align: center;
            border-right: 1px solid #e0e0e0;
            padding-right: 40px;
        }
        .main-content-area {
            flex-grow: 1;
            padding: 0;
        }
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid var(--ka-accent); 
            margin-bottom: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
        .welcome-text-sidebar {
            font-size: 1.2em;
            font-weight: 600;
            margin: 10px 0 20px 0;
            color: var(--text-dark);
        }
        
        .sidebar-wrapper .btn-custom { 
            background-color: var(--ka-accent); 
            color: #fff;
            padding: 12px 25px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
            width: 100%;
            margin-top: 10px;
            box-sizing: border-box;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .sidebar-wrapper .btn-custom:hover {
            background-color: #1E40AF; /* Darker Blue on hover */
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(29, 78, 216, 0.4); 
        }
        
        .sidebar-wrapper .btn-logout { 
            background-color: var(--logout-danger); 
            color: #fff;
        }
        .sidebar-wrapper .btn-logout:hover {
            background-color: #DC2626; 
        }
        .sidebar-wrapper .btn-report { 
            background-color: #3B82F6; 
            color: white;
            font-weight: 700;
        }
        .sidebar-wrapper .btn-report:hover {
            background-color: #2563EB; 
        }

        .sidebar-wrapper hr { 
            margin: 20px 0;
            border: 0;
            border-top: 1px solid #e0e0e0;
        }
        .sidebar-stats-card {
            background-color: var(--ka-secondary-bg);
            padding: 18px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: left;
            border-left: 5px solid var(--ka-accent); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .sidebar-stats-card h4 {
            margin: 0 0 5px 0;
            font-size: 0.9em;
            color: #555;
        }
        .sidebar-stats-card p {
            margin: 0;
            font-size: 1.5em;
            font-weight: 700;
            color: var(--ka-accent); 
        }
        .header {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 0;
        }

        /* STYLING TAMBAHAN UNTUK DASHBOARD KOTAK AMAL */
        .stats-card {
            background-color: #fff; /* Reset background for main content */
            border: 1px solid #e0e0e0;
            text-align: left;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .stats-card i {
            color: var(--ka-accent); /* Ikon berwarna aksen */
            font-size: 3.0em;
        }
        
        .stats-card h3 {
            font-size: 1.3em;
            color: var(--text-dark); 
        }
        
        .stats-card .value {
            font-size: 3.5em;
            font-weight: 800;
            color: var(--ka-accent);
        }
        
        .main-content-area h2 {
            font-size: 1.8em;
            color: var(--ka-accent);
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-top: 40px;
            font-family: 'Montserrat', sans-serif;
        }

        table thead th {
            background-color: var(--ka-accent);
            color: white;
            font-weight: 600;
        }
        
        /* NEW FOOTER STYLES */
        .footer-main {
            background-color: #F9FAFB; 
            padding: 25px 0;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            border-top: 1px solid #D1D5DB; 
            margin-top: 30px; 
            box-shadow: none;
        }

        .footer-content {
            max-width: 1200px; 
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-main p {
            margin: 4px 0;
            font-size: 0.85em;
            color: #6B7280; 
            font-weight: 500;
        }
        
        .footer-main p strong {
            font-weight: 700;
            color: var(--text-dark); 
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 10px;"> 
                <div style="text-align: left; line-height: 1.2; padding-top: 5px;">
                    <span style="font-size: 0.8em; color: #1F2937; display: block; margin: 0;">Sistem Informasi Pengelolaan ZISWAF & Kotak Amal</span>
                    <h1 style="margin: 0; font-size: 2.0em; font-weight: 900; font-family: 'Montserrat', sans-serif;">
                         <img src="../assets/img/give_track_logo_final.png" alt="Give Track Logo System"
                            style="height: 40px; width: auto; margin: 0; padding: 0; vertical-align: middle;">
                    </h1> 
                    <span style="font-size: 0.7em; color: #555; display: block; margin: 0;">mendonasikan, mengapresiasi, dan menjaga keberlanjutan kebaikan</span>
                </div>
            </div>
            
        </div>
        <div class="content">
            <div class="sidebar-wrapper">
                <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Kotak Amal" class="profile-img">
                
                <p class="welcome-text-sidebar">Selamat Datang,<br>
                <strong><?php echo htmlspecialchars($nama_pemilik); ?> (Pemilik KA)</strong></p>
                <p style="margin-top: -10px; font-size: 0.9em; color: #777;">Lokasi: **<?php echo htmlspecialchars($nama_toko); ?>**</p>

                <a href="<?php echo $base_url; ?>pages/edit_pemilik_kotak_amal.php" class="btn btn-custom"><i class="fas fa-edit"></i> Edit Data Kotak Amal</a>
                
                <a href="tambah_laporan.php" class="btn btn-custom btn-report" style="margin-top: 10px;">
                    <i class="fas fa-bullhorn"></i> Laporkan Masalah
                </a>

                <a href="../login/logout.php" class="btn btn-custom btn-logout" style="margin-top: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                
                <hr>
                
                <div class="sidebar-stats-card">
                    <h4>Total Dana Terkumpul (All Time)</h4>
                    <p>Rp <?php echo number_format($total_dana, 0, ',', '.'); ?></p>
                </div>
                <div class="sidebar-stats-card">
                    <h4>Frekuensi Pengambilan</h4>
                    <p><?php echo number_format($jumlah_pengambilan, 0, ',', '.'); ?>x</p>
                </div>
            </div>
            <div class="main-content-area">
                <p style="text-align: left; font-size: 1.1em; color: #555;">Selamat datang di Dashboard Kotak Amal Anda. Pantau total dana yang terkumpul dan riwayat pengambilan.</p>
                
                <div class="stats-grid" style="grid-template-columns: 1fr;">
                    <div class="stats-card card-kotak-amal">
                        <i class="fas fa-box-open"></i>
                        <h3>Total Dana yang Terkumpul Keseluruhan</h3>
                        <span class="value">Rp <?php echo number_format($total_dana, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <h2>Riwayat Pengambilan Dana Kotak Amal</h2>
                <table style="font-size: 0.95em;">
                    <thead>
                        <tr>
                            <th><i class="fas fa-receipt"></i> ID Kwitansi KA</th>
                            <th><i class="fas fa-calendar"></i> Tanggal Ambil</th>
                            <th><i class="fas fa-coins"></i> Nominal</th> 
                            <th><i class="fas fa-user-tag"></i> Petugas Pengambil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_riwayat->num_rows > 0) { ?>
                            <?php while ($row = $result_riwayat->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['ID_Kwitansi_KA']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['Tgl_Ambil'])); ?></td>
                                    <td class="money-col">Rp. <?php echo number_format($row['JmlUang'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row['Nama_User'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="4" class="no-data">Belum ada riwayat pengambilan dana kotak amal yang tercatat.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            </div>
    </div>
    <footer class="footer-main">
        <div class="footer-content">
            <p style="font-weight: 700; color: var(--text-dark); font-size: 0.95em;">&copy; <?php echo date('Y'); ?> Give Track</p>
            <p style="font-size: 0.85em;">Sistem Informasi Pengelolaan ZISWAF & Kotak Amal. Dikelola oleh **LKSA Nur Hidayah**.</p>
        </div>
    </footer>
</body>
</html>