<?php
session_start();
include '../config/database.php';
//
// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Pegawai') {
    die("Akses ditolak.");
}

// Ambil data donatur untuk dropdown (memfilter berdasarkan LKSA user yang login)
$sql_donatur = "SELECT ID_donatur, Nama_Donatur, NO_WA FROM Donatur WHERE Status_Data = 'Active' AND ID_LKSA = ?";
$stmt_donatur = $conn->prepare($sql_donatur);
// Asumsi ID_LKSA ada di session
$stmt_donatur->bind_param("s", $_SESSION['id_lksa']);
$stmt_donatur->execute();
$result_donatur = $stmt_donatur->get_result();
$stmt_donatur->close();

$sidebar_stats = ''; // Pastikan sidebar tampil

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";

include '../includes/header.php'; // LOKASI BARU
?>
<style>
    /* Custom Styles for Sumbangan Form */
    :root {
        --accent-sumbangan: #6366F1; /* Indigo */
        --success-color: #10B981;
        --border-color-soft: #D1D5DB;
        --total-bg: #EEF2FF; /* Light Indigo Background */
        --total-text: #1E3A8A; /* Deep Blue for contrast */
        --zis-card-bg: #FFFFFF;
        --zis-card-hover: #F9F7FF;
        --link-tambah: #F97316; /* Orange untuk tautan tambah */
    }
    .form-container {
        max-width: 800px;
        border-top: 3px solid var(--accent-sumbangan);
    }
    .form-section h2 {
        color: var(--accent-sumbangan);
    }
    .money-input-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 Kolom untuk Zakat, Infaq, Sedekah */
        gap: 20px;
    }
    .zis-card {
        background-color: var(--zis-card-bg);
        border: 1px solid var(--border-color-soft);
        border-radius: 8px;
        padding: 15px;
        transition: all 0.2s;
        box-shadow: 0 1px 5px rgba(0,0,0,0.05);
    }
    .zis-card:hover {
        background-color: var(--zis-card-hover);
        border-color: var(--accent-sumbangan);
    }
    .input-label {
        font-weight: 700;
        font-size: 0.95em;
        color: var(--accent-sumbangan); /* Label warna aksen */
        display: block;
        margin-bottom: 5px;
    }
    /* Input nominal dipertegas */
    .zis-input {
        text-align: right !important;
        font-weight: 700 !important;
        color: var(--accent-sumbangan) !important;
        font-size: 1.1em !important;
    }
    .total-display-card {
        background-color: var(--total-bg);
        border: 2px solid var(--accent-sumbangan);
        padding: 15px 25px;
        border-radius: 10px;
        margin-top: 20px;
        text-align: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        grid-column: span 3; /* Memastikan total mengambil lebar penuh */
    }
    .total-display-card p {
        margin: 0;
        font-size: 0.9em;
        font-weight: 600;
        color: var(--total-text);
    }
    .total-display-card #total_sumbangan_value {
        font-size: 2.2em;
        font-weight: 800;
        color: var(--accent-sumbangan);
        display: block;
        margin-top: 5px;
    }
    /* Mengelompokkan Fidyah secara terpisah (di bawah) */
    .fidyah-group {
        grid-column: span 3;
        padding-top: 15px;
        border-top: 1px solid var(--border-color-soft);
    }
    .fidyah-group input {
         max-width: 200px;
         margin-right: auto;
         text-align: left !important;
    }
    /* Gaya untuk Input Pencarian Donatur yang disederhanakan */
    .donor-search-wrapper {
        border: 1px solid var(--border-color-soft);
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    #donatur_search_input {
        border: none !important;
        border-bottom: 1px solid var(--border-color-soft) !important;
        border-radius: 6px 6px 0 0 !important;
        box-sizing: border-box;
        margin-bottom: 0 !important;
    }
    #donatur_select {
        border: none !important;
        border-radius: 0 0 6px 6px !important;
        width: 100% !important;
        padding: 5px 12px !important;
        box-sizing: border-box;
        font-size: 0.9em;
        /* Styling untuk scrollable list */
        max-height: 180px; 
        overflow-y: auto;
    }
    .add-link {
        display: block;
        margin-top: 10px;
        font-size: 0.85em;
        font-weight: 600;
        color: var(--link-tambah);
        text-decoration: none;
    }
    .add-link:hover {
        text-decoration: underline;
    }
    /* Kotak Info Donatur Terpilih */
    .selected-donor-info {
        background-color: #D1FAE5; /* Light Green */
        border: 1px solid var(--success-color);
        border-radius: 6px;
        padding: 10px 15px;
        margin-top: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9em;
        font-weight: 600;
        color: #047857; /* Deep Green */
    }
    .selected-donor-info strong {
        color: var(--accent-sumbangan);
    }
