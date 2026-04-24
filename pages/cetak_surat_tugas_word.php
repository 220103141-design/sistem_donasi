<?php
session_start();
include '../config/database.php';

// Fungsi untuk format tanggal ke Bahasa Indonesia
function format_tanggal_indonesia($date_string) {
    if (!$date_string) return '-';
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


// --- 1. OTORISASI & PENGAMBILAN ID SURAT TUGAS ---

if (!isset($_SESSION['jabatan']) || !in_array($_SESSION['jabatan'], ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak. Silakan login sebagai pengguna internal.");
}

$id_surat_tugas = $_GET['id_tugas'] ?? '';

if (empty($id_surat_tugas)) {
    die("ID Surat Tugas tidak ditemukan.");
}

// --- 2. PENGAMBILAN DATA DARI DATABASE (Gabungan 4 Tabel dengan FIX COLLATIONS) ---

$sql = "SELECT 
            st.ID_Surat_Tugas, st.Tgl_Mulai_Tugas, 
            ka.Nama_Toko, ka.Alamat_Toko, ka.ID_LKSA, 
            l.Nama_LKSA, l.ID_Kabupaten_Nama AS Kota_LKSA, l.Alamat AS Alamat_LKSA,
            u_pembuat.Nama_User AS Nama_Pembuat,
            u_pembuat.Jabatan AS Jabatan_Pembuat,
            u_petugas.Nama_User AS Nama_Petugas,
            u_petugas.Jabatan AS Jabatan_Petugas
        FROM SuratTugas st
        JOIN KotakAmal ka ON st.ID_KotakAmal = ka.ID_KotakAmal COLLATE utf8mb4_general_ci
        JOIN LKSA l ON ka.Id_lksa = l.Id_lksa COLLATE utf8mb4_general_ci
        JOIN User u_pembuat ON st.ID_user = u_pembuat.Id_user COLLATE utf8mb4_general_ci
        /* Perbaikan: Menambahkan COLLATE pada kolom Id_user yang dibandingkan dengan parameter */
        LEFT JOIN User u_petugas ON u_petugas.Id_user COLLATE utf8mb4_general_ci = ? 
        WHERE st.ID_Surat_Tugas = ?";

$stmt = $conn->prepare($sql);
$id_user_session = $_SESSION['id_user'] ?? 'N/A';
$stmt->bind_param("ss", $id_user_session, $id_surat_tugas); 
if ($stmt === false) {
    die("Error saat menyiapkan kueri: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data Surat Tugas tidak ditemukan.");
}

// --- 3. VARIABEL DINAMIS ---

$nama_petugas_ditugaskan = $data['Nama_Petugas'] ?? ($_SESSION['nama_user'] ?? 'Petugas Kotak Amal');
$jabatan_petugas = $data['Jabatan_Petugas'] ?? ($_SESSION['jabatan'] ?? 'Petugas Pengambilan Kotak Amal');
$alamat_petugas_tugas = $data['Alamat_LKSA'] ?? 'Alamat Kantor LKSA'; 
$id_surat = $data['ID_Surat_Tugas'];
$id_lksa = $data['ID_LKSA'];
$nama_lksa = $data['Nama_LKSA'];
$nama_pembuat = $data['Nama_Pembuat']; 
$jabatan_pembuat = $data['Jabatan_Pembuat'];
$nama_toko = $data['Nama_Toko'];
$alamat_toko = $data['Alamat_Toko'];
$kota_lksa = $data['Kota_LKSA'] ?? 'Surakarta';

// Format Tanggal
$tgl_mulai_tugas = format_tanggal_indonesia($data['Tgl_Mulai_Tugas']);
$tgl_selesai_prediksi = format_tanggal_indonesia(date('Y-m-d', strtotime($data['Tgl_Mulai_Tugas'] . ' + 3 days'))); 
$tanggal_cetak_raw = date('Y-m-d');
$tanggal_cetak = format_tanggal_indonesia($tanggal_cetak_raw);
$tahun_cetak = date('Y', strtotime($tanggal_cetak_raw));


// --- 4. HTTP HEADERS (OUTPUT SEBAGAI FILE WORD) ---

$filename = "Surat_Tugas_" . $id_surat . ".doc";
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename={$filename}");
header("Pragma: no-cache");
header("Expires: 0");

// --- 5. STRUKTUR HTML UNTUK WORD (TATA LETAK DAN FORMAT FIX) ---
?>
<html>
<head>
    <meta charset="utf-8">
    <title>SURAT TUGAS</title>
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
            white-space: nowrap;
        }
        .identitas-value {
            white-space: normal;
        }
        .ttd-area {
            width: 45%; 
            float: right;
            margin-top: 30px;
            text-align: center;
        }
        ol li {
             margin-bottom: 5px;
        }
        h2 {
            font-size: 14pt;
            text-decoration: underline;
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">

    <br><br><br><br> 

    <p class="center">
        <b>SURAT TUGAS</b>
    </p>
    
    <p style="text-align: right;">
        Nomor: <?php echo htmlspecialchars($id_surat); ?>/ST/PA/<?php echo htmlspecialchars($id_lksa); ?>/20<?php echo date('y', strtotime($tahun_cetak)); ?>
    </p>
    
    <p style="text-indent: 0.5in;">Yang bertanda tangan di bawah ini:</p>
    <table class="identitas" style="margin-left: 0.5in;">
        <tr>
            <td width="15%">Nama</td>
            <td width="2%">:</td>
            <td class="identitas-value"><b><?php echo htmlspecialchars($nama_pembuat); ?></b></td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td class="identitas-value"><b><?php echo htmlspecialchars($jabatan_pembuat); ?></b></td>
        </tr>
    </table>

    <p style="text-indent: 0.5in; margin-top: 10px;">Dengan ini memberikan tugas kepada:</p>
    <table class="identitas" style="margin-left: 0.5in;">
        <tr>
            <td>Nama</td>
            <td>:</td>
            <td class="identitas-value"><b><?php echo htmlspecialchars($nama_petugas_ditugaskan); ?></b></td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td class="identitas-value"><b><?php echo htmlspecialchars($jabatan_petugas); ?></b></td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td class="identitas-value"><b><?php echo htmlspecialchars($alamat_petugas_tugas); ?></b></td>
        </tr>
    </table>

    <p style="text-indent: 0.5in; margin-top: 10px;">Untuk melaksanakan tugas pengambilan dan pencatatan kotak amal milik Panti Asuhan <b><?php echo htmlspecialchars($nama_lksa); ?></b> yang ditempatkan di:</p>
    
    <p style="margin-top: 10px; margin-left: 0.5in;">
        1. <b><?php echo htmlspecialchars($nama_toko); ?></b> (Alamat: <?php echo htmlspecialchars($alamat_toko); ?>)
    </p>
    <p style="text-indent: 0.5in;">Adapun tugas yang harus dilaksanakan meliputi:</p>
    <ol style="margin-top: 10px; margin-left: 0.5in;">
        <li>Mengambil kotak amal sesuai jadwal yang telah ditentukan.</li>
        <li>Mengamankan dan membawa hasil donasi ke kantor panti asuhan.</li>
        <li>Melakukan pencatatan jumlah donasi pada formulir yang telah disediakan.</li>
        <li>Menyerahkan laporan hasil pengambilan kepada bagian administrasi.</li>
        <li>Menjaga etika, kejujuran, dan nama baik panti selama bertugas.</li>
    </ol>

    <p style="text-indent: 0.5in;">Surat tugas ini berlaku sejak tanggal <b><?php echo htmlspecialchars($tgl_mulai_tugas); ?></b> hingga <b><?php echo htmlspecialchars($tgl_selesai_prediksi); ?></b> atau sampai tugas selesai dilaksanakan dengan baik.</p>

    <p style="text-indent: 0.5in;">Demikian surat tugas ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    
    <div class="ttd-area">
        <p>
            <?php echo htmlspecialchars($kota_lksa); ?>, <?php echo htmlspecialchars($tanggal_cetak); ?>
        </p>
        <p style="margin-top: 20px; margin-bottom: 70px;">
            <b>Kepala Panti Asuhan</b>
        </p>
        <p style="margin-top: 20px;">
            (<b><?php echo htmlspecialchars($nama_pembuat); ?></b>)
        </p>
    </div>

</div>
</body>
</html>