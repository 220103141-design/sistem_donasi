<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Cek hak akses
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id_kwitansi'] ?? '';
$sql = "SELECT d.NO_WA, d.Nama_Donatur, s.* FROM Sumbangan s JOIN Donatur d ON s.ID_donatur = d.ID_donatur WHERE s.ID_Kwitansi_ZIS = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kwitansi);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    $nomor_wa = $data['NO_WA'];
    $total_sumbangan = $data['Zakat_Profesi'] + $data['Zakat_Maal'] + $data['Infaq'] + $data['Sedekah'] + $data['Fidyah'];
    $pesan = "Assalamualaikum " . $data['Nama_Donatur'] . ".\n\n"
           . "Terima kasih atas donasi Anda.\n"
           . "Rincian:\n"
           . "- Zakat Profesi: Rp " . number_format($data['Zakat_Profesi']) . "\n"
           . "- Zakat Maal: Rp " . number_format($data['Zakat_Maal']) . "\n"
           . "- Infaq: Rp " . number_format($data['Infaq']) . "\n"
           . "- Sedekah: Rp " . number_format($data['Sedekah']) . "\n"
           . "- Fidyah: Rp " . number_format($data['Fidyah']) . "\n"
           . "Total: Rp " . number_format($total_sumbangan) . "\n\n"
           . "Semoga menjadi berkah. Hormat kami, " . $_SESSION['id_lksa'] . ".";
} else {
    $nomor_wa = "Tidak Ditemukan";
    $pesan = "Data sumbangan tidak ditemukan.";
}
?>
<div class="content">
    <div class="form-container">
        <h2>Tampilan WA Blast</h2>
        <p>Berikut adalah pesan yang akan dikirimkan kepada donatur.</p>
        <form action="#">
            <div class="form-section">
                <div class="form-group">
                    <label>Nomor WhatsApp:</label>
                    <input type="text" value="<?php echo htmlspecialchars($nomor_wa); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Isi Pesan:</label>
                    <textarea rows="10" cols="50" readonly><?php echo htmlspecialchars($pesan); ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <a href="#" class="btn btn-success" disabled>Kirim WA (Belum Aktif)</a>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>