</style>

<div class="form-container">
    <h1>Input Sumbangan Baru</h1>
    <form action="proses_sumbangan.php" method="POST" enctype="multipart/form-data" id="sumbanganForm">
        <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($_SESSION['id_user']); ?>">
        <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>">

        <div class="form-section">
            <h2><i class="fas fa-handshake"></i> Detail Transaksi & Donatur</h2>
            <div class="form-grid">
                 <div class="form-group" style="grid-column: span 2;">
                    <label>Tanggal Transaksi (Otomatis Hari Ini):</label>
                    <input type="text" value="<?php echo date('d F Y'); ?>" readonly style="background-color: #e9ecef; font-weight: 600;">
                </div>
            </div>
            
            <div class="form-group">
                <label>Pilih Donatur (Cari Berdasarkan Nama atau WA):</label>
                <div class="donor-search-wrapper">
                    <input type="text" id="donatur_search_input" placeholder="Ketik Nama Donatur atau Nomor WA..." class="searchable-input">
                    
                    <select name="id_donatur" id="donatur_select" size="6" required>
                        <option value="">-- Pilih Donatur --</option>
                        <?php 
                        if ($result_donatur->num_rows > 0) {
                            // Mengambil ulang data untuk menyimpan ke JS
                            $donatur_data = [];
                            $result_donatur->data_seek(0);
                            while ($row_donatur = $result_donatur->fetch_assoc()) { 
                                $display_text = htmlspecialchars($row_donatur['Nama_Donatur']) . " (WA: " . htmlspecialchars($row_donatur['NO_WA']) . ")";
                                $donatur_data[$row_donatur['ID_donatur']] = [
                                    'name' => htmlspecialchars($row_donatur['Nama_Donatur']),
                                    'wa' => htmlspecialchars($row_donatur['NO_WA'])
                                ];
                                ?>
                                <option value="<?php echo htmlspecialchars($row_donatur['ID_donatur']); ?>" 
                                        data-search-term="<?php echo strtolower($row_donatur['Nama_Donatur'] . ' ' . $row_donatur['NO_WA']); ?>">
                                    <?php echo $display_text; ?>
                                </option>
                            <?php }
                            // Simpan data donatur ke variabel JS
                            echo "<script>const DONOR_LIST_DATA = " . json_encode($donatur_data) . ";</script>";
                        } else { ?>
                             <option value="" disabled>Tidak ada Donatur aktif di LKSA Anda</option>
                        <?php } ?>
                    </select>
                </div>
                <a href="tambah_donatur.php" target="_blank" class="add-link">
                    <i class="fas fa-user-plus"></i> Tambah Donatur Baru jika tidak ditemukan
                </a>

                <div id="selected_donor_display" class="selected-donor-info" style="display: none;">
                    <span><i class="fas fa-check-circle"></i> Donatur Terpilih: <strong id="donor_name_display"></strong></span>
                    <span id="donor_wa_display"></span>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2><i class="fas fa-coins"></i> Klasifikasi Donasi ZIS (Rp)</h2>
            <p class="form-description">Isi nominal yang sesuai dengan jenis sumbangan yang diberikan. Min. 0.</p>
            
            <div class="money-input-grid">
                
                <div class="zis-card">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="input-label"><i class="fas fa-user-tie"></i> Zakat Profesi:</label>
                        <input type="number" name="zakat_profesi" class="zis-input" value="0" min="0">
                    </div>
                </div>
                
                <div class="zis-card">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="input-label"><i class="fas fa-sitemap"></i> Zakat Maal:</label>
                        <input type="number" name="zakat_maal" class="zis-input" value="0" min="0">
                    </div>
                </div>
                
                <div class="zis-card">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="input-label"><i class="fas fa-hand-holding-usd"></i> Infaq:</label>
                        <input type="number" name="infaq" class="zis-input" value="0" min="0">
                    </div>
                </div>
                
                <div class="zis-card">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="input-label"><i class="fas fa-heart"></i> Sedekah:</label>
                        <input type="number" name="sedekah" class="zis-input" value="0" min="0">
                    </div>
                </div>

                <div class="fidyah-group">
                    <label class="input-label"><i class="fas fa-utensils"></i> Fidyah (Khusus Makanan):</label>
                    <input type="number" name="fidyah" class="zis-input" value="0" min="0">
                </div>
                
                <div class="total-display-card">
                    <p>TOTAL SUMBANGAN UANG</p>
                    <span id="total_sumbangan_value">Rp 0</span>
                </div>

            </div>
        </div>
        
        <div class="form-section">
             <h2><i class="fas fa-gift"></i> Sumbangan Natura (Non-Uang)</h2>
             <div class="form-group">
                <label>Natura (Deskripsi Barang):</label>
                <input type="text" name="natura" placeholder="Contoh: 10 kg beras, 5 dus mie instan">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success" id="submit_btn"><i class="fas fa-save"></i> Simpan Sumbangan</button>
            <a href="sumbangan.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
        </div>
    </form>
