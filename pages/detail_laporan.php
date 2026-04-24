<?php
session_start();
include '../config/database.php';
// Include header di sini, jangan lupa set $sidebar_stats = '';
$sidebar_stats = ''; 
include '../includes/header.php';

// Otorisasi: Hanya Pimpinan dan Kepala LKSA yang bisa melihat
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$id_laporan = $_GET['id'] ?? '';
if (empty($id_laporan)) {
    die("ID Laporan tidak ditemukan.");
}

// Query untuk mengambil data laporan
$sql = "SELECT * FROM Laporan l WHERE l.ID_Laporan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data laporan tidak ditemukan.");
}

// --- LOGIKA UPDATE STATUS ---
if ($data['Status_Baca'] == 'Belum Dibaca') {
    $update_sql = "UPDATE Laporan SET Status_Baca = 'Sudah Dibaca' WHERE ID_Laporan = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $id_laporan);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Perbarui data yang ditampilkan tanpa reload
    $data['Status_Baca'] = 'Sudah Dibaca';
}

// Logika pencarian nama pelapor
$id_pelapor = $data['ID_user_pelapor'];
$pelapor_type = $data['Pelapor_Type'];
$nama_pelapor = 'N/A';

if ($pelapor_type == 'USER') {
    $name_sql = "SELECT Nama_User FROM User WHERE Id_user = ?";
    $table_name = 'User Internal'; // Updated for better display
} elseif ($pelapor_type == 'DONATUR') {
    $name_sql = "SELECT Nama_Donatur AS Nama_User FROM Donatur WHERE ID_Donatur = ?";
    $table_name = 'Donatur';
} elseif ($pelapor_type == 'PEMILIK_KA') {
    $name_sql = "SELECT Nama_Pemilik AS Nama_User FROM KotakAmal WHERE ID_KotakAmal = ?";
    $table_name = 'Pemilik Kotak Amal';
} else {
    $table_name = 'Tidak Diketahui';
}

if (isset($name_sql)) {
    $name_stmt = $conn->prepare($name_sql);
    $name_stmt->bind_param("s", $id_pelapor);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    $nama_pelapor = $name_result->fetch_assoc()['Nama_User'] ?? "{$table_name} Tidak Ditemukan";
    $name_stmt->close();
}
?>

<style>
    /* Mengadopsi font dari header/style.css */
    .detail-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px;
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        font-family: 'Open Sans', sans-serif;
    }
    .detail-container h1 {
        font-family: 'Montserrat', sans-serif;
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 20px;
        font-size: 2.2em;
        border-bottom: 3px solid #f0f0f0;
        padding-bottom: 15px;
    }
    .detail-section {
        margin-bottom: 30px;
        padding: 20px;
        border-radius: 10px;
        background-color: #f8f9fa;
        border-left: 5px solid #2c3e50;
    }
    .detail-info {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 10px 20px;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .detail-info:last-of-type {
        border-bottom: none;
        padding-bottom: 0;
    }
    .detail-label {
        font-weight: 600;
        color: #555;
        font-size: 0.95em;
    }
    .detail-value {
        font-weight: 400;
        color: #34495e;
    }
    .message-box {
        border: 1px solid #e0e0e0;
        padding: 25px;
        border-radius: 10px;
        background-color: #ffffff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        white-space: pre-wrap;
        font-size: 1em;
        line-height: 1.6;
    }
    .message-box h2 {
        font-family: 'Montserrat', sans-serif;
        color: #2c3e50;
        font-size: 1.5em;
        margin-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 5px;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 5px;
        color: white;
        font-weight: 700;
        font-size: 0.85em;
        display: inline-block;
    }
    .status-unread { background-color: #e74c3c; }
    .status-read { background-color: #2ecc71; }
    .btn-kembali {
        background-color: #2c3e50;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: background-color 0.3s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .btn-kembali:hover {
        background-color: #34495e;
        transform: translateY(-2px);
    }
</style>
<div class="main-content-area">
<div class="detail-container">
    <h1><i class="fas fa-envelope-open-text" style="color: #2c3e50;"></i> Detail Laporan Pengguna</h1>
    
    <div class="detail-section" style="border-left: 5px solid #2c3e50; background-color: #f0f8ff;">
        <div class="detail-info">
            <span class="detail-label">Status Laporan</span>
            <span class="detail-value">
                <span class="status-badge <?php echo ($data['Status_Baca'] == 'Sudah Dibaca') ? 'status-read' : 'status-unread'; ?>">
                    <?php echo htmlspecialchars($data['Status_Baca']); ?>
                </span>
            </span>
        </div>
        <div class="detail-info">
            <span class="detail-label">ID Laporan</span>
            <span class="detail-value"><?php echo htmlspecialchars($data['ID_Laporan']); ?></span>
        </div>
        <div class="detail-info">
            <span class="detail-label">Tanggal Dikirim</span>
            <span class="detail-value"><?php echo date('d M Y H:i:s', strtotime($data['Tgl_Lapor'])); ?></span>
        </div>
        <div class="detail-info" style="border-bottom: none;">
            <span class="detail-label">LKSA Tujuan</span>
            <span class="detail-value"><?php echo htmlspecialchars($data['ID_LKSA']); ?></span>
        </div>
    </div>
    
    <div class="detail-section" style="border-left: 5px solid #3498db; background-color: #f8f9fa;">
        <div class="detail-info">
            <span class="detail-label">Pelapor</span>
            <span class="detail-value">
                <?php echo htmlspecialchars($nama_pelapor); ?>
                <small style="color:#6c757d;">(<?php echo htmlspecialchars($table_name); ?>)</small>
            </span>
        </div>
        <div class="detail-info">
            <span class="detail-label">ID Pelapor</span>
            <span class="detail-value"><?php echo htmlspecialchars($data['ID_user_pelapor']); ?></span>
        </div>
        <div class="detail-info" style="border-bottom: none;">
            <span class="detail-label">Subjek Laporan</span>
            <span class="detail-value" style="font-weight: 600;"><?php echo htmlspecialchars($data['Subjek']); ?></span>
        </div>
    </div>

    <div class="message-box" style="margin-top: 30px;">
        <h2>Pesan Lengkap</h2>
        <p><?php echo htmlspecialchars($data['Pesan']); ?></p>
    </div>

    <div class="form-actions" style="justify-content: flex-start; margin-top: 30px;">
        <a href="laporan.php" class="btn-kembali"><i class="fas fa-arrow-left"></i> Kembali ke Kotak Masuk</a>
    </div>
</div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>