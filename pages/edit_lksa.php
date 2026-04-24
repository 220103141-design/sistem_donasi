<?php
session_start();
include '../config/database.php';
$sidebar_stats = ''; // Pastikan sidebar tampil

// Authorization check: Hanya Pimpinan Pusat yang bisa mengakses halaman ini
if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak. Anda tidak memiliki izin untuk mengedit LKSA.");
}

$id_lksa_to_edit = $_GET['id'] ?? '';
if (empty($id_lksa_to_edit)) {
    die("ID LKSA tidak ditemukan.");
}

// Ambil data LKSA dari database
$sql = "SELECT * FROM LKSA WHERE Id_lksa = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_lksa_to_edit);
$stmt->execute();
$result = $stmt->get_result();
$data_lksa = $result->fetch_assoc();
$stmt->close();

if (!$data_lksa) {
    die("Data LKSA tidak ditemukan.");
}

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$logo_path = $data_lksa['Logo'] ? $base_url . 'assets/img/' . $data_lksa['Logo'] : $base_url . 'assets/img/yayasan.png';

include '../includes/header.php';
?>
<div class="content">
    <div class="form-container">
        <h1><i class="fas fa-edit" style="color: #06B6D4;"></i> Edit Data LKSA (<?php echo htmlspecialchars($data_lksa['Id_lksa']); ?>)</h1>
        <p>Perbarui informasi kantor cabang LKSA ini.</p>
        
        <form action="proses_lksa.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($data_lksa['Id_lksa']); ?>">
            <input type="hidden" name="logo_lama" value="<?php echo htmlspecialchars($data_lksa['Logo'] ?? ''); ?>">

            <div class="form-section">
                <h2>Informasi Utama</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama LKSA Lengkap:</label>
                        <input type="text" name="nama_lksa" value="<?php echo htmlspecialchars($data_lksa['Nama_LKSA'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Pimpinan (Tidak Dapat Diubah di Sini):</label>
                        <input type="text" value="<?php echo htmlspecialchars($data_lksa['Nama_Pimpinan'] ?? 'Belum Ditunjuk'); ?>" readonly style="background-color: #e9ecef;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat Lokasi (Untuk Kode ID LKSA):</label>
                    <input type="text" name="alamat_lksa" value="<?php echo htmlspecialchars($data_lksa['Alamat'] ?? ''); ?>" required>
                    <small style="color: #6B7280; display: block; margin-top: 5px;">Mengubah ini TIDAK akan mengubah ID LKSA.</small>
                </div>
            </div>

            <div class="form-section">
                <h2>Informasi Kontak</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nomor WA:</label>
                        <input type="text" name="nomor_wa_lksa" value="<?php echo htmlspecialchars($data_lksa['Nomor_WA'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email LKSA:</label>
                        <input type="email" name="email_lksa" value="<?php echo htmlspecialchars($data_lksa['Email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Logo LKSA</h2>
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo LKSA" style="height: 100px; width: auto; border: 1px solid #ddd; padding: 5px;">
                    <p style="font-size: 0.85em; color: #555;">Logo Saat Ini</p>
                </div>
                <div class="form-group">
                    <label>Unggah Logo Baru (Opsional, Max 5MB):</label>
                    <input type="file" name="logo" accept="image/*">
                    <small style="color: #6B7280; display: block; margin-top: 5px;">Kosongkan jika tidak ingin mengubah logo.</small>
                </div>
            </div>

            <div class="form-actions">
                <a href="lksa.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>