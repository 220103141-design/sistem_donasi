<?php
session_start();
include '../config/database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $credential = $_POST['credential'] ?? '';

    // Coba login sebagai User (Pimpinan, Kepala LKSA, dll.)
    // --- PERBAIKAN: HANYA AMBIL DATA USER BERDASARKAN NAMA USER ---
    $sql_user = "SELECT * FROM User WHERE Nama_User = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_login_success = false;

    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        
        // --- PERBAIKAN KRITIS: VERIFIKASI PASSWORD MENGGUNAKAN password_verify() ---
        if (password_verify($credential, $user['Password'])) {
            $user_login_success = true;
        } 
        // KLAUSUL TRANSISIONAL (UNTUK MEMPERTIMBANGKAN PASSWORD LAMA YANG MASIH PLAINTEXT)
        // KLAUSUL INI PERLU DIHAPUS SETELAH SEMUA USER DI-MIGRASI/RESET PASSWORD!
        elseif ($credential === $user['Password']) {
            $user_login_success = true;
            // Di sini, Anda bisa menambahkan logika untuk me-hash dan mengupdate password di DB
        }
        // END KLAUSUL TRANSISIONAL
        
        if ($user_login_success) {
            $_SESSION['loggedin'] = true;
            $_SESSION['id_user'] = $user['Id_user'];
            $_SESSION['id_lksa'] = $user['Id_lksa'];
            $_SESSION['jabatan'] = $user['Jabatan'];
            // --- PERBAIKAN SESI: Tambahkan Nama User ke Sesi ---
            $_SESSION['nama_user'] = $user['Nama_User'];
            
            $stmt_user->close();
            header("Location: ../index.php");
            exit;
        }
        $stmt_user->close();
    }


    // Coba login sebagai Donatur
    $sql_donatur = "SELECT * FROM Donatur WHERE Nama_Donatur = ? AND NO_WA = ?";
    $stmt_donatur = $conn->prepare($sql_donatur);
    $stmt_donatur->bind_param("ss", $username, $credential);
    $stmt_donatur->execute();
    $result_donatur = $stmt_donatur->get_result();

    if ($result_donatur->num_rows > 0) {
        $donatur = $result_donatur->fetch_assoc();
        $_SESSION['id_donatur'] = $donatur['ID_donatur'];
        $_SESSION['nama_donatur'] = $donatur['Nama_Donatur'];
        $_SESSION['is_donatur'] = true;
        $stmt_donatur->close();
        header("Location: ../pages/dashboard_donatur.php");
        exit;
    }
    $stmt_donatur->close();

    // Coba login sebagai Pemilik Kotak Amal
    $sql_pemilik = "SELECT ID_KotakAmal, Nama_Pemilik FROM KotakAmal WHERE Nama_Pemilik = ? AND WA_Pemilik = ?";
    $stmt_pemilik = $conn->prepare($sql_pemilik);
    $stmt_pemilik->bind_param("ss", $username, $credential);
    $stmt_pemilik->execute();
    $result_pemilik = $stmt_pemilik->get_result();
    
    if ($result_pemilik->num_rows > 0) {
        $pemilik = $result_pemilik->fetch_assoc();
        $_SESSION['id_kotak_amal'] = $pemilik['ID_KotakAmal'];
        $_SESSION['nama_pemilik'] = $pemilik['Nama_Pemilik'];
        $_SESSION['is_pemilik_kotak_amal'] = true;
        $stmt_pemilik->close();
        header("Location: ../pages/dashboard_pemilik_kotak_amal.php");
        exit;
    }
    $stmt_pemilik->close();

    $error = "Kredensial login salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login Sistem</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <style>
        /* Memastikan latar belakang dan pemusatan */
        body {
            background-image: url('../assets/img/bg.png'); 
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            width: 100vw;
            height: 100vh;
            margin: 0;
            padding: 0;
            background-color: transparent !important;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="login-container">
        
        <div style="text-align: center; line-height: 1.2; margin-bottom: 35px;">
            
            <img src="../assets/img/give_track_logo_final.png" alt="Give Track Logo System"
                style="height: 50px; width: auto; margin-bottom: 15px;">
        </div>
        
        <?php if (!empty($error)) { echo "<p class='error-message'>$error</p>"; } ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username:
                </label>
                <input type="text" id="username" name="username" required placeholder="Nama User/Donatur/Pemilik KA">
            </div>
            <div class="form-group">
                <label for="credential">
                    <i class="fas fa-key"></i> Password:
                </label>
                <input type="password" id="credential" name="credential" required placeholder="Password/No. WA">
            </div>
            <button type="submit" class="btn-login">
                Login
            </button>
        </form>
    </div>
</body>
</html>