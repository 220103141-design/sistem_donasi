<?php
// File: pages/get_wilayah.php
include '../config/database.php'; 

$id = isset($_GET['id']) ? $_GET['id'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : 0;
$query_result = array();

// Logika pengambilan data dari tabel 'wilayah' menggunakan kode
if ($id) {
    if ($level == 1) { // Ambil Kabupaten/Kota, kode length = 5
        $query = "SELECT kode, nama FROM wilayah WHERE LEFT(kode, 2) = ? AND LENGTH(kode) = 5 ORDER BY nama";
    } elseif ($level == 2) { // Ambil Kecamatan, kode length = 8
        $query = "SELECT kode, nama FROM wilayah WHERE LEFT(kode, 5) = ? AND LENGTH(kode) = 8 ORDER BY nama";
    } elseif ($level == 3) { // Ambil Kelurahan/Desa, kode length = 13
        $query = "SELECT kode, nama FROM wilayah WHERE LEFT(kode, 8) = ? AND LENGTH(kode) = 13 ORDER BY nama";
    } else {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $query_result[] = $row;
        }
        $stmt->close();
    }
} else {
    // Ambil Provinsi (level 0), kode length = 2
    $query = "SELECT kode, nama FROM wilayah WHERE LENGTH(kode) = 2 ORDER BY nama";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $query_result[] = $row;
        }
    }
}

// Mengembalikan hasil dalam format JSON
header('Content-Type: application/json');
echo json_encode($query_result);

$conn->close();
?>