<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "uangku_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

function formatRupiah($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Helper Tanggal Indonesia Dinamis
function tanggalIndo($tanggal) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $timestamp = strtotime($tanggal);
    return $hari[date('w', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}
?>