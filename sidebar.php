<?php 
$current_page = basename($_SERVER['PHP_SELF']); 

$q_badge = $conn->query("SELECT COUNT(*) as low_stock FROM inventaris WHERE stok < 10");
$low_stock_count = $q_badge->fetch_assoc()['low_stock'];

// Logika if-else untuk menu aktif
$active_index = "";
$active_transaksi = "";
$active_inventaris = "";
$active_laporan = "";

if ($current_page == 'index.php') {
    $active_index = "active";
}
if ($current_page == 'transaksi.php') {
    $active_transaksi = "active";
}
if ($current_page == 'inventaris.php') {
    $active_inventaris = "active";
}
if ($current_page == 'laporan.php') {
    $active_laporan = "active";
}
?>

<!-- CDN RemixIcon agar ikon selalu termuat di semua halaman -->
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet">

<aside class="sidebar">
    <div class="logo">
        <div class="logo-icon">W</div>
        <h2>Fintech.</h2>
    </div>
    <div class="workspace-card">
        <div class="ws-icon">
            <i class="ri-store-2-line"></i>
        </div>
        <div class="ws-info">
            <h4>Kopi Senja</h4>
            <p>Workspace bisnis</p>
        </div>
        <div class="ws-arrow">
            <i class="ri-arrow-down-s-line"></i>
        </div>
    </div>
    <p class="menu-label">MENU UTAMA</p>
    <nav class="menu">
        <a href="index.php" class="<?= $active_index ?>">
            <i class="ri-dashboard-line"></i>
            Dashboard
        </a>
        <a href="transaksi.php" class="<?= $active_transaksi ?>">
            <i class="ri-exchange-dollar-line"></i>
            Transaksi
        </a>
        <a href="inventaris.php" class="<?= $active_inventaris ?>">
            <i class="ri-box-3-line"></i>
            Inventaris
            <?php if ($low_stock_count > 0) { ?>
                <span class="badge"><?= $low_stock_count ?></span>
            <?php } ?>
        </a>
        <a href="laporan.php" class="<?= $active_laporan ?>">
            <i class="ri-file-text-line"></i>
            Laporan
        </a>
    </nav>
</aside>