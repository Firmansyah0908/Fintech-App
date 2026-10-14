<?php
include 'koneksi.php'; // Otomatis menjalankan session_start()

// Jika sudah login, langsung ke Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cari user menggunakan prepared statement agar aman dari SQL Injection
    $stmt = $conn->prepare("SELECT id, nama_lengkap, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Cek password murni (tanpa enkripsi)
        if ($password === $row['password']) {
            // Berhasil login, simpan data ke sesi
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Fintech App</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon" style="margin: 0 auto 16px auto;">W</div>
                <h2>Selamat Datang</h2>
                <p class="text-muted">Silakan masuk ke akun Fintech Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                </div>
                <button type="submit" class="btn-primary w-100" style="margin-top: 10px;">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>