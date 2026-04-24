<?php
// File: config/db_helpers.php

// Fungsi untuk mengeksekusi query SELECT sederhana (tanpa parameter eksternal)
function fetch_simple_value($conn, $sql_template) {
    if ($result = $conn->query($sql_template)) {
        if ($row = $result->fetch_assoc()) {
            return $row['total'] ?? 0;
        }
    }
    return 0;
}

// Fungsi untuk mengeksekusi query dengan 1 parameter (menggunakan Prepared Statement)
function fetch_single_param_value($conn, $sql_template, $param_value, $param_type = 's') {
    $stmt = $conn->prepare($sql_template);
    if ($stmt === false) {
        // Jika prepared statement gagal, kembali ke 0
        return 0;
    }
    $stmt->bind_param($param_type, $param_value);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['total'] ?? 0;
}
?>