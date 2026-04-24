<?php
session_start();
include '../config/database.php';

// Fungsi untuk format tanggal ke Bahasa Indonesia
function format_tanggal_indonesia($date_string) {
    if (!$date_string) return '';
    $bulan_indonesia = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $timestamp = strtotime($date_string);
    $day = date('d', $timestamp);
    $month_en = date('F', $timestamp);
    $year = date('Y', $timestamp);
    $month_id = $bulan_indonesia[$month_en] ?? $month_en;
    return $day . ' ' . $month_id . ' ' . $year;
}

// Fungsi helper untuk mendapatkan nama hari dalam Bahasa Indonesia
function get_nama_hari($date_string) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $hari[date('w', strtotime($date_string))];
}

// --- 1. OTORISASI & PENGAMBILAN ID KWITANSI ---

if (!isset($_SESSION['jabatan']) && !isset($_SESSION['is_pemilik_kotak_amal'])) {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['kwitansi'] ?? '';
if (empty($id_kwitansi)) {
    die("ID Kwitansi tidak ditemukan.");
}

// --- 2. PENGAMBILAN DATA LENGKAP (FIX COLLATIONS) ---

$sql = "SELECT 
            dka.ID_Kwitansi_KA, dka.Tgl_Ambil, dka.JmlUang,
            ka.Nama_Toko, ka.Alamat_Toko, ka.Nama_Pemilik AS Nama_Saksi, ka.WA_Pemilik,
            u.Nama_User AS Nama_Petugas, u.Jabatan AS Jabatan_Petugas,
            l.Nama_LKSA, l.Nama_Pimpinan AS Nama_Pimpinan_LKSA, l.ID_Kabupaten_Nama AS Kota_LKSA, l.Alamat AS Alamat_LKSA
        FROM Dana_KotakAmal dka
        JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal COLLATE utf8mb4_general_ci
        JOIN User u ON dka.Id_user = u.Id_user COLLATE utf8mb4_general_ci
        JOIN LKSA l ON dka.Id_lksa = l.Id_lksa COLLATE utf8mb4_general_ci
        WHERE dka.ID_Kwitansi_KA = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kwitansi);
if ($stmt === false) {
    die("Error saat menyiapkan kueri: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data Pengambilan Dana tidak ditemukan.");
}

// --- 3. VARIABEL DINAMIS ---

// FIX: Menggunakan ?? '' untuk mencegah null/Deprecated Notice
$nama_petugas = $data['Nama_Petugas'] ?? '';
$jabatan_petugas = $data['Jabatan_Petugas'] ?? '';
$nama_saksi = $data['Nama_Saksi'] ?? 'Pemilik Toko';
$nama_toko = $data['Nama_Toko'] ?? '';
$alamat_toko = $data['Alamat_Toko'] ?? '';

$id_kwitansi_ka = $data['ID_Kwitansi_KA'] ?? '';
$tgl_ambil_raw = $data['Tgl_Ambil'] ?? date('Y-m-d');
$nominal = number_format($data['JmlUang'] ?? 0, 0, ',', '.');
$terbilang = ''; 

$nama_lksa = $data['Nama_LKSA'] ?? '';
$nama_pimpinan = $data['Nama_Pimpinan_LKSA'] ?? 'Pimpinan LKSA'; // Nama Kepala Panti Otomatis
$kota_lksa = $data['Kota_LKSA'] ?? 'Surakarta';

// Format Tanggal
$nama_hari = get_nama_hari($tgl_ambil_raw);
$tanggal_ambil = format_tanggal_indonesia($tgl_ambil_raw);
$tanggal_cetak_raw = date('Y-m-d');
$tanggal_cetak = format_tanggal_indonesia($tanggal_cetak_raw);
$tahun_cetak = date('Y', strtotime($tanggal_cetak_raw));


// --- 4. HTTP HEADERS (OUTPUT SEBAGAI FILE WORD) ---

$filename = "Berita_Acara_" . $id_kwitansi_ka . ".doc";
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename={$filename}");
header("Pragma: no-cache");
header("Expires: 0");

// --- 5. STRUKTUR HTML UNTUK WORD (TATA LETAK DAN FORMAT FIX) ---
?>
<html>
<head>
    <meta charset="utf-8">
    <title>BERITA ACARA</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            width: 100%;
        }
        .container {
            width: 18cm; 
            margin: 0 auto;
        }
        .center {
            text-align: center;
        }
        .identitas td {
            padding: 2px 0;
            vertical-align: top;
        }
        .underline {
            text-decoration: underline;
        }
        .ba-detail td {
            padding: 5px 0;
        }
    </style>
