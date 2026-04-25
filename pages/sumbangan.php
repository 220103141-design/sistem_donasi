<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
$jabatan_session = $_SESSION['jabatan'] ?? '';
$id_lksa = $_SESSION['id_lksa'] ?? '';
$id_user_session = $_SESSION['id_user'] ?? '';

if (!in_array($jabatan_session, ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

// Gabungkan (JOIN) tabel Sumbangan dengan tabel Donatur dan User untuk mendapatkan Nama Donatur dan Petugas Input
// PERBAIKAN: Menambahkan kolom Natura, Nama_User, dan Status_Verifikasi
$sql = "SELECT s.*, d.Nama_Donatur, u.Nama_User AS Petugas_Input 
        FROM Sumbangan s 
        LEFT JOIN Donatur d ON s.ID_donatur = d.ID_donatur
        LEFT JOIN User u ON s.ID_user = u.Id_user";

$params = [];
$types = "";

if ($jabatan_session != 'Pimpinan') {
    // Batasi per LKSA jika bukan Pimpinan Pusat
    $sql .= " WHERE s.ID_LKSA = ?";
    $params[] = $id_lksa;
    $types = "s";
}

$sql .= " ORDER BY s.Tgl DESC, s.ID_Kwitansi_ZIS DESC";

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<style>
    /* Style Kustom untuk Manajemen Sumbangan (Lebih Profesional) */
    :root {
        --primary-color: #1F2937;
        --accent-sumbangan: #047857; /* Indigo -> Dark Emerald */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --warning-color: #F59E0B; /* Amber/Orange */
        --info-color: #10B981; /* Cyan/Aqua -> Emerald Green */
        --border-color: #E5E7EB;
        --text-color: #374151;
        --bg-hover-soft: #F5F3FF; /* Light Indigo */
    }

    /* Style untuk tombol aksi yang lebih jelas */
    .btn-action-icon {
        padding: 5px 8px;
        margin: 0 1px;
        border-radius: 6px;
        font-size: 0.85em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .btn-detail-custom { background-color: var(--info-color); color: white; }
    .btn-delete-custom { background-color: var(--danger-color); color: white; }
    .btn-cetak-custom { background-color: var(--success-color); color: white; }
    .btn-verifikasi-link { background-color: var(--warning-color); color: white; }
    
    /* Warna Status */
    .status-badge {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: 700;
        white-space: nowrap;
    }
    .status-terverifikasi { background-color: #D1FAE5; color: #047857; border: 1px solid #10B981; }
    .status-menunggu { background-color: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }

    /* Perbaikan Visual Tabel Profesional */
    .responsive-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); 
        border-radius: 8px; 
        overflow: hidden;
        border: 1px solid var(--border-color);
        font-size: 0.9em; 
        color: var(--text-color);
    }
    .responsive-table thead tr {
        background-color: var(--accent-sumbangan); /* Header Indigo -> Dark Emerald */
        color: white;
    }
    .responsive-table th, .responsive-table td {
        padding: 10px 15px; 
        white-space: nowrap;
        vertical-align: middle;
        border-bottom: 1px solid #F3F4F6;
    }
    .responsive-table tbody tr:hover {
        background-color: var(--bg-hover-soft);
        box-shadow: inset 3px 0 0 0 var(--accent-sumbangan); 
    }
</style>

<h1 class="dashboard-title"><i class="fas fa-funnel-dollar" style="color: var(--accent-sumbangan);"></i> Manajemen Sumbangan ZIS</h1>
<p>Kelola dan pantau semua transaksi sumbangan yang telah diinput. Verifikasi adalah langkah akhir sebelum pencetakan kwitansi.</p>
<a href="tambah_sumbangan.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Input Sumbangan Baru</a>

<?php if (in_array($jabatan_session, ['Pimpinan', 'Kepala LKSA'])) { ?>
    <a href="verifikasi-donasi.php" class="btn btn-verifikasi-link" style="margin-left: 10px;"><i class="fas fa-check-double"></i> Verifikasi Donasi</a>
<?php } ?>


<div style="overflow-x: auto; margin-top: 20px;">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>ID Kwitansi</th>
                <th>Tanggal</th>
                <th>Donatur</th>
                <th>Total ZIS Uang</th>
                <th>Natura</th>
                <th>Petugas Input</th>
                <th>Status</th>
                <th style="width: 150px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { 
                $total_uang = $row['Zakat_Profesi'] + $row['Zakat_Maal'] + $row['Infaq'] + $row['Sedekah'] + $row['Fidyah'];
                $natura_display = empty($row['Natura']) ? '-' : htmlspecialchars($row['Natura']);
                $status_verifikasi = $row['Status_Verifikasi'] ?? 'Menunggu'; // Default ke Menunggu
                $status_class = ($status_verifikasi == 'Terverifikasi') ? 'status-terverifikasi' : 'status-menunggu';
                
                // Cek otorisasi untuk Hapus (hanya Pegawai/Kepala/Pimpinan boleh hapus)
                $can_delete = in_array($jabatan_session, ['Pimpinan', 'Kepala LKSA', 'Pegawai']);
                // Pegawai hanya bisa Hapus jika statusnya masih 'Menunggu' (agar data yang sudah diverifikasi tidak hilang)
                if ($jabatan_session == 'Pegawai' && $status_verifikasi == 'Terverifikasi') {
                    $can_delete = false;
                }
            ?>
                <tr>
                    <td data-label="ID Kwitansi"><?php echo $row['ID_Kwitansi_ZIS']; ?></td>
                    <td data-label="Tanggal"><?php echo $row['Tgl']; ?></td>
                    <td data-label="Donatur"><?php echo $row['Nama_Donatur']; ?></td>
                    <td data-label="Total ZIS Uang">Rp <?php echo number_format($total_uang); ?></td>
                    <td data-label="Natura"><?php echo $natura_display; ?></td>
                    <td data-label="Petugas Input"><?php echo $row['Petugas_Input']; ?></td>
                    <td data-label="Status">
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_verifikasi); ?>
                        </span>
                    </td>
                    <td data-label="Aksi" style="white-space: nowrap; text-align: center;">
                        
                        <a href="detail_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                           class="btn btn-action-icon btn-detail-custom" 
                           title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                        </a>

                        <?php if ($status_verifikasi == 'Terverifikasi') { ?>
                            <a href="cetak_kwitansi.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                               class="btn btn-action-icon btn-cetak-custom" 
                               target="_blank" 
                               title="Cetak Kwitansi">
                                <i class="fas fa-print"></i>
                            </a>
                        <?php } ?>
                        
                        <?php if ($can_delete) { ?>
                            <a href="hapus_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                               class="btn btn-action-icon btn-delete-custom" 
                               title="Hapus Transaksi" 
                               onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.');">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php } ?>
                        
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>