<?php
session_start();
include '../config/database.php';

// Pastikan hanya Pimpinan Pusat yang bisa mengakses halaman ini
if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak. Anda tidak memiliki izin untuk melihat halaman ini.");
}

// Logika untuk menampilkan data LKSA
$sql = "SELECT * FROM LKSA"; 
$result = $conn->query($sql);

// Set sidebar stats ke string kosong agar sidebar tetap tampil
$sidebar_stats = ''; 

include '../includes/header.php';
?>
<style>
    :root {
        --primary-soft: #374151; /* Dark Slate Gray (Teks utama) */
        --accent-soft: #10B981; /* Light Sea Green -> Emerald Green (Header) */
        --preview-soft: #047857; /* Indigo -> Dark Emerald untuk aksi */
        --border-color: #E5E7EB;
        --text-color: #374151;
        --bg-hover-soft: #E0F7FA; /* Sangat lembut pada hover */
    }

    /* Style kustom untuk tombol aksi yang lebih kecil */
    .btn-action-icon {
        padding: 6px 12px;
        margin: 0 4px;
        border-radius: 8px; 
        font-size: 0.9em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .btn-preview {
        background-color: var(--preview-soft); /* Indigo -> Dark Emerald untuk Preview */
        color: white;
    }
    .btn-preview:hover {
        background-color: #059669; /* Darker Dark Emerald */
        transform: translateY(-1px);
    }
    
    /* Perbaikan Visual Tabel Profesional */
    .responsive-table-lksa {
        width: 100%;
        min-width: 1400px; 
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px; 
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); /* Shadow yang lebih profesional */
        border-radius: 12px; 
        overflow: hidden;
        border: 1px solid var(--border-color);
        font-size: 0.9em; 
        color: var(--text-color);
        table-layout: fixed; 
    }

    .responsive-table-lksa thead tr {
        background-color: var(--accent-soft); 
        color: white;
    }
    
    .responsive-table-lksa th {
        font-weight: 700;
        padding: 12px 15px; 
        text-align: left;
        white-space: nowrap; 
        border-right: 1px solid rgba(255, 255, 255, 0.2); /* Garis pemisah kolom header */
        letter-spacing: 0.5px;
    }
    
    /* Lebar Kolom Alamat Ditetapkan */
    .alamat-header, .alamat-cell {
        width: 250px; 
        min-width: 250px;
        max-width: 250px;
    }
    
    .responsive-table-lksa td {
        padding: 0; /* Hapus padding pada TD */
        border-bottom: 1px solid #F3F4F6;
        vertical-align: top;
        line-height: 1.4;
        border-right: 1px solid #F9FAFB; /* Garis pemisah kolom data yang sangat lembut */
    }

    /* === SCROLLBAR HANYA PADA KOLOM ALAMAT === */
    .alamat-cell {
        padding: 0; 
        white-space: normal;
    }
    
    .alamat-scroll-wrapper {
        padding: 10px 15px; 
        max-width: 100%;
        height: 40px; 
        line-height: 1.4;
        overflow-x: auto; 
        white-space: nowrap; 
        text-overflow: ellipsis; 
        box-sizing: border-box;
        transition: background-color 0.2s;
    }
    .responsive-table-lksa tbody tr:hover .alamat-scroll-wrapper {
        background-color: rgba(255, 255, 255, 0.5); /* Background putih transparan saat hover */
    }
    
    /* Gaya data untuk TD lainnya (non-alamat) */
    .data-content-wrapper {
        padding: 10px 15px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .responsive-table-lksa td:last-child {
        padding: 10px 15px; /* Kembalikan padding untuk kolom Aksi */
    }


    /* Warna baris dan hover */
    .responsive-table-lksa tbody tr:nth-child(even) {
        background-color: #FDFDFE; 
    }
    
    .responsive-table-lksa tbody tr:hover {
        background-color: var(--bg-hover-soft); 
        box-shadow: inset 4px 0 0 0 var(--accent-soft); /* Aksen sidebar soft teal */
    }
    
    /* Warna tombol Tambah LKSA */
    .btn-success {
        background-color: var(--accent-soft);
        color: white;
        font-weight: 700;
    }
    .btn-success:hover {
        background-color: #0c9c6f; 
    }
</style>

<h1 class="dashboard-title"><i class="fas fa-building" style="color: var(--primary-soft);"></i> Manajemen LKSA</h1>
<p>Halaman ini memungkinkan Anda untuk mengelola semua data LKSA yang terdaftar (kantor cabang).</p>

<a href="tambah_lksa.php" class="btn btn-success" style="margin-bottom: 20px;"><i class="fas fa-plus-circle"></i> Tambah LKSA (Kantor Cabang)</a>

<div style="overflow-x: auto;"> 
    <table class="responsive-table-lksa">
        <thead>
            <tr>
                <th>ID LKSA</th>
                <th>Nama LKSA</th>
                <th>Nama Pimpinan</th>
                <th>Provinsi</th>
                <th>Kabupaten/Kota</th>
                <th>Kecamatan</th>
                <th>Kelurahan/Desa</th>
                <th>Nomor WA</th>
                <th class="alamat-header">Alamat</th>
                <th>Tanggal Daftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><div class="data-content-wrapper"><?php echo $row['Id_lksa']; ?></div></td>
                    <td><div class="data-content-wrapper"><?php echo $row['Nama_LKSA'] ?? 'N/A'; ?></div></td>
                    <td><div class="data-content-wrapper"><?php echo $row['Nama_Pimpinan'] ?? 'Belum Ditunjuk'; ?></div></td>
                    
                    <td><div class="data-content-wrapper"><?php echo $row['ID_Provinsi_Nama'] ?? '-'; ?></div></td>
                    <td><div class="data-content-wrapper"><?php echo $row['ID_Kota_Kabupaten_Nama'] ?? '-'; ?></div></td>
                    <td><div class="data-content-wrapper"><?php echo $row['ID_Kecamatan_Nama'] ?? '-'; ?></div></td>
                    <td><div class="data-content-wrapper"><?php echo $row['ID_Kelurahan_Nama'] ?? '-'; ?></div></td>
                    
                    <td><div class="data-content-wrapper"><?php echo $row['Nomor_WA'] ?? '-'; ?></div></td>
                    
                    <td class="alamat-cell">
                        <div class="alamat-scroll-wrapper">
                            <?php echo $row['Alamat']; ?>
                        </div>
                    </td>
                    
                    <td><div class="data-content-wrapper"><?php echo $row['Tanggal_Daftar']; ?></div></td>
                    
                    <td style="white-space: nowrap;">
                        <a href="detail_lksa.php?id=<?php echo $row['Id_lksa']; ?>" 
                           class="btn btn-primary btn-action-icon btn-preview" 
                           title="Lihat Detail">
                            <i class="fas fa-eye"></i> Preview
                        </a>
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