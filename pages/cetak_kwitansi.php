<?php
session_start();
include '../config/database.php';

// Ambil ID kwitansi dari URL
$id_kwitansi = $_GET['id'] ?? '';
if (empty($id_kwitansi)) {
    die("ID Kwitansi tidak ditemukan.");
}

// Ambil data lengkap kwitansi
$sql = "SELECT s.*, d.Nama_Donatur, d.Alamat_Lengkap, u.Nama_User, l.Nama_Pimpinan, l.Alamat AS Alamat_LKSA, l.Nomor_WA AS WA_LKSA
        FROM Sumbangan s
        LEFT JOIN Donatur d ON s.ID_donatur = d.ID_donatur
        LEFT JOIN User u ON s.Id_user = u.Id_user
        LEFT JOIN LKSA l ON s.Id_lksa = l.Id_lksa
        WHERE s.ID_Kwitansi_ZIS = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kwitansi);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Data kwitansi tidak ditemukan.");
}

// Hitung total sumbangan
$total_sumbangan = $data['Zakat_Profesi'] + $data['Zakat_Maal'] + $data['Infaq'] + $data['Sedekah'] + $data['Fidyah'];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi #<?php echo htmlspecialchars($data['ID_Kwitansi_ZIS']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
            color: #333;
        }
        .kwitansi-container {
            width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 2em;
            font-family: 'Poppins', sans-serif;
        }
        .kwitansi-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .kwitansi-info div {
            width: 45%;
        }
        .kwitansi-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .footer div {
            text-align: center;
        }
        @media print {
            body { background-color: #fff; }
            .kwitansi-container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="kwitansi-container">
        <div class="header">
            <h1>Kwitansi Donasi</h1>
            <p><?php echo htmlspecialchars($data['Id_lksa']); ?> - <?php echo htmlspecialchars($data['Alamat_LKSA']); ?></p>
        </div>
        <div class="kwitansi-info">
            <div>
                <p><strong>Nomor Kwitansi:</strong> <?php echo htmlspecialchars($data['ID_Kwitansi_ZIS']); ?></p>
                <p><strong>Tanggal:</strong> <?php echo htmlspecialchars($data['Tgl']); ?></p>
                <p><strong>Nama Donatur:</strong> <?php echo htmlspecialchars($data['Nama_Donatur']); ?></p>
                <p><strong>Alamat Donatur:</strong> <?php echo htmlspecialchars($data['Alamat_Lengkap']); ?></p>
            </div>
            <div>
                <p><strong>Telah diterima dari:</strong> <?php echo htmlspecialchars($data['Nama_Donatur']); ?></p>
                <p><strong>Jumlah:</strong> Rp <?php echo number_format($total_sumbangan); ?></p>
                <p><strong>Untuk Pembayaran:</strong> Sumbangan ZIS</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Jenis Donasi</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Zakat Profesi</td><td>Rp <?php echo number_format($data['Zakat_Profesi']); ?></td></tr>
                <tr><td>Zakat Maal</td><td>Rp <?php echo number_format($data['Zakat_Maal']); ?></td></tr>
                <tr><td>Infaq</td><td>Rp <?php echo number_format($data['Infaq']); ?></td></tr>
                <tr><td>Sedekah</td><td>Rp <?php echo number_format($data['Sedekah']); ?></td></tr>
                <tr><td>Fidyah</td><td>Rp <?php echo number_format($data['Fidyah']); ?></td></tr>
                <?php if (!empty($data['Natura'])) { ?>
                    <tr><td>Natura</td><td><?php echo htmlspecialchars($data['Natura']); ?></td></tr>
                <?php } ?>
                <tr class="total">
                    <td>Total</td>
                    <td>Rp <?php echo number_format($total_sumbangan); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <div>
                <p>Hormat kami,</p>
                <br><br>
                <p><?php echo htmlspecialchars($data['Nama_User']); ?></p>
                <p>Petugas</p>
            </div>
            <div>
                <p><?php echo htmlspecialchars($data['Id_lksa']); ?></p>
                <br><br>
                <p><?php echo htmlspecialchars($data['Nama_Pimpinan']); ?></p>
                <p>Pimpinan</p>
            </div>
        </div>
    </div>
</body>
</html>