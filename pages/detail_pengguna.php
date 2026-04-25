<?php
session_start();
include '../config/database.php';
$sidebar_stats = ''; 

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_user_to_view = $_GET['id'] ?? '';
if (empty($id_user_to_view)) {
    die("ID Pengguna tidak ditemukan.");
}

// Ambil data pengguna dari database
$sql = "SELECT * FROM User WHERE Id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_user_to_view);
$stmt->execute();
$result = $stmt->get_result();
$data_user = $result->fetch_assoc();
$stmt->close();

if (!$data_user) {
    die("Data pengguna tidak ditemukan.");
}

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$foto_path = $data_user['Foto'] ? $base_url . 'assets/img/' . $data_user['Foto'] : $base_url . 'assets/img/yayasan.png';

include '../includes/header.php';
?>
<style>
    /* Styling khusus untuk tampilan Detail/Preview - MINIMALIS & ELEGAN */
    :root {
        --primary-text: #374151;
        --accent-soft: #10B981; /* Teal -> Emerald Green */
        --edit-btn-color: #10B981; /* Orange -> Emerald Green */
        --border-color: #E5E7EB;
        --bg-light: #F9FAFB;
        --job-color: #047857; /* Emerald -> Dark Emerald */
    }
    
    .detail-container {
        max-width: 650px; /* Lebih fokus */
        margin: 0 auto; 
        padding: 30px; 
        background-color: #fff;
        border-radius: 10px; 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Shadow lebih halus */
        border-top: 3px solid var(--accent-soft); /* Aksen garis atas tipis */
        width: 100%;
        box-sizing: border-box;
    }
    .detail-container h1 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8em; 
        font-weight: 700;
        color: var(--primary-text);
        margin-bottom: 25px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
    }
    
    .profile-display {
        text-align: center;
        margin-bottom: 40px; /* White space lebih luas */
    }
    .profile-display img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid white; 
        outline: 2px solid var(--border-color); /* Outline sangat tipis dan soft */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 15px;
        transition: transform 0.3s;
    }
    .profile-display p.name {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.6em; 
        font-weight: 700;
        color: var(--primary-text);
        margin: 0 0 5px 0;
    }
    .profile-display p.job-lksa {
        color: var(--job-color);
        font-weight: 600;
        font-size: 0.95em;
        margin: 0;
    }
    
    /* Detail Data - Minimalis Key-Value Pair */
    .data-section {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 10px 0;
    }
    .data-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid #F7F7F7;
        align-items: center;
    }
    .data-row:last-child {
        border-bottom: none;
    }
    .data-label {
        font-weight: 600;
        color: #6B7280;
        font-size: 0.9em;
    }
    .data-value {
        font-weight: 500;
        color: var(--primary-text);
        font-size: 1.0em;
        text-align: right;
    }

    /* Tombol Aksi */
    .btn-edit-data {
        background-color: var(--edit-btn-color); 
    }
    .btn-cancel {
         background-color: #6B7280;
    }
</style>

<div class="detail-container">
    <h1><i class="fas fa-address-card" style="color: var(--primary-text);"></i> Detail Profil</h1>
    
    <div class="profile-display">
        <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Profil">
        <p class="name"><?php echo htmlspecialchars($data_user['Nama_User']); ?></p>
        <p class="job-lksa"><?php echo htmlspecialchars($data_user['Jabatan']); ?> &middot; LKSA <?php echo htmlspecialchars($data_user['Id_lksa']); ?></p>
    </div>

    <div class="data-section">
        <div class="data-row">
            <span class="data-label">ID Pengguna</span>
            <span class="data-value"><?php echo htmlspecialchars($data_user['Id_user']); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Jabatan</span>
            <span class="data-value"><?php echo htmlspecialchars($data_user['Jabatan']); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">ID LKSA</span>
            <span class="data-value"><?php echo htmlspecialchars($data_user['Id_lksa']); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Status Akun</span>
            <span class="data-value"><?php echo htmlspecialchars($data_user['Status']); ?></span>
        </div>
    </div>
    
    <div class="form-actions" style="justify-content: space-between; margin-top: 30px; display: flex;">
        <a href="users.php" class="btn btn-cancel"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
        
        <a href="edit_pengguna.php?id=<?php echo htmlspecialchars($data_user['Id_user']); ?>" 
           class="btn btn-edit-data">
            <i class="fas fa-edit"></i> Edit Data
        </a>
    </div>
</div>

<?php
include '../includes/footer.php'; 
$conn->close();
?>