<?php
session_start();
include '../config/database.php';

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

$id_user = $_SESSION['id_user'];
$id_lksa = $_SESSION['id_lksa'];

$sidebar_stats = ''; 

include '../includes/header.php'; 

$base_path = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
?>
<div class="form-container">
    <h1>Tambah Kotak Amal Baru</h1>
    <form action="proses_kotak_amal.php" method="POST" enctype="multipart/form-data" id="kotakAmalForm">
        <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($id_user); ?>">
        <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($id_lksa); ?>">
        
        <input type="hidden" name="alamat_toko" id="alamat_toko_hidden_final">
        
        <div class="form-section">
            <h2>Informasi Tempat</h2>
            <div class="form-group">
                <label>Nama Tempat:</label>
                <input type="text" name="nama_toko" required>
            </div>
        </div>

        <div class="form-section">
            <h2><i class="fas fa-map-marked-alt"></i> Lokasi Berdasarkan API Wilayah</h2>
            
            <div class="form-group">
                <label>Provinsi:</label>
                <select id="provinsi" name="provinsi_kode" required></select>
                <input type="hidden" name="ID_Provinsi" id="ID_Provinsi_nama"> 
            </div>
            
            <div class="form-group">
                <label>Kabupaten/Kota:</label>
                <select id="kabupaten" name="kabupaten_kode" required></select>
                <input type="hidden" name="ID_Kabupaten" id="ID_Kabupaten_nama">
            </div>

            <div class="form-group">
                <label>Kecamatan:</label>
                <select id="kecamatan" name="kecamatan_kode" required></select>
                <input type="hidden" name="ID_Kecamatan" id="ID_Kecamatan_nama">
            </div>
            
            <div class="form-group">
                <label>Kelurahan/Desa:</label>
                <select id="kelurahan" name="kelurahan_kode" required></select>
                <input type="hidden" name="ID_Kelurahan" id="ID_Kelurahan_nama">
            </div>
            
            <div class="form-group">
                <label>Alamat Detail (Nama Jalan, Blok, RT/RW):</label>
                <textarea name="alamat_detail_manual" id="alamat_detail_manual" rows="2" required placeholder="Contoh: Jl. Sudirman No. 10, RT 01/RW 02"></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h2><i class="fas fa-link"></i> Link Google Maps (URL)</h2>
            <p>Masukkan link Google Maps yang ingin ditampilkan di peta.</p>
            
            <div class="form-group">
                <label>Link Google Maps (URL):</label>
                <input type="url" name="google_maps_link" id="google_maps_link" placeholder="Contoh: https://maps.app.goo.gl/..." >
            </div>
        </div>
        <div class="form-section">
            <h2>Informasi Pemilik & Jadwal</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Pemilik:</label>
                    <input type="text" name="nama_pemilik">
                </div>
                <div class="form-group">
                    <label>Nomor WA Pemilik:</label>
                    <input type="text" name="wa_pemilik">
                </div>
            </div>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                 <div class="form-group">
                    <label>Tanggal Pengambilan Pertama:</label>
                    <input type="date" name="jadwal_pengambilan" required> 
                </div>
                <div class="form-group">
                    <label>Email Pemilik:</label>
                    <input type="email" name="email_pemilik">
                </div>
            </div>
            
            <div class="form-group">
                <label>Unggah Foto:</label>
                <input type="file" name="foto" accept="image/*">
            </div>
            
            <div class="form-group">
                <label>Keterangan Tambahan:</label>
                <textarea name="keterangan" rows="4" cols="50"></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Kotak Amal</button>
            <a href="kotak-amal.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="<?php echo $base_path; ?>assets/js/wilayah.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('kotakAmalForm');
    const finalAddressInput = document.getElementById('alamat_toko_hidden_final');
    const manualAddressInput = document.getElementById('alamat_detail_manual');
    
    // ====================================================================
    // === INISIALISASI API WILAYAH (Tetap) ===
    
    if (typeof initWilayah !== 'undefined') {
        initWilayah(
            '#provinsi', 
            '#kabupaten', 
            '#kecamatan', 
            '#kelurahan', 
            'get_wilayah.php', // Path relatif ke pages/get_wilayah.php
            {
                province_name_input: '#ID_Provinsi_nama', // Hidden field untuk Nama Provinsi
                city_name_input: '#ID_Kabupaten_nama',
                district_name_input: '#ID_Kecamatan_nama',
                village_name_input: '#ID_Kelurahan_nama'
            }
        );
    } else {
        console.error("Error: wilayah.js failed to load atau initWilayah tidak terdefinisi.");
    }
    // ====================================================================

    // === Logic Submit untuk Menggabungkan Alamat (Tetap) ===
    
    form.addEventListener('submit', (e) => {
        
        const alamatDetail = manualAddressInput.value.trim();
        const kelurahanNama = document.getElementById('ID_Kelurahan_nama').value;
        const kecamatanNama = document.getElementById('ID_Kecamatan_nama').value;
        const kabupatenNama = document.getElementById('ID_Kabupaten_nama').value;
        const provinsiNama = document.getElementById('ID_Provinsi_nama').value;
        
        if (!alamatDetail || !kelurahanNama) {
            e.preventDefault(); 
            Swal.fire('Peringatan', 'Mohon isi Alamat Detail dan pastikan semua dropdown wilayah sudah dipilih.', 'warning');
            return; 
        }
        
        // Menggabungkan alamat lengkap: Detail, Kelurahan, Kecamatan, Kab/Kota, Provinsi
        const fullAddress = `${alamatDetail}, Kel. ${kelurahanNama}, Kec. ${kecamatanNama}, ${kabupatenNama}, ${provinsiNama}`;
        
        // Mengirim alamat lengkap ke hidden field yang digunakan oleh proses_kotak_amal.php
        finalAddressInput.value = fullAddress;
    });


});
</script>

<?php
include '../includes/footer.php';
$conn->close();
?>