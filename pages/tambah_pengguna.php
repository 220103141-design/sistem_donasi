<?php
session_start();
include '../config/database.php';
//
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$sql_lksa = "SELECT Id_lksa, Nama_LKSA FROM LKSA"; // Ambil juga Nama_LKSA
$result_lksa = $conn->query($sql_lksa);

$sidebar_stats = ''; // Pastikan sidebar tampil

include '../includes/header.php'; // LOKASI BARU
?>
<style>
    /* Style Tambahan untuk Tampilan Form Ramping dan Simpel */
    :root {
        --form-bg-color: #FFFFFF;
        --border-color-soft: #D1D5DB;
        --input-focus-color: #06B6D4;
        --text-label: #4B5563;
        --font-size-small: 0.9em;
    }
    
    .form-container {
        max-width: 650px; /* Lebar lebih fokus */
        margin: 0 auto;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-top: 3px solid var(--input-focus-color);
    }
    
    .form-section h2 {
        font-size: 1.4em;
        font-weight: 600;
        border-bottom: 1px solid var(--border-color-soft);
        padding-bottom: 8px;
    }
    
    /* Input dan Select agar terlihat ramping */
    .form-group label {
        font-size: var(--font-size-small);
        color: var(--text-label);
        font-weight: 600;
        margin-bottom: 4px;
        display: block;
    }

    .form-group input[type="text"],
    .form-group input[type="password"],
    .form-group input[type="file"],
    .form-group select {
        padding: 10px 12px; /* Padding dikurangi */
        border: 1px solid var(--border-color-soft);
        border-radius: 6px; /* Lebih ramping */
        width: 100%;
        box-sizing: border-box;
        font-size: var(--font-size-small); /* Ukuran font lebih kecil/simpel */
        background-color: var(--form-bg-color);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus, .form-group select:focus {
        border-color: var(--input-focus-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2); /* Shadow fokus yang simpel */
    }
    
    .form-grid {
        gap: 20px;
    }
    
    /* Tombol Aksi */
    .btn-success {
        background-color: #10B981; /* Emerald Green */
        font-weight: 600;
        padding: 10px 20px;
    }
    .btn-cancel {
        background-color: #6B7280;
        font-weight: 600;
        padding: 10px 20px;
    }
</style>

<div class="form-container">
    <h1>Tambah Pengguna Baru</h1>
    <form action="proses_pengguna.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="tambah">
        <div class="form-section">
            <h2>Data Pengguna</h2>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Nama User:</label>
                    <input type="text" name="nama_user" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
            </div>
            
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Jabatan:</label>
                    <select name="jabatan" required>
                        <?php 
                        $is_pimpinan_pusat = ($_SESSION['jabatan'] == 'Pimpinan' && $_SESSION['id_lksa'] == 'Pimpinan_Pusat');
                        
                        if ($is_pimpinan_pusat) { ?>
                            <option value="Kepala LKSA">Kepala LKSA</option>
                            <option value="Pegawai">Pegawai</option>
                            <option value="Petugas Kotak Amal">Petugas Kotak Amal</option>
                        <?php } elseif ($_SESSION['jabatan'] == 'Pimpinan') { ?>
                            <option value="Kepala LKSA">Kepala LKSA</option>
                            <option value="Pegawai">Pegawai</option>
                            <option value="Petugas Kotak Amal">Petugas Kotak Amal</option>
                        <?php } elseif ($_SESSION['jabatan'] == 'Kepala LKSA') { ?>
                            <option value="Pegawai">Pegawai</option>
                            <option value="Petugas Kotak Amal">Petugas Kotak Amal</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID LKSA:</label>
                    <?php 
                    if ($_SESSION['jabatan'] == 'Kepala LKSA' || ($_SESSION['jabatan'] == 'Pimpinan' && $_SESSION['id_lksa'] != 'Pimpinan_Pusat')) { 
                        // Kepala LKSA dan Pimpinan Cabang hanya bisa membuat user di LKSA/cabang-nya sendiri
                    ?>
                        <input type="text" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>" readonly required>
                    <?php } else { ?>
                        <select name="id_lksa" required>
                            <option value="">-- Pilih LKSA --</option>
                            <?php while ($row_lksa = $result_lksa->fetch_assoc()) { ?>
                                <option value="<?php echo htmlspecialchars($row_lksa['Id_lksa']); ?>">
                                    <?php echo htmlspecialchars($row_lksa['Id_lksa']); ?> (<?php echo htmlspecialchars($row_lksa['Nama_LKSA'] ?? 'N/A'); ?>)
                                </option>
                            <?php } ?>
                        </select>
                    <?php } ?>
                </div>
            </div>

            <div class="form-group">
                <label>Foto:</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
            <a href="users.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
        </div>
    </form>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>