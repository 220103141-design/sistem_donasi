<?php
// File: includes/header.php (Modified)
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
if ($current_dir == 'lksa_nh') {
    $current_page = 'index.php';
}

$dashboard_active = ($current_page == 'index.php' || strpos($current_page, 'dashboard_') !== false) ? 'active' : '';
$lksa_active = ($current_page == 'lksa.php' || $current_page == 'tambah_lksa.php' || $current_page == 'tambah_pimpinan.php' || $current_page == 'edit_lksa.php') ? 'active' : '';
$users_active = ($current_page == 'users.php' || $current_page == 'tambah_pengguna.php' || $current_page == 'edit_pengguna.php') ? 'active' : '';
$donatur_active = ($current_page == 'donatur.php' || $current_page == 'tambah_donatur.php' || $current_page == 'edit_donatur.php') ? 'active' : '';
$sumbangan_active = ($current_page == 'sumbangan.php' || $current_page == 'tambah_sumbangan.php' || $current_page == 'detail_sumbangan.php') ? 'active' : '';
$verifikasi_active = ($current_page == 'verifikasi-donasi.php' || $current_page == 'edit_sumbangan.php' || $current_page == 'wa-blast-form.php') ? 'active' : '';
$kotak_amal_active = ($current_page == 'kotak-amal.php' || $current_page == 'tambah_kotak_amal.php' || $current_page == 'edit_kotak_amal.php') ? 'active' : '';
$dana_kotak_amal_active = ($current_page == 'dana-kotak-amal.php' || $current_page == 'edit_dana_kotak_amal.php' || $current_page == 'catat_pengambilan_ka.php' || $current_page == 'detail_surat_tugas.php') ? 'active' : '';
// --- NEW ACTIVE VARIABLE ---
$riwayat_ka_active = ($current_page == 'riwayat_dana_kotak_amal.php') ? 'active' : '';
// --- END NEW ACTIVE VARIABLE ---
$laporan_active = ($current_page == 'laporan.php' || $current_page == 'tambah_laporan.php' || $current_page == 'detail_laporan.php') ? 'active' : ''; 
$export_menu_active = ($current_page == 'export_data_menu.php') ? 'active' : '';

// --- SIDEBAR LOGIC (UNCHANGED, except for new variable usage) ---
$show_sidebar = false;
$sidebar_html = '';
$is_internal_user = false;

