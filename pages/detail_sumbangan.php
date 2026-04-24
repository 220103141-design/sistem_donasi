<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Verifikasi otorisasi
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Pegawai') {
    die("Akses ditolak.");
}

$id_kwitansi = $_GET['id'] ?? '';
if (empty($id_kwitansi)) {
    die("ID Kwitansi tidak ditemukan.");
}

// Ambil data sumbangan, donatur, dan pengguna yang menginput
$sql = "SELECT s.*, d.Nama_Donatur, d.NO_WA, u.Nama_User 
        FROM Sumbangan s 
        LEFT JOIN Donatur d ON s.ID_donatur = d.ID_donatur 
        LEFT JOIN User u ON s.ID_user = u.Id_user 
        WHERE s.ID_Kwitansi_ZIS = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error saat menyiapkan kueri: " . $conn->error);
}
$stmt->bind_param("s", $id_kwitansi);
$stmt->execute();
$result = $stmt->get_result();
$data_sumbangan = $result->fetch_assoc();

if (!$data_sumbangan) {
    die("Data sumbangan tidak ditemukan.");
}
?>
<style>
/* CSS UNTUK RESPONSIVITAS TABEL DETAIL (CARD VIEW) */
@media (max-width: 600px) {
    /* Menyembunyikan tampilan header tabel standar di mobile */
    .responsive-detail thead {
        display: none;
    }
    
    /* Membuat baris tabel menjadi blok (card) */
    .responsive-detail tr {
        display: block;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    /* Membuat sel (td/th) menjadi baris detail */
    .responsive-detail td, .responsive-detail th {
        display: flex;
        justify-content: space-between;
        padding: 10px 15px;
        width: auto;
        border: none;
        border-bottom: 1px dashed #eee;
        background-color: #fff;
    }
    
    .responsive-detail tr:last-child { /* Khusus untuk baris TOTAL */
        font-weight: bold;
        background-color: #f0f8ff;
    }
    .responsive-detail tr:last-child td {
        background-color: transparent;
    }
    
    /* Menampilkan label data dari atribut data-label */
    .responsive-detail td::before, .responsive-detail th::before {
        content: attr(data-label);
        font-weight: bold;
        color: #555;
        margin-right: 10px;
        text-align: left;
    }

    /* Memperbaiki tampilan tabel pertama (Informasi Umum) agar tumpuk vertikal */
    .form-container table:first-of-type {
        display: block;
        box-shadow: none; /* Hilangkan shadow tabel */
        border: none;
        border-radius: 0;
    }
    .form-container table:first-of-type tr {
        display: flex;
        flex-direction: column;
        border: none;
        box-shadow: none;
        margin-bottom: 0;
    }
    .form-container table:first-of-type th, .form-container table:first-of-type td {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border: none;
        border-bottom: 1px dashed #eee;
    }
    .form-container table:first-of-type tr:last-child td {
        border-bottom: none;
    }
}
</style>
<div class="content">
    <h1>Detail Sumbangan</h1>
    <p>Berikut adalah rincian lengkap dari transaksi sumbangan ini.</p>

    <div class="form-container">
        <table style="width: 100%;">
            <tr>
                <th>No. Kwitansi</th>
                <td><?php echo htmlspecialchars($data_sumbangan['ID_Kwitansi_ZIS']); ?></td>
            </tr>
            <tr>
                <th>Tanggal</th>
                <td><?php echo htmlspecialchars($data_sumbangan['Tgl']); ?></td>
            </tr>
            <tr>
                <th>Donatur</th>
                <td><?php echo htmlspecialchars($data_sumbangan['Nama_Donatur']); ?></td>
            </tr>
            <tr>
                <th>Nomor WA Donatur</th>
                <td><?php echo htmlspecialchars($data_sumbangan['NO_WA']); ?></td>
            </tr>
            <tr>
                <th>Dibuat Oleh</th>
                <td><?php echo htmlspecialchars($data_sumbangan['Nama_User']); ?></td>
            </tr>
        </table>

        <br>

        <h2>Rincian Dana</h2>
        <table style="width: 100%;" class="responsive-detail">
            <thead>
                <tr>
                    <th>Jenis Sumbangan</th>
                    <th>Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="Jenis Sumbangan">Zakat Profesi</td>
                    <td data-label="Jumlah (Rp)"><?php echo number_format($data_sumbangan['Zakat_Profesi']); ?></td>
                </tr>
                <tr>
                    <td data-label="Jenis Sumbangan">Zakat Maal</td>
                    <td data-label="Jumlah (Rp)"><?php echo number_format($data_sumbangan['Zakat_Maal']); ?></td>
                </tr>
                <tr>
                    <td data-label="Jenis Sumbangan">Infaq</td>
                    <td data-label="Jumlah (Rp)"><?php echo number_format($data_sumbangan['Infaq']); ?></td>
                </tr>
                <tr>
                    <td data-label="Jenis Sumbangan">Sedekah</td>
                    <td data-label="Jumlah (Rp)"><?php echo number_format($data_sumbangan['Sedekah']); ?></td>
                </tr>
                <tr>
                    <td data-label="Jenis Sumbangan">Fidyah</td>
                    <td data-label="Jumlah (Rp)"><?php echo number_format($data_sumbangan['Fidyah']); ?></td>
                </tr>
                <?php if (!empty($data_sumbangan['Natura'])) { ?>
                    <tr>
                        <td data-label="Jenis Sumbangan">Natura (Barang)</td>
                        <td data-label="Detail Natura"><?php echo htmlspecialchars($data_sumbangan['Natura']); ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <th data-label="Rincian">Total</th>
                    <th data-label="Total Nominal">Rp <?php echo number_format($data_sumbangan['Zakat_Profesi'] + $data_sumbangan['Zakat_Maal'] + $data_sumbangan['Infaq'] + $data_sumbangan['Sedekah'] + $data_sumbangan['Fidyah']); ?></th>
                </tr>
            </tbody>
        </table>

        <br>
        <div class="form-actions" style="justify-content: flex-start;">
            <a href="sumbangan.php" class="btn btn-cancel">Kembali ke Daftar Sumbangan</a>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>