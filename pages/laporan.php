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

$jabatan_user = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// Kueri SQL untuk mengambil laporan (dengan Pelapor_Type)
$sql = "SELECT * FROM Laporan l";

$params = [];
$types = "";

// Filter laporan berdasarkan LKSA jika bukan Pimpinan Pusat
if ($jabatan_user == 'Kepala LKSA' || ($jabatan_user == 'Pimpinan' && $id_lksa != 'Pimpinan_Pusat')) {
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " WHERE l.ID_LKSA = ?";
    $params[] = $id_lksa;
    $types = "s";
}

$sql .= " ORDER BY l.Tgl_Lapor DESC";

// Eksekusi Kueri Utama
$stmt_laporan = $conn->prepare($sql);

if (!empty($params)) {
    $stmt_laporan->bind_param($types, ...$params);
}

$stmt_laporan->execute();
$result = $stmt_laporan->get_result();
$stmt_laporan->close();
?>
<h1 class="dashboard-title"><i class="fas fa-inbox"></i> Kotak Masuk Laporan</h1>
<p>Daftar pesan dan laporan yang dikirimkan oleh pengguna sistem (Internal, Donatur, Pemilik Kotak Amal).</p>

<table id="laporan-table" class="responsive-table">
    <thead>
        <tr>
            <th>Tanggal Lapor</th>
            <th>Pelapor (Tipe)</th>
            <th>Subjek</th>
            <th>LKSA Tujuan</th>
            <th>Status</th>
            <th>Aksi</th> </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { 
            
            $id_pelapor = $row['ID_user_pelapor'];
            $pelapor_type = $row['Pelapor_Type'];
            $nama_pelapor = 'N/A';

            // Logika pencarian nama berdasarkan Pelapor_Type
            if ($pelapor_type == 'USER') {
                $name_sql = "SELECT Nama_User FROM User WHERE Id_user = ?";
                $table_name = 'User';
            } elseif ($pelapor_type == 'DONATUR') {
                $name_sql = "SELECT Nama_Donatur AS Nama_User FROM Donatur WHERE ID_Donatur = ?";
                $table_name = 'Donatur';
            } elseif ($pelapor_type == 'PEMILIK_KA') {
                $name_sql = "SELECT Nama_Pemilik AS Nama_User FROM KotakAmal WHERE ID_KotakAmal = ?";
                $table_name = 'Pemilik Kotak Amal';
            } else {
                $table_name = 'Tidak Diketahui';
            }

            // Kueri Nama Pelapor (sudah menggunakan prepared statement sebelumnya, ini dipertahankan)
            if (isset($name_sql)) {
                $name_stmt = $conn->prepare($name_sql);
                $name_stmt->bind_param("s", $id_pelapor);
                $name_stmt->execute();
                $name_result = $name_stmt->get_result();
                $nama_pelapor = $name_result->fetch_assoc()['Nama_User'] ?? "{$table_name} Tidak Ditemukan";
                $name_stmt->close();
            }

            $status_style = ($row['Status_Baca'] == 'Belum Dibaca') ? 'font-weight: bold; color: red;' : 'color: green;';
            $status_text = ($row['Status_Baca'] == 'Belum Dibaca') ? 'Baru!' : 'Dibaca';
            $button_style = ($row['Status_Baca'] == 'Belum Dibaca') ? 'background-color: #2c3e50;' : 'background-color: #3498db;';
        ?>
            <tr>
                <td data-label="Tanggal Lapor"><?php echo date('d M Y H:i', strtotime($row['Tgl_Lapor'])); ?></td>
                <td data-label="Pelapor (Tipe)">
                    <strong><?php echo htmlspecialchars($nama_pelapor); ?></strong> 
                    <small style="color:#6c757d;">(<?php echo htmlspecialchars($pelapor_type); ?>)</small>
                </td>
                <td data-label="Subjek"><?php echo htmlspecialchars($row['Subjek']); ?></td>
                <td data-label="LKSA Tujuan"><?php echo htmlspecialchars($row['ID_LKSA']); ?></td>
                <td data-label="Status" style="<?php echo $status_style; ?>"><?php echo $status_text; ?></td>
                <td data-label="Aksi">
                    <a href="detail_laporan.php?id=<?php echo $row['ID_Laporan']; ?>" class="btn btn-primary" style="<?php echo $button_style; ?> padding: 8px 15px;">
                        Lihat <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>