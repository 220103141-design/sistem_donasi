<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_user = $_GET['id'] ?? '';
$sql = "SELECT * FROM User WHERE Id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$data_user = $result->fetch_assoc();

if (!$data_user) {
    die("Data pengguna tidak ditemukan.");
}

?>
<div class="content">
    <div class="form-container">
        <h1>Edit Pengguna</h1>
        <form action="proses_pengguna.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($data_user['Id_user']); ?>">
            <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($data_user['Foto']); ?>">
            <div class="form-section">
                <h2>Informasi Pengguna</h2>
                <div class="form-group">
                    <label>Nama User:</label>
                    <input type="text" name="nama_user" value="<?php echo htmlspecialchars($data_user['Nama_User']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Jabatan:</label>
                    <select name="jabatan" required>
                        <option value="Kepala LKSA" <?php echo ($data_user['Jabatan'] == 'Kepala LKSA') ? 'selected' : ''; ?>>Kepala LKSA</option>
                        <option value="Pegawai" <?php echo ($data_user['Jabatan'] == 'Pegawai') ? 'selected' : ''; ?>>Pegawai</option>
                        <option value="Petugas Kotak Amal" <?php echo ($data_user['Jabatan'] == 'Petugas Kotak Amal') ? 'selected' : ''; ?>>Petugas Kotak Amal</option>
                        
                        <?php if ($data_user['Jabatan'] == 'Pimpinan') { ?>
                             <option value="Pimpinan" selected style="background-color: #f0f0f0;">Pimpinan (Tidak Dapat Diubah)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID LKSA:</label>
                    <input type="text" name="id_lksa" value="<?php echo htmlspecialchars($data_user['Id_lksa']); ?>">
                </div>
                <div class="form-group">
                    <label>Foto Saat Ini:</label>
                    <?php if ($data_user['Foto']) { ?>
                        <img src="../assets/img/<?php echo htmlspecialchars($data_user['Foto']); ?>" alt="Foto Profil" style="width: 100px; height: 100px; object-fit: cover;">
                    <?php } else { ?>
                        <p>Belum ada foto.</p>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label>Unggah Foto Baru:</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                <a href="users.php" class="btn btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>