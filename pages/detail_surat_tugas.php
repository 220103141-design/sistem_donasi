<?php
session_start();
include '../config/database.php';
$sidebar_stats = ''; 
include '../includes/header.php';

// Authorization check: Semua yang bisa melihat KA harus bisa melihat ST-nya
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_surat_tugas = $_GET['id_tugas'] ?? '';
if (empty($id_surat_tugas)) {
    die("ID Surat Tugas tidak ditemukan.");
}

// Query untuk mengambil data lengkap Surat Tugas
// FIX 1: Menambahkan COLLATE utf8mb4_general_ci pada JOIN ID_KotakAmal dan ID_user
$sql = "SELECT st.*, ka.Nama_Toko, ka.Alamat_Toko, ka.Nama_Pemilik, ka.WA_Pemilik, u.Nama_User AS Nama_Pembuat 
        FROM SuratTugas st
        JOIN KotakAmal ka ON st.ID_KotakAmal = ka.ID_KotakAmal COLLATE utf8mb4_general_ci
        JOIN LKSA l ON ka.Id_lksa = l.Id_lksa COLLATE utf8mb4_general_ci
        JOIN User u ON st.ID_user = u.Id_user COLLATE utf8mb4_general_ci
        WHERE st.ID_Surat_Tugas = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_surat_tugas);
$stmt->execute();
$result = $stmt->get_result();
$data_st = $result->fetch_assoc();
$stmt->close();

if (!$data_st) {
    die("Data Surat Tugas tidak ditemukan.");
}

// Logika untuk menampilkan nama petugas klaim (asumsi sudah terekam di Dana_KotakAmal saat tugas Selesai)
$nama_petugas_klaim = 'Belum Diklaim'; 
$tgl_selesai = 'N/A';
$jml_uang_ambil = 'N/A';

if ($data_st['Status_Tugas'] == 'Selesai') {
    // FIX 2: Menambahkan COLLATE utf8mb4_general_ci pada JOIN ID_user di klaim
    $sql_klaim = "SELECT dka.Tgl_Ambil, dka.JmlUang, u.Nama_User 
                  FROM Dana_KotakAmal dka
                  JOIN User u ON dka.Id_user = u.Id_user COLLATE utf8mb4_general_ci
                  WHERE dka.ID_KotakAmal = ? 
                  ORDER BY dka.Tgl_Ambil DESC LIMIT 1"; 
    
    $stmt_klaim = $conn->prepare($sql_klaim);
    $stmt_klaim->bind_param("s", $data_st['ID_KotakAmal']);
    $stmt_klaim->execute();
    $result_klaim = $stmt_klaim->get_result();
    $data_klaim = $result_klaim->fetch_assoc();
    $stmt_klaim->close();

    if ($data_klaim) {
        $nama_petugas_klaim = $data_klaim['Nama_User'];
        $tgl_selesai = date('d M Y H:i', strtotime($data_klaim['Tgl_Ambil']));
        $jml_uang_ambil = 'Rp ' . number_format($data_klaim['JmlUang']);
    }
}


?>
<style>
    /* Styling for Detail Surat Tugas */
    .detail-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-left: 5px solid #F97316; /* Orange accent */
    }
    .detail-container h1 {
        font-size: 2.0em;
        color: #F97316;
        border-bottom: 2px solid #E5E7EB;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .data-table {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }
    .data-table th, .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #F3F4F6;
    }
    .data-table th {
        width: 35%;
        background-color: #F9FAFB;
        color: #4B5563;
        font-weight: 600;
    }
    .status-active { color: #10B981; font-weight: 700; }
    .status-completed { color: #06B6D4; font-weight: 700; }
    .status-cancelled { color: #EF4444; font-weight: 700; }

    .btn-action-view {
        background-color: #6B7280;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-action-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-download-word {
        background-color: #3B82F6; /* Blue for Word */
    }
    .btn-download-word:hover {
        background-color: #2563EB;
    }

</style>

<div class="detail-container">
    <h1><i class="fas fa-file-signature" style="color: #F97316;"></i> Detail Surat Tugas: <?php echo htmlspecialchars($data_st['ID_Surat_Tugas']); ?></h1>
    
    <table class="data-table">
        <tr>
            <th>Status Tugas</th>
            <td>
                <?php 
                    $status_class = '';
                    switch ($data_st['Status_Tugas']) {
                        case 'Aktif': $status_class = 'status-active'; break;
                        case 'Selesai': $status_class = 'status-completed'; break;
                        case 'Batal': $status_class = 'status-cancelled'; break;
                    }
                ?>
                <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($data_st['Status_Tugas']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Dibuat Oleh (Pemberi Tugas)</th>
            <td><strong><?php echo htmlspecialchars($data_st['Nama_Pembuat']); ?></strong> (<?php echo htmlspecialchars($data_st['ID_user']); ?>)</td>
        </tr>
        <tr>
            <th>Tanggal Dibuat</th>
            <td><?php echo date('d M Y H:i:s', strtotime($data_st['Tgl_Mulai_Tugas'])); ?></td>
        </tr>
    </table>
    
    <h2 style="margin-top: 30px; font-size: 1.5em; color: #1F2937;"><i class="fas fa-map-marker-alt"></i> Lokasi Tujuan</h2>
    <table class="data-table">
        <tr>
            <th>Nama Tempat</th>
            <td><?php echo htmlspecialchars($data_st['Nama_Toko']); ?></td>
        </tr>
        <tr>
            <th>Alamat</th>
            <td><?php echo htmlspecialchars($data_st['Alamat_Toko']); ?></td>
        </tr>
        <tr>
            <th>Nama Pemilik</th>
            <td><?php echo htmlspecialchars($data_st['Nama_Pemilik'] ?? '-'); ?></td>
        </tr>
    </table>

    <h2 style="margin-top: 30px; font-size: 1.5em; color: #1F2937;"><i class="fas fa-clipboard-check"></i> Status Eksekusi</h2>
    <table class="data-table">
        <tr>
            <th>Petugas Pelaksana (Klaim)</th>
            <td><strong><?php echo htmlspecialchars($nama_petugas_klaim); ?></strong></td>
        </tr>
        <tr>
            <th>Tanggal Selesai</th>
            <td><?php echo $tgl_selesai; ?></td>
        </tr>
        <tr>
            <th>Nominal Diambil</th>
            <td><?php echo $jml_uang_ambil; ?></td>
        </tr>
    </table>
    
    <div class="form-actions" style="justify-content: space-between; margin-top: 30px;">
        <a href="dana-kotak-amal.php" class="btn btn-action-view"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Pengambilan</a>
        
        <a href="cetak_surat_tugas_word.php?id_tugas=<?php echo htmlspecialchars($data_st['ID_Surat_Tugas']); ?>" 
           class="btn btn-action-view btn-download-word" 
           target="_blank" 
           title="Download Surat Tugas dalam format Word">
            <i class="fas fa-file-word"></i> Download Surat Tugas (Word)
        </a>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>