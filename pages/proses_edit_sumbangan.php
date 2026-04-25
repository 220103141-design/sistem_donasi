<?php
session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_kwitansi = $_POST['id_kwitansi'] ?? '';
    $zakat_profesi = $_POST['zakat_profesi'] ?? 0;
    $zakat_maal = $_POST['zakat_maal'] ?? 0;
    $infaq = $_POST['infaq'] ?? 0;
    $sedekah = $_POST['sedekah'] ?? 0;
    $fidyah = $_POST['fidyah'] ?? 0;

    $sql = "UPDATE Sumbangan SET Zakat_Profesi = ?, Zakat_Maal = ?, Infaq = ?, Sedekah = ?, Fidyah = ? WHERE ID_Kwitansi_ZIS = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddddds", $zakat_profesi, $zakat_maal, $infaq, $sedekah, $fidyah, $id_kwitansi);

    if ($stmt->execute()) {
        header("Location: verifikasi-donasi.php");
        exit;
    } else {
        die("Error saat memperbarui sumbangan: " . $stmt->error);
    }
} else {
    header("Location: verifikasi-donasi.php");
    exit;
}

$conn->close();
?>