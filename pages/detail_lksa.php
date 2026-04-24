<?php
session_start();
include '../config/database.php';
$sidebar_stats = ''; // PENTING: Memastikan includes/header.php memuat sidebar

// Authorization check: Hanya Pimpinan Pusat
if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak. Anda tidak memiliki izin untuk melihat detail LKSA.");
}

$id_lksa_to_view = $_GET['id'] ?? '';
if (empty($id_lksa_to_view)) {
    die("ID LKSA tidak ditemukan.");
}

// Ambil data LKSA dari database
$sql = "SELECT * FROM LKSA WHERE Id_lksa = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_lksa_to_view);
$stmt->execute();
$result = $stmt->get_result();
$data_lksa = $result->fetch_assoc();
$stmt->close();

if (!$data_lksa) {
    die("Data LKSA tidak ditemukan.");
}

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$logo_path = $data_lksa['Logo'] ? $base_url . 'assets/img/' . $data_lksa['Logo'] : $base_url . 'assets/img/yayasan.png';

include '../includes/header.php'; // Membuka struktur layout utama (termasuk sidebar)

?>
<style>
    /* Styling dasar untuk Detail/Preview */
    .detail-container {
        /* Hapus max-width dan margin auto di sini, biarkan main-content-area yang menanganinya */
        padding: 30px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-left: 5px solid #10B981; /* Cyan -> Emerald Green */
        width: 100%; /* Pastikan mengisi area konten utama */
        box-sizing: border-box;
    }
    .detail-container h1 {
        font-size: 2.0em;
        font-weight: 700;
        color: #1F2937;
        border-bottom: 2px solid #E5E7EB;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .data-table {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }
    .data-table th, .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #F3F4F6;
    }
    .data-table th {
        width: 35%;
        background-color: #F9FAFB;
        color: #4B5563;
        font-weight: 600;
    }
    .data-table td {
        font-weight: 500;
        color: #1F2937;
    }
    .logo-display {
        text-align: center;
        margin-bottom: 30px;
    }
    .logo-display img {
        height: 120px;
        width: auto;
        border: 4px solid #10B981; /* Cyan -> Emerald Green */
        padding: 5px;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .btn-edit-data {
        background-color: #047857; /* Orange -> Dark Emerald untuk Edit */
        color: white;
        font-weight: 700;
        padding: 12px 25px;
        border-radius: 8px;
        transition: background-color 0.2s, transform 0.2s;
        text-decoration: none;
    }
    .btn-edit-data:hover {
        background-color: #059669; /* Darker Dark Emerald */
        transform: translateY(-2px);
    }
</style>

<div class="detail-container">
    <h1><i class="fas fa-building" style="color: #10B981;"></i> Detail LKSA: <?php echo htmlspecialchars($data_lksa['Id_lksa']); ?></h1>
    
    <div class="logo-display">
        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo LKSA">
        <p style="margin-top: 10px; font-size: 0.9em; color: #555;"><?php echo htmlspecialchars($data_lksa['Nama_LKSA'] ?? 'N/A'); ?></p>
    </div>

    <table class="data-table">
        <tr>
            <th>ID LKSA</th>
            <td><?php echo htmlspecialchars($data_lksa['Id_lksa']); ?></td>
        </tr>
        <tr>
            <th>Nama Pimpinan</th>
            <td><?php echo htmlspecialchars($data_lksa['Nama_Pimpinan'] ?? 'Belum Ditunjuk'); ?></td>
        </tr>
        <tr>
            <th>Nomor WA</th>
            <td><?php echo htmlspecialchars($data_lksa['Nomor_WA'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Email LKSA</th>
            <td><?php echo htmlspecialchars($data_lksa['Email'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Alamat Lengkap</th>
            <td><?php echo htmlspecialchars($data_lksa['Alamat'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Provinsi</th>
            <td><?php echo htmlspecialchars($data_lksa['ID_Provinsi_Nama'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Kabupaten/Kota</th>
            <td><?php echo htmlspecialchars($data_lksa['ID_Kabupaten_Nama'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Kecamatan</th>
            <td><?php echo htmlspecialchars($data_lksa['ID_Kecamatan_Nama'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Kelurahan/Desa</th>
            <td><?php echo htmlspecialchars($data_lksa['ID_Kelurahan_Nama'] ?? '-'); ?></td>
        </tr>
        <tr>
            <th>Tanggal Pendaftaran</th>
            <td><?php echo htmlspecialchars($data_lksa['Tanggal_Daftar'] ?? '-'); ?></td>
        </tr>
    </table>
    
    <div class="form-actions" style="justify-content: space-between; margin-top: 30px; display: flex;">
        <a href="lksa.php" class="btn btn-cancel"><i class="fas fa-arrow-left"></i> Kembali</a>
        
        <a href="edit_lksa.php?id=<?php echo htmlspecialchars($data_lksa['Id_lksa']); ?>" 
           class="btn btn-edit-data">
            <i class="fas fa-edit"></i> Edit Data LKSA
        </a>
    </div>
</div>

<?php
include '../includes/footer.php'; // Menutup struktur layout utama
$conn->close();
?>