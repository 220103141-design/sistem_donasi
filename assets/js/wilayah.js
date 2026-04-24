// File: assets/js/wilayah.js

function initWilayah(provinsiSelector, kabupatenSelector, kecamatanSelector, kelurahanSelector, apiPath, options = {}) {
    // 1. Ambil elemen SELECT
    const $provinsi = document.querySelector(provinsiSelector);
    const $kabupaten = document.querySelector(kabupatenSelector);
    const $kecamatan = document.querySelector(kecamatanSelector);
    const $kelurahan = document.querySelector(kelurahanSelector);

    // 2. Ambil elemen INPUT HIDDEN untuk menyimpan nama wilayah
    const $provinsiNameInput = options.province_name_input ? document.querySelector(options.province_name_input) : null;
    const $kabupatenNameInput = options.city_name_input ? document.querySelector(options.city_name_input) : null;
    const $kecamatanNameInput = options.district_name_input ? document.querySelector(options.district_name_input) : null;
    const $kelurahanNameInput = options.village_name_input ? document.querySelector(options.village_name_input) : null;
    
    // 3. Fungsi Helper untuk membersihkan dan menonaktifkan dropdown
    const clearAndSetDefault = (element, defaultText = 'Pilih') => {
        element.innerHTML = `<option value="">-- ${defaultText} --</option>`;
        element.disabled = true;
        element.classList.add('disabled-select'); 
    };
    
    // 4. Fungsi Helper untuk mengupdate nilai field hidden (Nama Wilayah)
    const updateHiddenName = (selectElement, nameInputElement) => {
        if (nameInputElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            // Jika option yang dipilih adalah default text, set nilai hidden ke kosong
            nameInputElement.value = selectedOption.textContent.trim().startsWith('--') ? '' : selectedOption.textContent.trim();
        }
    };
    
    // 5. Bersihkan level di bawahnya pada saat inisialisasi
    clearAndSetDefault($kabupaten, 'Pilih Kabupaten/Kota');
    clearAndSetDefault($kecamatan, 'Pilih Kecamatan');
    clearAndSetDefault($kelurahan, 'Pilih Kelurahan/Desa');

    // 6. Fungsi utama untuk mengambil data dari API (get_wilayah.php)
    const fetchData = async (url, element, defaultText) => {
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            // Bersihkan dan aktifkan elemen
            clearAndSetDefault(element, defaultText);
            element.disabled = false;
            element.classList.remove('disabled-select');

            // Isi dengan data baru
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.kode; // Menggunakan kode sebagai value
                option.textContent = item.nama; // Menggunakan nama sebagai display text
                element.appendChild(option);
            });
            return data;
        } catch (error) {
            console.error('Error fetching data:', error);
            clearAndSetDefault(element, 'Gagal memuat data');
            return [];
        }
    };

    // 7. Event Listeners: Mengambil data bertingkat
    
    // A. Load Provinsi
    const loadProvinsi = () => {
        fetchData(`${apiPath}?level=0`, $provinsi, 'Pilih Provinsi');
    };
    
    // B. Listener: Perubahan Provinsi -> Muat Kabupaten
    $provinsi.addEventListener('change', () => {
        const id = $provinsi.value; 
        updateHiddenName($provinsi, $provinsiNameInput); 

        if (id) {
            fetchData(`${apiPath}?id=${id}&level=1`, $kabupaten, 'Pilih Kabupaten/Kota');
        } else {
            clearAndSetDefault($kabupaten, 'Pilih Kabupaten/Kota');
        }
        clearAndSetDefault($kecamatan, 'Pilih Kecamatan');
        clearAndSetDefault($kelurahan, 'Pilih Kelurahan/Desa');
    });

    // C. Listener: Perubahan Kabupaten -> Muat Kecamatan
    $kabupaten.addEventListener('change', () => {
        const id = $kabupaten.value; 
        updateHiddenName($kabupaten, $kabupatenNameInput); 

        if (id) {
            fetchData(`${apiPath}?id=${id}&level=2`, $kecamatan, 'Pilih Kecamatan');
        } else {
            clearAndSetDefault($kecamatan, 'Pilih Kecamatan');
        }
        clearAndSetDefault($kelurahan, 'Pilih Kelurahan/Desa');
    });

    // D. Listener: Perubahan Kecamatan -> Muat Kelurahan
    $kecamatan.addEventListener('change', () => {
        const id = $kecamatan.value; 
        updateHiddenName($kecamatan, $kecamatanNameInput); 

        if (id) {
            fetchData(`${apiPath}?id=${id}&level=3`, $kelurahan, 'Pilih Kelurahan/Desa');
        } else {
            clearAndSetDefault($kelurahan, 'Pilih Kelurahan/Desa');
        }
    });
    
    // E. Listener: Perubahan Kelurahan -> Simpan Nama Kelurahan
    $kelurahan.addEventListener('change', () => {
        updateHiddenName($kelurahan, $kelurahanNameInput); 
    });

    // 8. Panggil fungsi untuk memuat data awal
    loadProvinsi();
}

// Tambahkan sedikit CSS agar dropdown yang nonaktif terlihat berbeda
const style = document.createElement('style');
style.textContent = `
    .disabled-select {
        background-color: #f7f7f7 !important;
        color: #999 !important;
        cursor: not-allowed;
    }
`;
document.head.appendChild(style);