<?php
session_start();
include '../config/database.php';
// Set sidebar_stats ke string kosong sebelum memuat header
$sidebar_stats = ''; 
include '../includes/header.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
?>

<style>
    /* ... (CSS tetap sama) ... */
    .export-list-container {
        max-width: 800px;
        margin: 30px auto;
        padding: 0;
        background-color: var(--form-bg);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-color);
    }

    .export-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s;
        text-decoration: none;
        color: var(--primary-color);
    }
    
    .export-item:last-child {
        border-bottom: none;
    }

    .export-item:hover {
        background-color: #F3F4F6; /* Light hover background */
    }

    .item-info {
        display: flex;
        align-items: center;
        text-align: left;
        flex-grow: 1;
    }

    .item-icon {
        font-size: 1.8em;
        margin-right: 20px;
        padding: 5px;
        border-radius: 6px;
        background-color: #E5E7EB; /* Light grey background for icon */
        color: var(--primary-color);
    }

    .item-title {
        font-size: 1em;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
    }

    .item-description {
        font-size: 0.8em;
        color: #6B7280;
        margin: 0;
        display: block;
        font-weight: 400;
    }
    
    .btn-download {
        padding: 8px 15px;
        font-size: 0.9em;
        font-weight: 600;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    /* Specific Colors */
    .color-green .item-icon, .color-green .btn-download { color: #10B981; }
    .color-indigo .item-icon, .color-indigo .btn-download { color: #6366F1; }
    .color-orange .item-icon, .color-orange .btn-download { color: #F97316; }
    .color-red .item-icon, .color-red .btn-download { color: #EF4444; }
    
    .color-green .btn-download { background-color: #10B981; color: white; }
    .color-indigo .btn-download { background-color: #6366F1; color: white; }
    .color-orange .btn-download { background-color: #F97316; color: white; }
    .color-red .btn-download { background-color: #EF4444; color: white; }
    
</style>

<h1 class="dashboard-title"><i class="fas fa-file-export"></i> Menu Export Data</h1>
<p>Pilih jenis data yang ingin Anda unduh dalam format CSV. Unduhan akan diproses di latar belakang.</p>

<div class="export-list-container">
    
    <a href="export_donatur.php" class="export-item color-green" target="_blank">
        <div class="item-info">
            <i class="fas fa-user-check item-icon"></i>
            <div>
                <span class="item-title">Data Donatur Registrasi</span>
                <span class="item-description">Daftar kontak donatur (Masuk)</span>
            </div>
        </div>
        <div class="btn btn-download">
            <i class="fas fa-download"></i> Unduh
        </div>
    </a>

    <a href="export_sumbangan.php" class="export-item color-indigo" target="_blank">
        <div class="item-info">
            <i class="fas fa-funnel-dollar item-icon"></i>
            <div>
                <span class="item-title">Data Sumbangan ZIS</span>
                <span class="item-description">Riwayat semua kwitansi donasi uang (Keluar)</span>
            </div>
        </div>
        <div class="btn btn-download">
            <i class="fas fa-download"></i> Unduh
        </div>
    </a>

    <a href="export_kotak_amal_list.php" class="export-item color-orange" target="_blank">
        <div class="item-info">
            <i class="fas fa-map-marked-alt item-icon"></i>
            <div>
                <span class="item-title">Data Kotak Amal</span>
                <span class="item-description">Daftar lokasi, alamat, dan data pemilik KA</span>
            </div>
        </div>
        <div class="btn btn-download">
            <i class="fas fa-download"></i> Unduh
        </div>
    </a>

    <a href="export_dana_kotak_amal.php" class="export-item color-red" target="_blank">
        <div class="item-info">
            <i class="fas fa-receipt item-icon"></i>
            <div>
                <span class="item-title">Data Pengambilan Dana Kotak Amal</span>
                <span class="item-description">Riwayat semua kwitansi pengambilan dana KA</span>
            </div>
        </div>
        <div class="btn btn-download">
            <i class="fas fa-download"></i> Unduh
        </div>
    </a>

</div>

<?php
include '../includes/footer.php';
$conn->close();
?>