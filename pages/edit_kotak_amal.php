<?php
session_start();
include '../config/database.php';
// Set sidebar_stats ke string kosong sebelum memuat header
$sidebar_stats = ''; 

// Ambil data sesi dengan penanganan untuk mencegah Undefined array key warning
$jabatan_user = $_SESSION['jabatan'] ?? '';
$id_lksa_session = $_SESSION['id_lksa'] ?? '';

// Authorization check: Hanya Pimpinan, Kepala LKSA, dan Petugas Kotak Amal yang bisa mengakses
if (!in_array($jabatan_user, ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kotak_amal = $_GET['id'] ?? '';
if (empty($id_kotak_amal)) {
    die("ID Kotak Amal tidak ditemukan.");
}

include '../includes/header.php';

// Ambil data Kotak Amal dari database (TERMASUK KOLOM Google_Maps_Link)
$sql = "SELECT ka.*, ka.Google_Maps_Link FROM KotakAmal ka WHERE ka.ID_KotakAmal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kotak_amal);
$stmt->execute();
$result = $stmt->get_result();
$data_ka = $result->fetch_assoc();
$stmt->close();

if (!$data_ka) {
    die("Data Kotak Amal tidak ditemukan.");
}

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$foto_ka = $data_ka['Foto'] ?? '';
// Menggunakan gambar default yayasan.png
$foto_path = $foto_ka ? $base_url . 'assets/img/' . $foto_ka : $base_url . 'assets/img/yayasan.png'; 
?>

<div class="content" style="padding: 0; background: none; box-shadow: none;">
    <div class="form-container">
        <h1><i class="fas fa-edit"></i> Edit Data Kotak Amal (<?php echo htmlspecialchars($data_ka['ID_KotakAmal']); ?>)</h1>
        <p>Perbarui informasi lokasi dan pemilik kotak amal.</p>

        <form action="proses_edit_kotak_amal.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_kotak_amal" value="<?php echo htmlspecialchars($data_ka['ID_KotakAmal']); ?>">
            <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data_ka['Foto'] ?? ''); ?>">
            
            <div class="form-section">
                <h2><i class="fas fa-box"></i> Informasi Lokasi</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Toko/Lokasi:</label>
                        <input type="text" name="nama_toko" value="<?php echo htmlspecialchars($data_ka['Nama_Toko']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Jadwal Pengambilan:</label>
                        <input type="text" name="jadwal_pengambilan" value="<?php echo htmlspecialchars($data_ka['Jadwal_Pengambilan']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat Toko:</label>
                    <textarea name="alamat_toko" rows="3" cols="50"><?php echo htmlspecialchars($data_ka['Alamat_Toko']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Link Google Maps (URL):</label>
                    <input type="url" name="google_maps_link" value="<?php echo htmlspecialchars($data_ka['Google_Maps_Link'] ?? ''); ?>" placeholder="Contoh: https://maps.app.goo.gl/...">
                </div>
                <div class="form-group">
                    <label>Keterangan Tambahan:</label>
                    <textarea name="keterangan" rows="3" cols="50"><?php echo htmlspecialchars($data_ka['Ket']); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-user-circle"></i> Data Kontak Pemilik</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Pemilik:</label>
                        <input type="text" name="nama_pemilik" value="<?php echo htmlspecialchars($data_ka['Nama_Pemilik']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp Pemilik:</label>
                        <input type="text" name="wa_pemilik" value="<?php echo htmlspecialchars($data_ka['WA_Pemilik']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Pemilik:</label>
                    <input type="email" name="email_pemilik" value="<?php echo htmlspecialchars($data_ka['Email']); ?>">
                </div>
            </div>
            
            <div class="form-section">
                <h2><i class="fas fa-camera"></i> Foto Kotak Amal</h2>
                <div class="form-group" style="text-align: center;">
                    <img src="../assets/img/<?php echo htmlspecialchars($data_ka['Foto'] ?? 'yayasan.png'); ?>" alt="Foto Kotak Amal" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 4px solid #F97316; margin-bottom: 10px;">
                </div>
                
                <div class="form-group">
                    <label>Unggah Foto Baru (Max 5MB, JPG/PNG/GIF):</label>
                    <input type="file" name="foto" accept="image/*">
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">Kosongkan jika tidak ingin mengubah foto.</small>
                </div>
            </div>

            <div class="form-actions">
                <a href="kotak-amal.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>