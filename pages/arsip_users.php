<?php
session_start();
include '../config/database.php';
// Set sidebar_stats ke string kosong agar sidebar tetap tampil
$sidebar_stats = ''; 
include '../includes/header.php';

// Verifikasi otorisasi: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses halaman ini.
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$jabatan = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// --- LOGIKA PENCARIAN (Meniru struktur kotak-amal.php) ---
$search_query = $_GET['search'] ?? '';
$filter_by = $_GET['filter_by'] ?? 'All'; 
$search_param = "%" . $search_query . "%";

// Daftar kolom yang diizinkan untuk pencarian (WHITELIST)
$allowed_columns = [
    'Id_user', 'Nama_User', 'Jabatan', 'Id_lksa'
];
// Label untuk ditampilkan di dropdown
$column_labels = [
    'All' => 'Semua Kolom',
    'Id_user' => 'ID Pengguna',
    'Nama_User' => 'Nama Pengguna',
    'Jabatan' => 'Jabatan',
    'Id_lksa' => 'ID LKSA'
];


// Persiapan query SQL dasar untuk mengambil data pengguna yang DIARSIPKAN.
$sql = "SELECT * FROM User WHERE Status = 'Archived'";

$params = [];
$types = "";

// 1. Cek Pencarian Teks
if (!empty($search_query)) {
    if ($filter_by !== 'All' && in_array($filter_by, $allowed_columns)) {
        $sql .= " AND " . $filter_by . " LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } else {
        // Pencarian di semua kolom (Default/Fallback)
        $sql .= " AND (Id_user LIKE ? OR Nama_User LIKE ? OR Jabatan LIKE ? OR Id_lksa LIKE ?)";
        for ($i = 0; $i < 4; $i++) {
            $params[] = $search_param;
            $types .= "s";
        }
    }
}

// 2. Filter LKSA
if ($jabatan == 'Pimpinan' && $id_lksa !== 'Pimpinan_Pusat') {
    $sql .= " AND Id_lksa = ?";
    $params[] = $id_lksa;
    $types .= "s";
} elseif ($jabatan == 'Kepala LKSA') {
    $sql .= " AND Id_lksa = ? AND Jabatan IN ('Pegawai', 'Petugas Kotak Amal')";
    $params[] = $id_lksa;
    $types .= "s";
}

$sql .= " ORDER BY Nama_User ASC";


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
    /* Sinkronisasi Gaya dengan Arsip Kotak Amal */
    :root {
        --primary-color: #1F2937; 
        --accent-restore: #10B981; /* Emerald Green */
        --accent-delete: #EF4444; /* Red */
        --header-color: #F97316; /* Orange (Warna header Arsip KA) */
        --border-color: #E5E7EB;
        --bg-hover: #FEF3C7; /* Light Amber/Yellow */
    }
    .arsip-wrapper {
        max-width: 100%; 
        margin: 0 auto; 
    }
    
    /* Tombol Kembali (sesuai Arsip KA) */
    .btn-kembali-aktif {
        background-color: #6B7280; 
        color: white;
        padding: 8px 15px;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        display: inline-flex;
        align-items: center;
        gap: 5px;
        float: right; /* Posisikan tombol di kanan atas */
        margin-bottom: 15px;
    }

    /* --- SEARCH BAR Sederhana (Sesuai Arsip KA) --- */
    .search-control-group-simple {
        display: flex;
        align-items: stretch;
        gap: 0; 
        max-width: 600px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .search-select-wrapper { 
        position: relative;
        flex-shrink: 0;
        width: 150px;
        border-right: 1px solid var(--border-color);
    }
    .search-select-simple {
        padding: 10px 15px; 
        border: none;
        font-size: 0.9em; 
        background-color: #fff; 
        width: 100%;
        height: 100%;
        font-weight: 600;
        color: var(--primary-color);
    }
    .search-input-simple {
        padding: 10px 15px;
        border: none;
        font-size: 1em;
        background-color: white;
        flex-grow: 1;
        min-width: 150px;
    }
    .btn-search-simple {
        background-color: var(--header-color); 
        color: white;
        padding: 10px 20px;
        border: none;
        font-weight: 700;
        cursor: pointer;
    }
    /* --- END SEARCH BAR --- */

    /* --- TABLE STYLES (Sesuai Arsip KA) --- */
    .table-archive {
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
        border-radius: 8px; /* Lebih kecil */
        overflow: hidden;
        margin-top: 15px;
    }
    
    .table-archive thead tr {
        background-color: var(--header-color); /* Orange Header */
        color: #fff;
    }
    .table-archive th {
        padding: 12px 15px; 
        font-weight: 700;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
    }
    .table-archive td {
        padding: 10px 15px;
        border-bottom: 1px solid #F3F4F6;
        background-color: #fff;
    }
    .table-archive tbody tr:hover {
        background-color: var(--bg-hover); /* Light Amber hover */
    }
    
    /* Tampilan User/Foto/Jabatan */
    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .profile-img-small {
        width: 40px; 
        height: 40px; 
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--border-color); 
        flex-shrink: 0;
    }
    .user-text-detail strong {
        font-weight: 600;
    }
    .job-title {
        font-size: 0.85em; 
        font-weight: 600; 
        color: var(--header-color); 
    }

    /* Tombol Aksi Icon + Text */
    .btn-action-icon {
        padding: 6px 12px;
        margin-left: 8px;
        border-radius: 6px; 
        font-size: 0.9em; 
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }
    .btn-restore {
        background-color: var(--accent-restore); 
        color: white;
    }
    .btn-delete-permanent {
        background-color: var(--accent-delete); 
        color: white;
    }

