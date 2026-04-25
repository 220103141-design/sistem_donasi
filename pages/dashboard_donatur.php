<?php
session_start();
include '../config/database.php';

// Verifikasi sesi donatur
if (!isset($_SESSION['id_donatur'])) {
    header("Location: ../login/login.php");
    exit;
}

$id_donatur = $_SESSION['id_donatur'];
$nama_donatur = $_SESSION['nama_donatur'] ?? 'Donatur';
$total_donasi = 0;
$result_history = null;

// Query untuk mendapatkan total donasi
// FIX: Mengganti 'Nominal' dengan penjumlahan kolom ZIS
$sql_total = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total_donasi FROM Sumbangan WHERE ID_donatur = ?";
$stmt_total = $conn->prepare($sql_total);
if ($stmt_total) {
    $stmt_total->bind_param("s", $id_donatur);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total_donasi = $result_total->fetch_assoc()['total_donasi'] ?? 0;
    $stmt_total->close();
}

// Query untuk mendapatkan riwayat donasi dan nama user yang menginput
// FIX: Memastikan semua kolom ZIS ditarik (s.*)
$sql_history = "SELECT s.*, u.Nama_User 
                FROM Sumbangan s 
                LEFT JOIN User u ON s.ID_User = u.Id_user 
                WHERE s.ID_donatur = ?
                ORDER BY s.Tgl desc";
$stmt_history = $conn->prepare($sql_history);
if ($stmt_history) {
    $stmt_history->bind_param("s", $id_donatur);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    $stmt_history->close();
}