</div>

<script>
// =======================================================
// JS Logic untuk Pencarian Donatur (Native JS Filtering)
// =======================================================
function updateDonorDisplay(selectedId) {
    const displayBox = document.getElementById('selected_donor_display');
    const nameDisplay = document.getElementById('donor_name_display');
    const waDisplay = document.getElementById('donor_wa_display');
    
    // Pastikan DONOR_LIST_DATA sudah didefinisikan (dari PHP)
    const donorData = window.DONOR_LIST_DATA || {}; 

    if (selectedId && donorData[selectedId]) {
        const donor = donorData[selectedId];
        nameDisplay.textContent = donor.name;
        waDisplay.textContent = 'WA: ' + donor.wa;
        displayBox.style.display = 'flex';
    } else {
        displayBox.style.display = 'none';
        nameDisplay.textContent = '';
        waDisplay.textContent = '';
    }
}

document.getElementById('donatur_search_input').addEventListener('input', function() {
    const filterText = this.value.toLowerCase();
    const select = document.getElementById('donatur_select');
    const options = select.options;
    
    // Lakukan filtering pada opsi
    for (let i = 1; i < options.length; i++) {
        const option = options[i];
        const searchText = option.getAttribute('data-search-term') || option.textContent.toLowerCase();

        if (searchText.includes(filterText)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
    
    // Jika ada nilai yang dipilih (selectedValue), pastikan item itu tetap terlihat.
    // Logika ini dipanggil di calculateTotal() melalui event change/input.
    calculateTotal();
});


// =======================================================
// JS Logic untuk Perhitungan Total & Validasi
// =======================================================
function formatRupiah(angka) {
    // Fungsi untuk memformat angka menjadi format Rupiah
    var reverse = angka.toString().split('').reverse().join(''),
    ribuan = reverse.match(/\d{1,3}/g);
    ribuan = ribuan.join('.').split('').reverse().join('');
    return 'Rp ' + ribuan;
}

function calculateTotal() {
    let total = 0;
    const inputs = document.querySelectorAll('.zis-input');
    inputs.forEach(input => {
        const value = parseInt(input.value) || 0;
        total += value;
    });

    document.getElementById('total_sumbangan_value').textContent = formatRupiah(total);
    
    // Validasi submit
    const naturaValue = document.querySelector('input[name="natura"]').value.trim();
    const submitBtn = document.getElementById('submit_btn');
    const selectedDonatur = document.getElementById('donatur_select').value;
    
    // Update kotak info donatur
    updateDonorDisplay(selectedDonatur);

    if (total <= 0 && naturaValue === '') {
         submitBtn.disabled = true;
         submitBtn.textContent = 'Isi nominal ZIS atau Natura';
    } else if (!selectedDonatur) {
         submitBtn.disabled = true;
         submitBtn.textContent = 'Pilih Donatur terlebih dahulu';
    } else {
         submitBtn.disabled = false;
         submitBtn.textContent = 'Simpan Sumbangan';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Tambahkan event listener untuk semua input yang relevan
    const inputs = document.querySelectorAll('.zis-input, input[name="natura"]');
    inputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
        // Pastikan nilai default 0 terpasang pada number input saat dimuat
        if (input.type === 'number' && (input.value === '' || input.value === null)) {
            input.value = '0';
        }
    });
    
    // Listener untuk Select Donatur (change event) dan Input Pencarian
    document.getElementById('donatur_select').addEventListener('change', calculateTotal);
    document.getElementById('donatur_search_input').addEventListener('input', calculateTotal);

    // Perhitungan awal setelah DOM dimuat
    calculateTotal();
});
</script>

<?php
include '../includes/footer.php';
$conn->close();
?>