</head>
<body>
<div class="container">

    <br><br><br><br> 

    <p class="center">
        <b>BERITA ACARA PENGAMBILAN DANA KOTAK AMAL</b>
    </p>
    
    <p style="text-indent: 0.5in;">Pada hari ini <b><?php echo htmlspecialchars($nama_hari); ?></b>, tanggal <b><?php echo htmlspecialchars($tanggal_ambil); ?></b> di <b><?php echo htmlspecialchars($kota_lksa); ?></b> telah dilaksanakan serah terima dana kotak amal dengan rincian sebagai berikut:</p>
    
    <p style="text-indent: 0.5in; margin-top: 20px;">1. Pihak-Pihak yang Terlibat:</p>
    <table class="identitas" style="margin-left: 1in; margin-top: 10px;">
        <tr>
            <td width="30%" colspan="3"><b>Penerima Dana</b></td>
        </tr>
        <tr>
            <td>Nama</td>
            <td>:</td>
            <td><b><?php echo htmlspecialchars($nama_petugas); ?></b></td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td><b><?php echo htmlspecialchars($jabatan_petugas); ?></b></td>
        </tr>
    </table>

    <table class="identitas" style="margin-left: 1in; margin-top: 10px;">
        <tr>
            <td width="30%" colspan="3"><b>Saksi</b></td>
        </tr>
        <tr>
            <td>Nama</td>
            <td>:</td>
            <td><b><?php echo htmlspecialchars($nama_saksi); ?></b></td>
        </tr>
        <tr>
            <td>Keterangan</td>
            <td>:</td>
            <td>Pemilik/Penanggung Jawab Lokasi <b><?php echo htmlspecialchars($nama_toko); ?></b></td>
        </tr>
    </table>

    <p style="text-indent: 0.5in; margin-top: 30px;">2. Rincian Pengambilan Dana:</p>
    <table class="ba-detail" style="margin-left: 1in; margin-top: 10px;">
        <tr>
            <td width="30%">Nomor Kwitansi KA</td>
            <td width="2%">:</td>
            <td><b><?php echo htmlspecialchars($id_kwitansi_ka); ?></b></td>
        </tr>
        <tr>
            <td>Lokasi Kotak Amal</td>
            <td>:</td>
            <td><b><?php echo htmlspecialchars($nama_toko); ?></b></td>
        </tr>
        <tr>
            <td>Alamat Lokasi</td>
            <td>:</td>
            <td><?php echo htmlspecialchars($alamat_toko); ?></td>
        </tr>
        <tr>
            <td>Nominal Uang</td>
            <td>:</td>
            <td><b>Rp <?php echo htmlspecialchars($nominal); ?></b></td>
        </tr>
        <tr>
            <td>(Terbilang)</td>
            <td>:</td>
            <td><?php echo htmlspecialchars($terbilang); ?></td>
        </tr>
    </table>
    
    <p style="text-indent: 0.5in; margin-top: 30px;">Dana tersebut telah diserahkan dari kotak amal ke Pihak Pertama dan akan dicatat sebagai pendapatan Panti Asuhan <b><?php echo htmlspecialchars($nama_lksa); ?></b>.</p>
    
    <p style="text-indent: 0.5in;">Demikian Berita Acara ini dibuat dengan sebenar-benarnya dan ditandatangani oleh kedua belah pihak sebagai bukti sah pengambilan dana.</p>

    <table style="width: 100%; margin-top: 50px; border-collapse: collapse;">
        <tr>
            <td colspan="3" style="text-align: center; border: none; padding: 0;">
                <?php echo htmlspecialchars($kota_lksa); ?>, <?php echo htmlspecialchars($tanggal_cetak); ?>
            </td>
        </tr>
        <tr>
            <td style="width: 35%; border: none;"></td> <td style="width: 30%; text-align: center; border: none; padding: 0;">
                Mengetahui,<br>
                <b>Kepala Panti Asuhan</b>
                <div style="height: 50px;"></div> (<span class="underline"><?php echo htmlspecialchars($nama_pimpinan); ?></span>)
            </td>
            <td style="width: 35%; border: none;"></td> </tr>
        <tr>
            <td colspan="3" style="border: none; height: 30px;"></td>
        </tr>
        <tr>
            <td style="width: 35%; text-align: center; border: none; padding: 0;">
                <b>Saksi</b>
            </td>
            <td style="width: 30%; border: none;"></td> <td style="width: 35%; text-align: center; border: none; padding: 0;">
                <b>Penerima</b>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; border: none; padding: 0; padding-top: 60px;">
                (<span class="underline"><?php echo htmlspecialchars($nama_saksi); ?></span>)
            </td>
            <td style="border: none;"></td> <td style="text-align: center; border: none; padding: 0; padding-top: 60px;">
                (<span class="underline"><?php echo htmlspecialchars($nama_petugas); ?></span>)
            </td>
        </tr>
    </table>

</div>
</body>
</html>