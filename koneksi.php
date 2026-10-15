<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_connect("localhost", "root", "", "uangku_db");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Cek login (kecuali sedang di halaman login.php)
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('tanggalIndo')) {
    function tanggalIndo($tanggal) {
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $ts = strtotime($tanggal);
        return $hari[date('w', $ts)] . ', ' . date('d', $ts) . ' ' . $bulan[date('n', $ts)] . ' ' . date('Y', $ts);
    }
}
?>