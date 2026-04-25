<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Verifikasi otorisasi: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses halaman ini.
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$jabatan = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// Persiapan query SQL dasar untuk mengambil data pengguna yang AKTIF.
$sql = "SELECT * FROM User WHERE Status = 'Active'";

// Logika untuk menyesuaikan query berdasarkan jabatan dan ID LKSA pengguna yang sedang login.
if ($jabatan == 'Pimpinan' && $id_lksa == 'Pimpinan_Pusat') {
    $sql .= " AND Id_user != '" . $_SESSION['id_user'] . "'";
} elseif ($jabatan == 'Pimpinan' && $id_lksa !== 'Pimpinan_Pusat') {
    $sql .= " AND Id_lksa = '$id_lksa' AND Id_user != '" . $_SESSION['id_user'] . "'";
} elseif ($jabatan == 'Kepala LKSA') {
    $sql .= " AND Id_lksa = '$id_lksa' AND Jabatan IN ('Pegawai', 'Petugas Kotak Amal')";
}

$result = $conn->query($sql);
?>
<style>
    /* Style Kustom untuk Manajemen Pengguna (Minimalis Akhir) */
    :root {
        --primary-color: #1F2937;
        --accent-users: #10B981; /* Cyan -> Emerald Green */
        --accent-secondary: #0c9c6f; /* Orange -> Medium Emerald (Arsip Link) */
        --archive-color: #EF4444;
        --border-color: #E5E7EB;
        --text-color: #374151;
        --bg-hover-soft: #F0F9FF;
        --add-color: #10B981; 
    }
    
    /* Tombol Aksi Ikon Only (Paling Kecil) */
    .btn-action-icon {
        padding: 4px 6px; /* Padding minimalis */
        width: 30px; 
        height: 30px; 
        margin: 0 1px; /* Margin sangat kecil */
        border-radius: 4px; 
        font-size: 0.85em; 
        display: inline-flex;
        justify-content: center; 
        align-items: center;
        box-shadow: none; /* Minimalis */
        border: 1px solid transparent; 
    }
    .btn-action-icon:hover {
        opacity: 0.85;
        border: 1px solid white; 
        transform: none; 
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .btn-action-icon span { display: none; }
    
    .btn-preview-custom { background-color: var(--accent-users); color: white; }
    .btn-archive-custom { background-color: var(--archive-color); color: white; }

    /* --- TATA LETAK TABEL RAMPLNG --- */
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
        table-layout: auto;
    }
    .responsive-table thead tr {
        background-color: var(--primary-color); 
        color: white;
    }
    .responsive-table th {
        font-weight: 600; 
        padding: 10px 15px; 
        white-space: nowrap;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }
    .responsive-table td {
        padding: 8px 15px; 
        border-bottom: 1px solid #F3F4F6;
        white-space: nowrap;
        vertical-align: middle;
    }
    .responsive-table tbody tr:hover {
        background-color: var(--bg-hover-soft);
        box-shadow: inset 3px 0 0 0 var(--accent-users); 
    }
    
    /* Style untuk Foto Profil di Tabel */
    .profile-img-small {
        width: 40px; 
        height: 40px; 
        object-fit: cover;
        border-radius: 50%; 
        border: 2px solid var(--accent-users); 
        padding: 1px;
    }
    .no-foto-icon {
        font-size: 25px; 
        color: #ccc;
        vertical-align: middle;
    }
    
    /* Tombol Navigasi Atas */
    .btn-success-add {
        background-color: var(--add-color); 
        padding: 8px 15px; 
        border-radius: 6px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: white; /* Diatur ke putih sesuai permintaan */
    }
    .btn-archive-link {
        background-color: var(--accent-secondary); 
        padding: 8px 15px; 
        border-radius: 6px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 10px;
        color: white; /* Diatur ke putih sesuai permintaan */
    }
</style>

<h1 class="dashboard-title"><i class="fas fa-users" style="color: var(--primary-color);"></i> Manajemen Pengguna</h1>
<p>Anda dapat mengelola akun pengguna di sistem.</p>

<div style="margin-bottom: 20px;">
    <a href="tambah_pengguna.php" class="btn btn-success-add"><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</a>
    <a href="arsip_users.php" class="btn btn-archive-link"><i class="fas fa-archive"></i> Lihat Arsip Pengguna</a>
</div>

<div style="overflow-x: auto;">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>Nama User</th>
                <th>Jabatan</th>
                <th>ID LKSA</th>
                <th>Foto</th>
                <th style="width: 100px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td data-label="Nama User"><?php echo $row['Nama_User']; ?></td>
                    <td data-label="Jabatan"><?php echo $row['Jabatan']; ?></td>
                    <td data-label="ID LKSA"><?php echo $row['Id_lksa']; ?></td>
                    <td data-label="Foto">
                        <?php if ($row['Foto']) { ?>
                            <img src="../assets/img/<?php echo htmlspecialchars($row['Foto']); ?>" alt="Foto Profil" class="profile-img-small">
                        <?php } else { ?>
                            <i class="fas fa-user-circle no-foto-icon"></i>
                        <?php } ?>
                    </td>
                    <td data-label="Aksi" style="white-space: nowrap; text-align: center;">
                        <a href="detail_pengguna.php?id=<?php echo $row['Id_user']; ?>" 
                           class="btn btn-action-icon btn-preview-custom" 
                           title="Preview/Edit Data">
                            <i class="fas fa-eye"></i>
                            <span>Preview</span>
                        </a>
                        <a href="proses_arsip_pengguna.php?id=<?php echo $row['Id_user']; ?>" 
                           class="btn btn-action-icon btn-archive-custom" 
                           onclick="return confirm('Apakah Anda yakin ingin mengarsipkan pengguna ini?');" 
                           title="Arsipkan">
                            <i class="fas fa-archive"></i>
                            <span>Arsipkan</span>
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