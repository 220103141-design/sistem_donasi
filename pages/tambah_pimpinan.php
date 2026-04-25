<?php
session_start();
// File ini tidak lagi digunakan, dialihkan ke tambah_pengguna.php

if ($_SESSION['jabatan'] != 'Pimpinan') {
    die("Akses ditolak.");
}

// REDIRECT ke form tambah pengguna
header("Location: tambah_pengguna.php");
exit;
?>