</style>

<div class="arsip-wrapper">
    <a href="users.php" class="btn btn-kembali-aktif">
        <i class="fas fa-arrow-left"></i> Kembali ke Aktif
    </a>

    <h1 class="dashboard-title"><i class="fas fa-archive" style="color: var(--header-color);"></i> Arsip Pengguna</h1>
    <p style="color: #555; margin-top: -10px; margin-bottom: 20px;">Daftar akun pengguna yang telah diarsipkan. Pulihkan atau hapus permanen data dari sini.</p>

    <form method="GET" action="" style="display: flex; gap: 10px;">
        <div class="search-control-group-simple">
            <div class="search-select-wrapper">
                <select name="filter_by" id="filter_by" class="search-select-simple">
                    <?php 
                    foreach ($column_labels as $value => $label) {
                        $selected = ($filter_by == $value) ? 'selected' : '';
                        echo "<option value=\"$value\" $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            
            <input type="text" name="search" placeholder="Cari di kolom terpilih..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input-simple">
            
            <button type="submit" class="btn-search-simple" title="Cari"><i class="fas fa-search"></i> Cari</button>
        </div>
        <?php if (!empty($search_query)) { ?>
            <a href="arsip_users.php" class="btn btn-cancel" style="padding: 10px 15px;" title="Reset Pencarian"><i class="fas fa-times"></i></a>
        <?php } ?>
    </form>
    
    <div style="overflow-x: auto;">
        <table class="table-archive">
            <thead>
                <tr>
                    <th style="width: 30%;">Detail Pengguna</th>
                    <th style="width: 18%;">Jabatan</th>
                    <th style="width: 15%;">ID LKSA</th>
                    <th style="width: 37%; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { 
                    $base_url_assets = "../assets/img/";
                    $foto_src = $base_url_assets . ($row['Foto'] ?? 'yayasan.png');
                ?>
                    <tr>
                        <td>
                            <div class="user-info-cell">
                                <?php if ($row['Foto'] && file_exists($base_url_assets . $row['Foto'])) { ?>
                                    <img src="<?php echo htmlspecialchars($foto_src); ?>" alt="Foto Profil" class="profile-img-small">
                                <?php } else { ?>
                                    <i class="fas fa-user-circle" style="font-size: 40px; color: #ccc; flex-shrink: 0;"></i>
                                <?php } ?>
                                <div class="user-text-detail">
                                    <strong><?php echo $row['Nama_User']; ?></strong>
                                    <small>ID: <?php echo $row['Id_user']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="job-title"><?php echo $row['Jabatan']; ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #1F2937;"><?php echo $row['Id_lksa']; ?></span>
                        </td>
                        <td style="text-align: right;">
                            <a href="proses_restore_pengguna.php?id=<?php echo $row['Id_user']; ?>" class="btn btn-action-icon btn-restore" title="Pulihkan" onclick="return confirm('Apakah Anda yakin ingin memulihkan pengguna ini?');">
                                <i class="fas fa-undo"></i> Pulihkan
                            </a>
                            <a href="hapus_pengguna.php?id=<?php echo $row['Id_user']; ?>" class="btn btn-action-icon btn-delete-permanent" title="Hapus Permanen" onclick="return confirm('PERINGATAN! Apakah Anda yakin ingin MENGHAPUS PERMANEN pengguna ini? Tindakan ini tidak dapat dibatalkan.');">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>