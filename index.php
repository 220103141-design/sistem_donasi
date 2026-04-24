<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header("Location: login/login.php");
    exit;
}

switch ($_SESSION['jabatan']) {
    case 'Pimpinan':
        include 'dashboards/dashboard_pimpinan.php';
        break;
    case 'Kepala LKSA':
        include 'dashboards/dashboard_kepala_lksa.php';
        break;
    case 'Pegawai':
        include 'dashboards/dashboard_pegawai.php';
        break;
    case 'Petugas Kotak Amal':
        include 'dashboards/dashboard_petugas_kotak_amal.php';
        break;
    default:
        die("Dashboard tidak ditemukan untuk peran Anda.");
}
?>