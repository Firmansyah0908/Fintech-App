<?php
include 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if ($row = mysqli_fetch_assoc($q)) {
        if ($password == $row['password']) {
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
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon" style="margin: 0 auto 16px auto;">W</div>
                <h2>Selamat Datang</h2>
                <p class="text-muted">Silakan masuk ke akun Fintech Anda</p>
            </div>
            
            <?php if ($error != ''): ?>
                <div class="alert alert-danger"><?= $error ?></div>
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