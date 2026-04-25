<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check: Pimpinan, Kepala LKSA, dan Petugas Kotak Amal
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id'] ?? '';
if (empty($id_kwitansi)) {
    die("ID Kwitansi tidak ditemukan.");
}

// Ambil data pengambilan
$sql = "SELECT dka.*, ka.Nama_Toko FROM Dana_KotakAmal dka 
        JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
        WHERE ID_Kwitansi_KA = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kwitansi);
$stmt->execute();
$result = $stmt->get_result();
$data_pengambilan = $result->fetch_assoc();
$stmt->close();

if (!$data_pengambilan) {
    die("Data pengambilan tidak ditemukan.");
}

?>
<div class="content">
    <div class="form-container">
        <h1>Edit Pengambilan Kotak Amal</h1>
        <p>Koreksi jumlah uang yang diambil untuk kwitansi: <strong><?php echo htmlspecialchars($data_pengambilan['ID_Kwitansi_KA']); ?></strong> (Toko: <?php echo htmlspecialchars($data_pengambilan['Nama_Toko']); ?>)</p>
        <form action="proses_edit_dana_kotak_amal.php" method="POST">
            <input type="hidden" name="id_kwitansi" value="<?php echo htmlspecialchars($data_pengambilan['ID_Kwitansi_KA']); ?>">
            
            <div class="form-section">
                <h2>Nominal Pengambilan</h2>
                <div class="form-group">
                    <label>Jumlah Uang (Rp):</label>
                    <input type="number" name="jumlah_uang" value="<?php echo htmlspecialchars($data_pengambilan['JmlUang']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Ambil (Read-only):</label>
                    <input type="text" value="<?php echo htmlspecialchars($data_pengambilan['Tgl_Ambil']); ?>" readonly>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <a href="dana-kotak-amal.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>