<?php
session_start();
include '../config/database.php';
//
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Pegawai') {
    die("Akses ditolak.");
}

$sidebar_stats = ''; 

include '../includes/header.php'; // LOKASI BARU

$base_path = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/"; // Untuk memuat wilayah.js
?>
<style>
    /* Style Tambahan untuk Tampilan Form Ramping dan Simpel */
    :root {
        --form-bg-color: #FFFFFF;
        --border-color-soft: #D1D5DB;
        --input-focus-color: #10B981; /* Emerald Green (Aksen Donatur) */
        --text-label: #4B5563;
        --font-size-small: 0.9em;
    }
    
    .form-container {
        max-width: 800px; 
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
        color: var(--input-focus-color);
    }
    
    /* Input dan Select agar terlihat ramping */
    .form-group label {
        font-size: var(--font-size-small);
        color: var(--text-label);
        font-weight: 600;
        margin-bottom: 4px;
        display: block;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px 12px; 
        border: 1px solid var(--border-color-soft);
        border-radius: 6px; 
        width: 100%;
        box-sizing: border-box;
        font-size: var(--font-size-small); 
        background-color: var(--form-bg-color);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        border-color: var(--input-focus-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); 
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    /* Tombol Aksi */
    .btn-success {
        background-color: #10B981; 
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
    <h1><i class="fas fa-user-plus"></i> Tambah Donatur Baru</h1>
    <form action="proses_donatur.php" method="POST" enctype="multipart/form-data" id="donaturForm">
        <input type="hidden" name="action" value="tambah">
        <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>">
        <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($_SESSION['id_user']); ?>">
        
        <input type="hidden" name="alamat_lengkap" id="alamat_lengkap_hidden">
        <input type="hidden" name="ID_Provinsi" id="ID_Provinsi_nama_input"> 
        <input type="hidden" name="ID_Kabupaten" id="ID_Kabupaten_nama_input">
        <input type="hidden" name="ID_Kecamatan" id="ID_Kecamatan_nama_input">
        <input type="hidden" name="ID_Kelurahan" id="ID_Kelurahan_nama_input">
        <input type="hidden" name="nama_donatur" id="nama_donatur_final">
        <div class="form-section">
            <h2>Data Personal</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Donatur:</label>
                    <div style="display: flex; gap: 10px;">
                        <select name="panggilan" id="panggilan" required style="width: 120px; flex-shrink: 0;">
                            <option value="Bapak">Bapak</option>
                            <option value="Ibu">Ibu</option>
                            <option value="Saudara/i">Saudara/i</option>
                        </select>
                        <input type="text" name="nama_donatur_raw" id="nama_donatur_raw" required placeholder="Nama Lengkap">
                    </div>
                </div>
                <div class="form-group">
                    <label>Nomor WhatsApp:</label>
                    <input type="text" name="no_wa">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Status Donasi:</label>
                    <select name="status_donasi" id="status_donasi_select">
                        <option value="Rutin">Rutin</option>
                        <option value="Insidental">Insidental</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="rutinitas_date_group">
                <label>Tanggal Rutinitas Awal:</label>
                <input type="date" name="tgl_rutinitas" id="tgl_rutinitas_input">
                <small style="color: #6B7280; display: block; margin-top: 5px;">Pilih tanggal donasi rutin dimulai.</small>
            </div>
            <div class="form-group">
                <label>Foto (Opsional):</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>

        <div class="form-section">
            <h2><i class="fas fa-map-marked-alt"></i> Detail Alamat</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Provinsi:</label>
                    <select id="provinsi" name="provinsi_kode" required></select>
                </div>
                <div class="form-group">
                    <label>Kabupaten/Kota:</label>
                    <select id="kabupaten" name="kabupaten_kode" required></select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Kecamatan:</label>
                    <select id="kecamatan" name="kecamatan_kode" required></select>
                </div>
                <div class="form-group">
                    <label>Kelurahan/Desa:</label>
                    <select id="kelurahan" name="kelurahan_kode" required></select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Alamat Detail (Nama Jalan, Blok, RT/RW):</label>
                <textarea name="alamat_detail_manual" id="alamat_detail_manual" rows="2" required placeholder="Contoh: Jl. Sudirman No. 10, RT 01/RW 02"></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Donatur</button>
            <a href="donatur.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo $base_path; ?>assets/js/wilayah.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('donaturForm');
    const finalAddressInput = document.getElementById('alamat_lengkap_hidden');
    const finalNameInput = document.getElementById('nama_donatur_final');
    const manualAddressInput = document.getElementById('alamat_detail_manual');
    
    // === START RUTINITAS DATE LOGIC ===
    const statusDonasiSelect = document.getElementById('status_donasi_select');
    const rutinitasDateGroup = document.getElementById('rutinitas_date_group');
    const tglRutinitasInput = document.getElementById('tgl_rutinitas_input');

    function toggleRutinitasDate() {
        if (statusDonasiSelect.value === 'Rutin') {
            rutinitasDateGroup.style.display = 'block';
            tglRutinitasInput.required = true; // Diperlukan hanya jika 'Rutin'
        } else {
            rutinitasDateGroup.style.display = 'none';
            tglRutinitasInput.required = false;
            // Clear value if hidden (optional, but good practice)
            tglRutinitasInput.value = ''; 
        }
    }

    statusDonasiSelect.addEventListener('change', toggleRutinitasDate);

    // Set initial state
    toggleRutinitasDate(); 
    // === END RUTINITAS DATE LOGIC ===

    
    // === INISIALISASI API WILAYAH (Menggunakan hidden fields baru) ===
    if (typeof initWilayah !== 'undefined') {
        initWilayah(
            '#provinsi', 
            '#kabupaten', 
            '#kecamatan', 
            '#kelurahan', 
            'get_wilayah.php', 
            {
                province_name_input: '#ID_Provinsi_nama_input', 
                city_name_input: '#ID_Kabupaten_nama_input',
                district_name_input: '#ID_Kecamatan_nama_input',
                village_name_input: '#ID_Kelurahan_nama_input'
            }
        );
    } else {
        console.error("Error: wilayah.js failed to load or initWilayah is undefined.");
    }
    // ====================================================================

    // === Logic Submit untuk Menggabungkan Nama dan Alamat ===
    form.addEventListener('submit', (e) => {
        
        // NEW: Menggabungkan Sapaan dan Nama
        const panggilan = document.getElementById('panggilan').value;
        const namaRaw = document.getElementById('nama_donatur_raw').value.trim();

        if (!panggilan || !namaRaw) {
            e.preventDefault(); 
            Swal.fire('Peringatan', 'Mohon isi Sapaan dan Nama Donatur dengan lengkap.', 'warning');
            return; 
        }
        
        // Menggabungkan Sapaan dan Nama Lengkap: Sapaan [Spasi] Nama Lengkap
        const fullNama = `${panggilan} ${namaRaw}`;
        finalNameInput.value = fullNama; // Set the final combined name
        
        // Logic Penggabungan Alamat 
        const alamatDetail = manualAddressInput.value.trim();
        const kelurahanNama = document.getElementById('ID_Kelurahan_nama_input').value;
        const kecamatanNama = document.getElementById('ID_Kecamatan_nama_input').value;
        const kabupatenNama = document.getElementById('ID_Kabupaten_nama_input').value;
        const provinsiNama = document.getElementById('ID_Provinsi_nama_input').value;
        
        // Cek jika wilayah belum dipilih (Kelurahan harus ada)
        if (!alamatDetail || !kelurahanNama) {
            e.preventDefault(); 
            Swal.fire('Peringatan', 'Mohon isi Alamat Detail dan pastikan semua dropdown wilayah sudah dipilih.', 'warning');
            return; 
        }
        
        // Menggabungkan alamat lengkap: Detail, Kelurahan, Kecamatan, Kab/Kota, Provinsi
        const fullAddress = `${alamatDetail}, Kel. ${kelurahanNama}, Kec. ${kecamatanNama}, ${kabupatenNama}, ${provinsiNama}`;
        
        // Mengirim alamat lengkap ke hidden field yang digunakan oleh proses_donatur.php
        finalAddressInput.value = fullAddress;
    });


});
</script>

<?php
include '../includes/footer.php';
$conn->close();
?>