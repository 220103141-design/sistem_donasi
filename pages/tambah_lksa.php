<?php
session_start();
include '../config/database.php';

if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak.");
}

// Pastikan sidebar_stats diatur ke string kosong sebelum memuat header
$sidebar_stats = ''; 

include '../includes/header.php'; // LOKASI BARU

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
?>
<div class="form-container">
    <h1><i class="fas fa-building" style="color: #06B6D4;"></i> Tambah LKSA Baru (Kantor Cabang)</h1>
    
    <form action="proses_lksa.php" method="POST" enctype="multipart/form-data" id="lksaForm">
        
        <div class="form-section">
            <h2>Informasi Utama</h2>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Nama LKSA Lengkap:</label>
                    <input type="text" name="nama_lksa" required placeholder="Contoh: LKSA Nur Hidayah Surakarta">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2><i class="fas fa-map-marked-alt"></i> Lokasi (Untuk Kode ID LKSA)</h2>
            <p>Pilih lokasi geografis. Nama Kota/Kabupaten akan digunakan sebagai kode ID LKSA.</p>
            
            <input type="hidden" name="nama_kabupaten_for_id" id="ID_Kabupaten_nama">
            <input type="hidden" name="alamat_lengkap_final" id="alamat_lengkap_final">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Provinsi:</label>
                    <select id="provinsi" name="provinsi_kode" required></select>
                    <input type="hidden" name="ID_Provinsi_nama" id="ID_Provinsi_nama"> 
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
                    <input type="hidden" name="ID_Kecamatan_nama" id="ID_Kecamatan_nama">
                </div>
                <div class="form-group">
                    <label>Kelurahan/Desa:</label>
                    <select id="kelurahan" name="kelurahan_kode" required></select>
                    <input type="hidden" name="ID_Kelurahan_nama" id="ID_Kelurahan_nama">
                </div>
            </div>

            <div class="form-group">
                <label>Alamat Detail (Nama Jalan, Blok, RT/RW):</label>
                <textarea name="alamat_detail_manual" id="alamat_detail_manual" rows="2" required placeholder="Contoh: Jl. Diponegoro No. 10"></textarea>
                <small style="color: #6B7280; display: block; margin-top: 5px;">Alamat lengkap akan digabungkan secara otomatis.</small>
            </div>
        </div>

        <div class="form-section">
            <h2>Informasi Kontak</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nomor WA:</label>
                    <input type="text" name="nomor_wa_lksa" placeholder="Contoh: 0812xxxxxx">
                </div>
                <div class="form-group">
                    <label>Email LKSA:</label>
                    <input type="email" name="email_lksa" placeholder="Contoh: admin@lksa.com">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Logo LKSA</h2>
            <div class="form-group">
                <label>Unggah Logo LKSA (Opsional, Max 5MB):</label>
                <input type="file" name="logo" accept="image/*">
                <small style="color: #6B7280; display: block; margin-top: 5px;">Unggah logo resmi LKSA yang didaftarkan.</small>
            </div>
        </div>

        <div class="form-actions">
            <a href="lksa.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Daftarkan LKSA</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo $base_url; ?>assets/js/wilayah.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('lksaForm');
    const finalAddressInput = document.getElementById('alamat_lengkap_final');
    const manualAddressInput = document.getElementById('alamat_detail_manual');
    
    // Inisialisasi API Wilayah
    if (typeof initWilayah !== 'undefined') {
        initWilayah(
            '#provinsi', 
            '#kabupaten', 
            '#kecamatan', 
            '#kelurahan', 
            'get_wilayah.php', 
            {
                province_name_input: '#ID_Provinsi_nama', 
                city_name_input: '#ID_Kabupaten_nama', // Menargetkan input hidden untuk Nama Kabupaten (ID Prefix)
                district_name_input: '#ID_Kecamatan_nama',
                village_name_input: '#ID_Kelurahan_nama'
            }
        );
    } else {
        console.error("Error: wilayah.js failed to load or initWilayah is undefined.");
    }

    // Logic Submit untuk Menggabungkan Alamat
    form.addEventListener('submit', (e) => {
        
        const alamatDetail = manualAddressInput.value.trim();
        const kelurahanNama = document.getElementById('ID_Kelurahan_nama').value;
        const kecamatanNama = document.getElementById('ID_Kecamatan_nama').value;
        const kabupatenNama = document.getElementById('ID_Kabupaten_nama').value;
        const provinsiNama = document.getElementById('ID_Provinsi_nama').value;
        
        if (!alamatDetail || !kabupatenNama) {
            e.preventDefault(); 
            Swal.fire('Peringatan', 'Mohon isi Alamat Detail dan pastikan Kabupaten/Kota sudah dipilih.', 'warning');
            return; 
        }
        
        // Menggabungkan alamat lengkap: Detail, Kelurahan, Kecamatan, Kab/Kota, Provinsi
        const fullAddress = `${alamatDetail}, Kel. ${kelurahanNama}, Kec. ${kecamatanNama}, ${kabupatenNama}, ${provinsiNama}`;
        
        // Mengirim alamat lengkap ke hidden field
        finalAddressInput.value = fullAddress;
    });


});
</script>

<?php
include '../includes/footer.php';
$conn->close();
?>