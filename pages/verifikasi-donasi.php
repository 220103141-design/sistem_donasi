<?php
set_time_limit(300); // Menambah batas waktu eksekusi skrip menjadi 300 detik (5 menit)
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa akses
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];

$sql = "SELECT s.*, d.Nama_Donatur, u.Nama_User AS Petugas_Input 
        FROM Sumbangan s 
        LEFT JOIN Donatur d ON s.ID_donatur = d.ID_donatur 
        LEFT JOIN User u ON s.ID_user = u.Id_user";

if ($_SESSION['jabatan'] == 'Kepala LKSA') {
    $sql .= " WHERE s.Id_lksa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_lksa);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($sql);
}

?>
<style>
    /* Style Kustom untuk Verifikasi Donasi */
    :root {
        --primary-color: #1F2937;
        --accent-sumbangan: #047857; /* Dark Emerald for Table Header */
        --success-color: #10B981; /* Emerald Green */
        --danger-color: #EF4444; 
        --warning-color: #F59E0B; /* Amber/Orange for Verifikasi */
        --info-color: #3B82F6; /* Blue for Edit/Preview */
        --wa-color: #25D366; /* WhatsApp Green */
        --border-color: #E5E7EB;
        --text-color: #374151;
        --bg-hover-soft: #F5F3FF; /* Light Indigo */
    }

    .dashboard-title {
        color: var(--accent-sumbangan) !important;
        border-bottom: 2px solid var(--accent-sumbangan) !important;
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

    /* Warna Status Badge */
    .status-badge {
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 0.8em;
        font-weight: 700;
        white-space: nowrap;
        display: inline-block;
    }
    .status-terverifikasi { background-color: #D1FAE5; color: #047857; border: 1px solid #10B981; }
    .status-belum-verifikasi { background-color: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }

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
        background-color: var(--accent-sumbangan); /* Dark Emerald Header */
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
    
    /* Tombol Khusus */
    .btn-verify-custom { background-color: var(--warning-color); color: white; }
    .btn-edit-custom { background-color: var(--info-color); color: white; }
    .btn-cetak-custom { background-color: var(--success-color); color: white; }
    .btn-wa-custom { background-color: var(--wa-color); color: white; }

</style>

<h1 class="dashboard-title"><i class="fas fa-check-double"></i> Verifikasi Donasi</h1>
<p>Daftar sumbangan yang menunggu verifikasi dari **Kepala LKSA** atau **Pimpinan**.</p>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>
    <div style="background-color: #D1FAE5; color: #047857; border: 1px solid #10B981; padding: 10px; margin-bottom: 20px; border-radius: 8px; font-weight: 600;">
        <i class="fas fa-check-circle"></i> Verifikasi berhasil! Sumbangan telah disetujui dan siap dicetak.
    </div>
<?php } ?>

<div style="overflow-x: auto;">
<table class="responsive-table">
    <thead>
        <tr>
            <th>ID Kwitansi</th>
            <th>Donatur</th>
            <th>Tanggal</th>
            <th>Total Sumbangan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { 
            $total_uang = $row['Zakat_Profesi'] + $row['Zakat_Maal'] + $row['Infaq'] + $row['Sedekah'] + $row['Fidyah'];
            $status_verifikasi = $row['Status_Verifikasi'] ?? 'Belum Terverifikasi'; 
            $status_class = ($status_verifikasi == 'Terverifikasi') ? 'status-terverifikasi' : 'status-belum-verifikasi';
        ?>
            <tr>
                <td data-label="ID Kwitansi"><?php echo $row['ID_Kwitansi_ZIS']; ?></td>
                <td data-label="Donatur"><?php echo $row['Nama_Donatur']; ?></td>
                <td data-label="Tanggal"><?php echo $row['Tgl']; ?></td>
                <td data-label="Total Sumbangan" style="font-weight: 700;">Rp <?php echo number_format($total_uang); ?></td>
                <td data-label="Status">
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($status_verifikasi); ?>
                    </span>
                </td>
                <td data-label="Aksi" style="white-space: nowrap; text-align: center;">
                    
                    <a href="detail_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                       class="btn btn-action-icon btn-edit-custom" 
                       title="Lihat Detail">
                        <i class="fas fa-eye"></i>
                    </a>

                    <?php if ($status_verifikasi == 'Terverifikasi') { ?>
                        
                        <a href="cetak_kwitansi.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                           class="btn btn-action-icon btn-cetak-custom" 
                           target="_blank" 
                           title="Cetak Kwitansi">
                            <i class="fas fa-print"></i> Cetak
                        </a>
                        
                        <a href="wa-blast-form.php?id_kwitansi=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                           class="btn btn-action-icon btn-wa-custom" 
                           title="WA Blast">
                            <i class="fab fa-whatsapp"></i> WA
                        </a>

                    <?php } else { ?>
                        
                        <a href="edit_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                           class="btn btn-action-icon btn-edit-custom" 
                           title="Edit Nominal">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        
                        <a href="proses_verifikasi.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" 
                           class="btn btn-action-icon btn-verify-custom" 
                           onclick="return confirm('Apakah Anda yakin ingin memverifikasi donasi ini?');" 
                           title="Verifikasi Donasi">
                            <i class="fas fa-check"></i> Verifikasi
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