if (isset($_SESSION['loggedin']) && isset($_SESSION['id_user'])) {

    if (isset($conn)) {
        $id_user = $_SESSION['id_user'];
        $user_info_sql = "SELECT Nama_User, Foto, Jabatan FROM User WHERE Id_user = '$id_user'";
        // PERBAIKAN KRITIS: Pastikan query dieksekusi dengan aman sebelum fetch_assoc
        $user_info_result = $conn->query($user_info_sql);
        $user_info = $user_info_result ? $user_info_result->fetch_assoc() : null;
        $nama_user = $user_info['Nama_User'] ?? 'Pengguna';
        $foto_user = $user_info['Foto'] ?? '';
        $jabatan = $user_info['Jabatan'] ?? '';
        $is_internal_user = true;
        $foto_path = $foto_user ? $base_url . 'assets/img/' . $foto_user : $base_url . 'assets/img/yayasan.png';

        $sidebar_stats = $sidebar_stats ?? '';

        if ($current_page == 'index.php' || $current_dir == 'dashboards' || in_array($current_page, ['lksa.php', 'users.php', 'donatur.php', 'sumbangan.php', 'kotak-amal.php', 'verifikasi-donasi.php', 'dana-kotak-amal.php', 'tambah_pimpinan.php', 'tambah_pengguna.php', 'tambah_donatur.php', 'tambah_kotak_amal.php', 'tambah_sumbangan.php', 'laporan.php', 'tambah_laporan.php', 'edit_pengguna.php', 'edit_donatur.php', 'edit_sumbangan.php', 'edit_kotak_amal.php', 'edit_lksa.php', 'edit_dana_kotak_amal.php', 'detail_sumbangan.php', 'detail_laporan.php', 'export_data_menu.php', 'riwayat_dana_kotak_amal.php'])) {
            if ($current_page != 'dashboard_donatur.php' && $current_page != 'dashboard_pemilik_kotak_amal.php') {
                $show_sidebar = true;
            }
        }

        if ($show_sidebar) {
            ob_start();
            ?>
            <div class="sidebar-wrapper">
                <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Profil" class="profile-img">

                <p class="welcome-text-sidebar">Selamat Datang,<br>
                    <strong><?php echo htmlspecialchars($nama_user); ?></strong> (<?php echo htmlspecialchars($jabatan); ?>)
                </p>

                <div class="sidebar-util-btns">
                    <a href="<?php echo $base_url; ?>pages/edit_pengguna.php?id=<?php echo htmlspecialchars($id_user); ?>"
                        class="btn btn-primary sidebar-small-btn" title="Edit Profil"><i class="fas fa-edit"></i></a>
                    <a href="<?php echo $base_url; ?>login/logout.php" class="btn btn-danger sidebar-small-btn" title="Logout"><i class="fas fa-sign-out-alt"></i>
                        </a>
                </div>

                <?php if ($jabatan != 'Pimpinan') { ?>
                <a href="<?php echo $base_url; ?>pages/tambah_laporan.php" class="btn btn-warning"
                    style="margin-top: 15px; margin-bottom: 15px; background-color: #0c9c6f; color: white; width: 100%; box-sizing: border-box;">
                    <i class="fas fa-bullhorn"></i> Lapor ke Atasan
                </a>
                <?php } ?>

                <hr>
                
                <?php echo $sidebar_stats; ?> <h2>Menu Navigasi</h2>
                
                <div class="sidebar-nav-group">
                    <a href="<?php echo $base_url; ?>index.php" class="sidebar-nav-item <?php echo $dashboard_active; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    
                    <?php if ($_SESSION['jabatan'] == 'Pimpinan' && $_SESSION['id_lksa'] == 'Pimpinan_Pusat') { ?>
                        <a href="<?php echo $base_url; ?>pages/lksa.php" class="sidebar-nav-item <?php echo $lksa_active; ?>">
                            <i class="fas fa-building"></i> Manajemen LKSA
                        </a>
                    <?php } ?>
                    
                    <?php if ($_SESSION['jabatan'] == 'Pimpinan' || $_SESSION['jabatan'] == 'Kepala LKSA') { ?>
                        <a href="<?php echo $base_url; ?>pages/users.php" class="sidebar-nav-item <?php echo $users_active; ?>">
                            <i class="fas fa-users"></i> Manajemen Pengguna
                        </a>
                    <?php } ?>
                </div>

                <?php if ($_SESSION['jabatan'] == 'Pimpinan' || $_SESSION['jabatan'] == 'Kepala LKSA' || $_SESSION['jabatan'] == 'Pegawai') { ?>
                    <hr class="nav-divider">
                    <h3>ZIS & Donatur</h3>
                    <div class="sidebar-nav-group">
                        <a href="<?php echo $base_url; ?>pages/donatur.php" class="sidebar-nav-item <?php echo $donatur_active; ?>">
                            <i class="fas fa-hand-holding-heart"></i> Manajemen Donatur ZIS
                        </a>
                        <a href="<?php echo $base_url; ?>pages/sumbangan.php" class="sidebar-nav-item <?php echo $sumbangan_active; ?>">
                            <i class="fas fa-funnel-dollar"></i> Manajemen Sumbangan
                        </a>
                        <?php if ($_SESSION['jabatan'] == 'Pimpinan' || $_SESSION['jabatan'] == 'Kepala LKSA') { ?>
                            <a href="<?php echo $base_url; ?>pages/verifikasi-donasi.php" class="sidebar-nav-item <?php echo $verifikasi_active; ?>">
                                <i class="fas fa-check-double"></i> Verifikasi Donasi
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>
                
                <?php if ($_SESSION['jabatan'] == 'Pimpinan' || $_SESSION['jabatan'] == 'Kepala LKSA' || $_SESSION['jabatan'] == 'Petugas Kotak Amal') { ?>
                    <hr class="nav-divider">
                    <h3>Kotak Amal</h3>
                    <div class="sidebar-nav-group">
                        <a href="<?php echo $base_url; ?>pages/kotak-amal.php" class="sidebar-nav-item <?php echo $kotak_amal_active; ?>">
                            <i class="fas fa-box"></i> Manajemen Kotak Amal
                        </a>
                        <a href="<?php echo $base_url; ?>pages/dana-kotak-amal.php" class="sidebar-nav-item <?php echo $dana_kotak_amal_active; ?>">
                            <i class="fas fa-coins"></i> Pengambilan Dana (Tugas)
                        </a>
                        <a href="<?php echo $base_url; ?>pages/riwayat_dana_kotak_amal.php" class="sidebar-nav-item <?php echo $riwayat_ka_active; ?>">
                            <i class="fas fa-history"></i> Riwayat Pengambilan
                        </a>
                        </div>
                <?php } ?>
                
                <?php if ($_SESSION['jabatan'] == 'Pimpinan' || $_SESSION['jabatan'] == 'Kepala LKSA') { ?>
                    <hr class="nav-divider">
                    <h3>Lainnya</h3>
                    <div class="sidebar-nav-group">
                        <a href="<?php echo $base_url; ?>pages/laporan.php" class="sidebar-nav-item <?php echo $laporan_active; ?>">
                            <i class="fas fa-inbox"></i> Laporan Pengguna
                        </a>
                        
                        <a href="<?php echo $base_url; ?>pages/export_data_menu.php" class="sidebar-nav-item <?php echo $export_menu_active; ?>">
                            <i class="fas fa-file-export"></i> Export Data
                        </a>
                        </div>
                <?php } ?>

                <hr>

            </div>
            <?php
            $sidebar_html = ob_get_clean();
        }
    }
}
// --- END SIDEBAR LOGIC ---
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Track - Sistem Informasi Pengelolaan ZISWAF & Kotak Amal</title> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... (CSS DARI HEADER.PHP SEBELUMNYA) ... */
        :root {
            --primary-color: #1F2937; /* Dark Navy/Slate (Base/Dark) */
            --secondary-color: #10B981; /* Aqua/Cyan (Accent/Highlight) */
            --tertiary-color: #F9FAFB; /* Soft Background (Baru) */
            --text-dark: #1F2937; /* Dark Slate Gray for general text */
            --text-light: #fff;
            --bg-light: #F9FAFB; /* Soft Background (Baru) */
            --border-color: #E5E7EB; /* Light border */
            --form-bg: #FFFFFF;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--bg-light);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px; /* Diperkecil */
            margin: 0 auto;
            padding: 20px;
        }

        /* Revised Header Styles */
        .header {
            padding: 20px 30px; /* Ditingkatkan untuk memberi ruang lebih */
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--form-bg);
            border-radius: 15px;
            margin-bottom: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); /* Shadow lebih kuat */
        }
        
        .header-logo-section {
            text-align: left;
            line-height: 1.2;
            padding-top: 5px;
            border-left: 5px solid var(--secondary-color); /* Tambah aksen border di kiri */
            padding-left: 15px;
        }

        .header-slogan-top {
            font-size: 0.85em; /* Sedikit diperbesar */
            color: #4B5563; /* Gray gelap */
            display: block; 
            margin: 0;
            font-weight: 600;
        }

        .header-title-main {
            margin: 5px 0 0 0; /* Jarak antara slogan atas dan logo */
            font-size: 2.2em;
            font-weight: 900;
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-color);
        }

        .header-logo-img {
            height: 45px; /* Sedikit dikecilkan agar seimbang dengan padding header */
            width: auto;
            margin: 0;
            padding: 0;
            vertical-align: middle;
        }
        
        .header-slogan-bottom {
            font-size: 0.75em; /* Sedikit diperbesar */
            color: #6B7280; 
            display: block; 
            margin: 0;
            font-style: italic;
        }
        /* End Revised Header Styles */


        .content {
            padding: 30px 40px; /* Dikecilkan untuk simetri */
            background-color: var(--form-bg);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            /* NEW: Re-enable original style and add flex for sidebar */
            margin-top: 15px; /* Dikecilkan */
            display: flex;
            gap: 30px; /* Dikecilkan */
            align-items: flex-start;
        }

        .btn {
            padding: 10px 20px; /* Dikecilkan */
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 8px; /* Dikecilkan */
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-block;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .btn-success {
            background: #10B981; /* Emerald Green */
            color: white;
        }
        
        .btn-warning {
            background: #0c9c6f; /* Orange/Warning */
            color: white;
        }

        .btn-danger {
            background: #EF4444; /* Red */
            color: white;
        }

        .btn-cancel {
            background: #6B7280; /* Gray-500 */
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px); /* Dikecilkan */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Dikecilkan */
        }
        
        /* Removed unused .summary-card styles */

        .dashboard-title {
            font-size: 2.0em; /* Dikecilkan */
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px; /* Dikecilkan */
            font-family: 'Montserrat', sans-serif;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 8px; /* Dikecilkan */
        }

        .welcome-text {
            font-size: 1.2em; /* Dikecilkan */
            font-weight: 600;
            color: #555;
            margin-top: 0;
            margin-bottom: 20px; /* Dikecilkan */
        }

        /* Menghilangkan CSS untuk top-nav yang sudah tidak ada */
        .top-nav {
            display: none; 
        }

        .nav-item {
            display: none;
        }
        /* Akhir Penghilangan CSS */

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px; /* Dikecilkan */
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05); /* Dikecilkan */
            border-radius: 10px; /* Dikecilkan */
            overflow: hidden;
            border: 1px solid var(--border-color);
            font-size: 0.95em; /* Dikecilkan */
        }

        th,
        td {
            text-align: left;
            padding: 12px; /* Dikecilkan */
            border-bottom: 1px solid var(--border-color); 
        }

        thead tr {
            background-color: var(--primary-color); /* Dark header */
            color: var(--text-light);
            font-weight: 600;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        thead th:first-child {
            border-top-left-radius: 10px;
        }
        
        thead th:last-child {
            border-top-right-radius: 10px;
        }

        tbody tr:nth-child(even) {
            background-color: #FDFDFD; 
        }
        
        tbody tr:hover {
            background-color: #F3F4F6; /* Light gray on hover */
        }
        
        /* Ensure the last row does not have a bottom border if it's the only one */
        tbody tr:last-child td {
            border-bottom: none;
        }

        .form-container {
            background-color: var(--form-bg);
            padding: 30px; /* Dikecilkan */
            border-radius: 12px; /* Dikecilkan */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            max-width: 700px; /* Dikecilkan */
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 25px; /* Dikecilkan */
        }

        .form-section h2 {
            border-bottom: 2px solid var(--secondary-color); /* Aqua/Cyan under header */
            padding-bottom: 8px; /* Dikecilkan */
            margin-bottom: 15px; /* Dikecilkan */
            color: var(--primary-color);
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.4em;
        }

        .form-group {
            margin-bottom: 15px; /* Dikecilkan */
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px; /* Dikecilkan */
            border: 1px solid var(--border-color);
            border-radius: 8px; /* Dikecilkan */
            box-sizing: border-box;
            font-size: 0.95em; /* Dikecilkan */
            font-family: 'Open Sans', sans-serif;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--secondary-color); /* Highlight on focus */
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3); /* Adjusted for Aqua/Cyan */
            outline: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Dikecilkan */
            gap: 15px; /* Dikecilkan */
        }

        .form-actions {
            display: flex;
            gap: 10px; /* Dikecilkan */
            justify-content: flex-end;
            margin-top: 25px; /* Dikecilkan */
        }
        
        /* Removed unused .summary-card styles */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Dikecilkan */
            gap: 20px; /* Dikecilkan */
            margin-bottom: 25px; /* Dikecilkan */
        }
        
        /* --- NEW STYLES FOR STATS CARD ELEGANCE --- */
        .stats-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: left; /* Layout Horizontal */
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid var(--border-color); 
            border-left: 5px solid; /* Use left border for color accent */
            display: flex;
            flex-direction: row; 
            justify-content: space-between;
            align-items: center;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-card i {
            font-size: 2.5em; /* Ikon besar */
            margin-bottom: 0;
            flex-shrink: 0;
            padding-right: 15px;
            opacity: 0.8; /* Sedikit transparan */
        }
        
        .stats-card-content {
            flex-grow: 1;
            text-align: right; /* Angka di kanan */
        }

        .stats-card h3 {
            margin: 0 0 5px 0;
            font-size: 0.9em; 
            color: #555; /* Warna redup untuk judul */
            font-weight: 600;
        }

        .stats-card .value {
            font-size: 1.8em; /* Angka besar dan menonjol */
            font-weight: 800;
            margin: 0;
            line-height: 1.1;
        }
        
        /* New large total card style */
        .stats-card-total-large {
            background-color: var(--primary-color); /* Deep Navy background */
            color: var(--text-light);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-top: 15px;
            border: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .stats-card-total-large h3 {
            color: var(--text-light);
            font-size: 1.4em;
            margin-bottom: 5px;
        }
        .stats-card-total-large i {
            font-size: 3.0em;
            color: var(--secondary-color); /* Aqua/Cyan */
            margin-bottom: 10px;
        }
        .stats-card-total-large .value {
            color: var(--secondary-color); /* Aqua/Cyan highlight untuk total */
            font-size: 3.0em; 
            font-weight: 900;
            margin-top: 5px;
        }
        /* --- END NEW STYLES --- */


        /* NEW CARD COLOR SCHEME */
        /* Aksen: Aqua/Deep Navy/Emerald/Indigo/Orange */
        .card-lksa { border-color: #10B981; } .card-lksa .value { color: #10B981; } .card-lksa i { color: #10B981; }
        .card-user { border-color: #1F2937; } .card-user .value { color: #1F2937; } .card-user i { color: #1F2937; }
        .card-donatur { border-color: #10B981; } .card-donatur .value { color: #10B981; } .card-donatur i { color: #10B981; } /* Emerald Green */
        .card-sumbangan { border-color: #047857; } .card-sumbangan .value { color: #047857; } .card-sumbangan i { color: #047857; } /* Indigo */
        .card-kotak-amal { border-color: #0c9c6f; } .card-kotak-amal .value { color: #0c9c6f; } .card-kotak-amal i { color: #0c9c6f; } /* Orange */
        .card-total { border-color: #EF4444; } .card-total .value { color: #EF4444; } .card-total i { color: #EF4444; }

        /* === NEW SIDEBAR STYLES (Disesuaikan untuk Layout 1 Kolom Utama) === */
        .sidebar-wrapper {
            width: 220px; /* Dikecilkan */
            flex-shrink: 0;
            padding: 15px 0; /* Dikecilkan */
            text-align: center;
            border-right: 1px solid var(--border-color);
            padding-right: 20px; /* Dikecilkan */
        }

        .main-content-area {
            flex-grow: 1;
            /* Perbaikan KRITIS untuk memastikan elemen ini tidak melebihi lebar */
            width: 100%; 
            min-width: 0; 
            /* Konten utama dashboard */
        }

        .profile-img {
            width: 80px; /* Dikecilkan dari 100px */
            height: 80px; /* Dikecilkan dari 100px */
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #E5E7EB; /* Border tipis dan lebih lembut */
            margin-bottom: 5px; 
            box-shadow: none; /* Dihapus */
        }

        .welcome-text-sidebar {
            font-size: 0.9em; /* Dikecilkan dari 1.0em */
            font-weight: 500;
            margin: 5px auto 10px auto; /* Disesuaikan */
            color: #6B7280; /* Warna abu-abu yang lebih lembut */
            line-height: 1.4;
        }
        /* Style untuk nama pengguna (yang ada di dalam strong tag) */
        .welcome-text-sidebar strong {
            font-weight: 700; 
            color: var(--primary-color);
            display: block; /* Memastikan nama di baris baru */
            font-size: 1.1em;
            margin-top: -3px;
        }

        /* NEW UTILITY BUTTON STYLES for Sidebar */
        .sidebar-util-btns {
            display: flex;
            justify-content: space-between;
            gap: 10px; 
            margin-bottom: 15px; 
        }

        .sidebar-small-btn {
            /* Icon-only button styles */
            padding: 10px; 
            width: 45%; 
            text-align: center;
            font-size: 1.1em; 
            border-radius: 8px; 
            display: flex; 
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            line-height: 1; 
            min-width: 40px; 
            max-width: 100px;
        }

        .sidebar-small-btn i {
            margin-right: 0 !important; 
        }
        /* END NEW UTILITY BUTTON STYLES */

        /* NEW NAVIGATION MENU STYLES */
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            text-align: left;
            text-decoration: none;
            color: var(--primary-color); 
            border-radius: 6px;
            margin-top: 5px;
            transition: background-color 0.2s, color 0.2s;
            font-size: 0.95em;
            font-weight: 600;
            line-height: 1.2;
        }
        .sidebar-nav-item:first-of-type {
             margin-top: 10px; /* Jarak dari header/hr Menu Navigasi */
        }
        .sidebar-nav-item i {
            margin-right: 10px;
            font-size: 1.1em;
            color: #9CA3AF; /* Light gray icon color */
            transition: color 0.2s;
            /* FIX: Ensure Font Awesome font loads for the icon element */
            font-family: 'Font Awesome 6 Free'; /* Primary Font Awesome 6 font */
            font-weight: 900; /* Ensure solid icons (fas) use the correct weight */
        }
        .sidebar-nav-item:hover {
            background-color: #E5E7EB; /* Lighter background on hover */
            color: var(--primary-color);
        }
        .sidebar-nav-item.active {
            background-color: var(--secondary-color); /* Active background color */
            color: var(--primary-color); /* Active text color */
            font-weight: 700;
        }
        .sidebar-nav-item.active i {
            color: var(--primary-color); /* Active icon color matches text */
        }
        /* Penyesuaian untuk grouping */
        .sidebar-nav-group {
            margin-bottom: 10px;
        }
        .nav-divider {
            margin: 15px 0 10px 0;
            border: 0;
            border-top: 1px solid var(--border-color);
        }

        /* END NEW NAVIGATION MENU STYLES */

        .sidebar-wrapper .btn {
            width: 100%;
            max-width: 200px; /* FIX: Menyesuaikan lebar dengan content area (220px - 20px padding kanan) */
            margin: 8px auto 0 auto; /* FIX: Ganti margin-top dan tambahkan auto untuk centering */
            font-size: 0.9em; /* Dikecilkan */
            box-sizing: border-box; /* FIX: Pastikan padding termasuk dalam lebar */
        }
        
        .sidebar-wrapper h2, .sidebar-wrapper h3 {
            font-size: 1.1em;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 15px;
            margin-bottom: 5px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 5px;
            text-align: left;
        }
        .sidebar-wrapper h3 {
            font-size: 0.95em;
            font-weight: 600;
            border-bottom: none;
            padding-bottom: 0;
            margin-top: 10px;
            color: #6B7280;
        }

        .sidebar-wrapper hr {
            margin: 15px 0; /* Dikecilkan */
            border: 0;
            border-top: 1px solid var(--border-color);
        }
        
        /* FOOTER STYLES (MODERN) */
        .footer-main {
            background-color: #F0F4F8; /* Warna abu-abu muda yang lembut */
            padding: 15px 0; /* Padding vertikal sedikit dikurangi */
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            border-top: 4px solid var(--secondary-color); /* Aksen warna Cyan yang kuat */
            margin-top: 30px; /* Jarak lebih besar dari konten utama */
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03); /* Sedikit bayangan di atas */
        }

        .footer-content {
            max-width: 1200px; 
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-main p {
            margin: 3px 0;
            font-size: 0.8em; /* Ukuran font lebih kecil */
            color: #4B5563; /* Warna gelap yang nyaman */
            font-weight: 500;
        }
        /* END FOOTER STYLES */

        /* === MEDIA QUERIES UNTUK RESPONSIVENESS === */
        
        /* Perubahan utama untuk tablet (768px - 1024px) */
        @media (max-width: 1024px) {
            .content {
                gap: 20px; /* Dikecilkan */
                padding: 20px; /* Dikecilkan */
            }
            .sidebar-wrapper {
                width: 180px; /* Dikecilkan */
                padding-right: 15px; /* Dikecilkan */
            }
            
            .main-content-area {
                /* Perbaikan KRITIS untuk memastikan elemen ini tidak melebihi lebar */
                overflow-x: auto; 
                padding-bottom: 5px; 
            }
        }
        
        /* Perubahan untuk perangkat mobile (di bawah 768px) */
        @media (max-width: 768px) {
            /* Konten utama menjadi satu kolom vertikal */
            .content {
                flex-direction: column;
                padding: 15px; /* Dikecilkan */
                gap: 15px; /* Dikecilkan */
            }

            /* Sidebar mengambil lebar penuh di atas */
            .sidebar-wrapper {
                width: 100%;
                padding-right: 0;
                border-right: none; /* Hapus garis pemisah vertikal */
                border-bottom: 1px solid var(--border-color); /* Tambah garis bawah */
                padding-bottom: 15px; /* Dikecilkan */
                margin-bottom: 15px; /* Dikecilkan */
            }
            
            /* Konten utama mengambil lebar penuh di bawah */
            .main-content-area {
                width: 100%;
                overflow-x: auto; /* Memastikan tabel bisa di-scroll di mobile */
            }

            /* Tombol-tombol di sidebar dibuat lebih lebar */
            .sidebar-wrapper .btn {
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
            }

            /* Tombol utilitas dibuat 50% dari lebar container, tetap berjejer */
            .sidebar-util-btns {
                margin-left: 0;
                margin-right: 0;
                justify-content: center;
            }
            
            /* Tombol Lapor diatur kembali agar mengambil lebar penuh di mobile */
            .sidebar-wrapper a.btn-warning {
                 margin-left: 0 !important;
                 margin-right: 0 !important;
            }

            /* Tata letak statistik di sidebar diubah menjadi vertikal penuh */
            .sidebar-stats-card {
                display: block; 
                width: 100%; 
                margin-top: 8px; /* Dikecilkan */
            }
            
            .sidebar-wrapper h2, .sidebar-wrapper h3 {
                margin-top: 8px; /* Dikecilkan */
                border-bottom: none;
                padding-bottom: 0;
                text-align: center;
            }

            .sidebar-nav-item {
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar-nav-item i {
                margin-right: 8px;
            }
            
            .footer-main {
                margin-top: 15px;
            }

            .top-nav {
                padding: 8px; /* Padding menu navigasi lebih kecil */
            }
        }
        /* END NEW SIDEBAR STYLES */
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-logo-section">
                <span class="header-slogan-top">Sistem Informasi Pengelolaan ZISWAF & Kotak Amal</span>
                
                <h1 class="header-title-main">
                     <img src="<?php echo $base_url; ?>assets/img/give_track_logo_final.png" alt="Give Track Logo System" class="header-logo-img">
                </h1> 
                
                <span class="header-slogan-bottom">mendonasikan, mengapresiasi, dan menjaga keberlanjutan kebaikan</span>
            </div>
        </div>
        <?php if ($show_sidebar) { ?>
            <div class="content">
                <?php echo $sidebar_html; ?>
                <div class="main-content-area">
                <?php } else { ?>
                    <div class="content">
                    <?php } ?>