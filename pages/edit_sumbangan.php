<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id'] ?? '';
if (empty($id_kwitansi)) {
    die("ID Kwitansi tidak ditemukan.");
}

$sql = "SELECT * FROM Sumbangan WHERE ID_Kwitansi_ZIS = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kwitansi);
$stmt->execute();
$result = $stmt->get_result();
$data_sumbangan = $result->fetch_assoc();

if (!$data_sumbangan) {
    die("Data sumbangan tidak ditemukan.");
}

?>
<div class="content">
    <div class="form-container">
        <h1>Edit Nominal Sumbangan</h1>
        <p>Koreksi nominal sumbangan untuk kwitansi: <strong><?php echo htmlspecialchars($data_sumbangan['ID_Kwitansi_ZIS']); ?></strong></p>
        <form action="proses_edit_sumbangan.php" method="POST">
            <input type="hidden" name="id_kwitansi" value="<?php echo htmlspecialchars($data_sumbangan['ID_Kwitansi_ZIS']); ?>">
            
            <div class="form-section">
                <h2>Detail Sumbangan (dalam Rupiah)</h2>
                <div class="form-group">
                    <label>Zakat Profesi:</label>
                    <input type="number" name="zakat_profesi" value="<?php echo htmlspecialchars($data_sumbangan['Zakat_Profesi']); ?>">
                </div>
                <div class="form-group">
                    <label>Zakat Maal:</label>
                    <input type="number" name="zakat_maal" value="<?php echo htmlspecialchars($data_sumbangan['Zakat_Maal']); ?>">
                </div>
                <div class="form-group">
                    <label>Infaq:</label>
                    <input type="number" name="infaq" value="<?php echo htmlspecialchars($data_sumbangan['Infaq']); ?>">
                </div>
                <div class="form-group">
                    <label>Sedekah:</label>
                    <input type="number" name="sedekah" value="<?php echo htmlspecialchars($data_sumbangan['Sedekah']); ?>">
                </div>
                <div class="form-group">
                    <label>Fidyah:</label>
                    <input type="number" name="fidyah" value="<?php echo htmlspecialchars($data_sumbangan['Fidyah']); ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                <a href="verifikasi-donasi.php" class="btn btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>