// LOGIC BARU UNTUK SIDEBAR
// Ambil foto donatur (asumsi Donatur table has a Foto column)
$sql_donatur_foto = "SELECT Foto FROM Donatur WHERE ID_donatur = ?";
$stmt_foto = $conn->prepare($sql_donatur_foto);
$stmt_foto->bind_param("s", $id_donatur);
$stmt_foto->execute();
$foto_result = $stmt_foto->get_result();
$foto_row = $foto_result->fetch_assoc();
$foto_donatur = $foto_row['Foto'] ?? '';
$stmt_foto->close();

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$foto_path = $foto_donatur ? $base_url . 'assets/img/' . $foto_donatur : $base_url . 'assets/img/yayasan.png'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Donatur</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* NEW STYLES FOR DONATUR LAYOUT */
        /* Warna Aksen Baru: #10B981 (Emerald Green) */
        :root {
            --donatur-accent: #10B981; /* Emerald Green */
            --donatur-secondary-bg: #E0F2F1; /* Light Green-Cyan */
            --logout-danger: #EF4444; /* Red */
            --text-dark: #1F2937; /* Deep Navy (Diperbarui) */
        }

        body {
            background-image: url('../assets/img/bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Open Sans', sans-serif; /* Menggunakan Open Sans */
        }
        .container {
            max-width: 1400px; /* Increased max-width */
            padding: 20px;
        }
        
        /* Implementasi layout sidebar di .content */
        .content { 
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1); /* Bayangan lebih menonjol dan elegan */
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
            border: 5px solid var(--donatur-accent); /* Menggunakan warna baru */
            margin-bottom: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2); /* Bayangan lebih jelas */
        }
        .welcome-text-sidebar {
            font-size: 1.2em;
            font-weight: 600;
            margin: 10px 0 20px 0;
            color: var(--text-dark); /* Deep Navy */
        }
        /* Mengganti btn-primary dan btn-danger dengan gaya kustom */
        .sidebar-wrapper .btn-custom { 
            background-color: var(--donatur-accent); /* Use Emerald Green accent color */
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
            background-color: #059669; /* Darker Emerald Green on hover */
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.4); /* Shadow khusus hover */
        }
        /* Gaya khusus untuk tombol Logout */
        .sidebar-wrapper .btn-logout { 
            background-color: var(--logout-danger); /* Red */
            color: #fff;
        }
        .sidebar-wrapper .btn-logout:hover {
            background-color: #DC2626; /* Darker red on hover */
        }
        .sidebar-wrapper .btn-report { /* Gaya untuk tombol Lapor Masalah */
            background-color: #3B82F6; /* Strong Blue (Diperbarui) */
            color: white;
            font-weight: 700;
        }
        .sidebar-wrapper .btn-report:hover {
            background-color: #2563EB; /* Darker Blue */
        }

        .sidebar-wrapper hr { 
            margin: 20px 0;
            border: 0;
            border-top: 1px solid #e0e0e0;
        }
        .sidebar-stats-card {
            background-color: var(--donatur-secondary-bg); /* Menggunakan warna latar belakang yang lebih terang */
            padding: 18px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: left;
            border-left: 5px solid var(--donatur-accent); /* Menggunakan warna baru */
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
            color: var(--donatur-accent); /* Menggunakan warna baru */
        }
        .header {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 0;
        }
        .header h1 {
            color: var(--text-dark); /* Deep Navy */
        }

        /* STYLING TAMBAHAN UNTUK DASHBOARD DONATUR */
        .stats-card {
            text-align: left;
            padding: 30px; /* Lebih lega */
            align-items: flex-start;
            border-radius: 15px; /* Lebih membulat */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); /* Shadow elegan */
        }
        
        .stats-card i {
            font-size: 3.0em; /* Ikon lebih besar */
        }
        
        .stats-card h3 {
            font-size: 1.3em;
            color: var(--text-dark); /* Deep Navy */
        }
        
        .stats-card .value {
            font-size: 3.5em;
            font-weight: 800;
        }
        
        .main-content-area h2 {
            font-size: 1.8em;
            color: var(--donatur-accent);
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-top: 40px;
            font-family: 'Montserrat', sans-serif;
        }

        table thead th {
            background-color: var(--donatur-accent);
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
                <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Profil" class="profile-img">
                
                <p class="welcome-text-sidebar">Selamat Datang,<br>
                <strong><?php echo htmlspecialchars($nama_donatur); ?> (Donatur)</strong></p>

                <a href="<?php echo $base_url; ?>pages/edit_donatur.php?id=<?php echo htmlspecialchars($id_donatur); ?>" class="btn btn-custom"><i class="fas fa-edit"></i> Edit Profil</a> 
                
                <a href="tambah_laporan.php" class="btn btn-custom btn-report" style="margin-top: 10px;">
                    <i class="fas fa-bullhorn"></i> Laporkan Masalah
                </a>

                <a href="../login/logout.php" class="btn btn-custom btn-logout" style="margin-top: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                
                <hr>
                
                <div class="sidebar-stats-card">
                    <h4>Total Donasi Anda</h4>
                    <p>Rp <?php echo number_format($total_donasi); ?></p>
                </div>
            </div>
            <div class="main-content-area">
                <p style="text-align: left; font-size: 1.1em; color: #555;">Selamat datang, <strong><?php echo htmlspecialchars($nama_donatur); ?></strong>! Pantau riwayat donasi Anda di sini.</p>
                
                <div class="stats-grid" style="grid-template-columns: 1fr;">
                    <div class="stats-card card-donatur">
                        <i class="fas fa-hand-holding-usd"></i>
                        <h3>Total Donasi ZIS Uang Keseluruhan</h3>
                        <span class="value">Rp <?php echo number_format($total_donasi); ?></span>
                    </div>
                </div>

                <h2>Riwayat Sumbangan ZIS Anda</h2>
                <table style="font-size: 0.95em;">
                    <thead>
                        <tr>
                            <th><i class="fas fa-receipt"></i> No. Kwitansi</th>
                            <th><i class="fas fa-calendar"></i> Tanggal</th>
                            <th>Total Donasi Uang</th>
                            <th>Natura (Barang)</th>
                            <th><i class="fas fa-user-tag"></i> Dibuat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_history && $result_history->num_rows > 0) { ?>
                            <?php while ($row = $result_history->fetch_assoc()) { 
                                // FIX: Menghitung total uang ZIS dari kolom individual
                                $total_uang_zis = $row['Zakat_Profesi'] + $row['Zakat_Maal'] + $row['Infaq'] + $row['Sedekah'] + $row['Fidyah'];
                                $natura_display = !empty($row['Natura']) ? htmlspecialchars($row['Natura']) : '-';
                            ?>
                                <tr>
                                    <td><?php echo $row['ID_Kwitansi_ZIS']; ?></td>
                                    <td><?php echo $row['Tgl']; ?></td>
                                    <td class="money-col">Rp <?php echo number_format($total_uang_zis); ?></td>
                                    <td><?php echo $natura_display; ?></td>
                                    <td><?php echo htmlspecialchars($row['Nama_User'] ?? 'Admin'); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="no-data">Belum ada data sumbangan yang tercatat.</td>
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