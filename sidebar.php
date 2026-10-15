<?php 
include 'koneksi.php';

$page = basename($_SERVER['PHP_SELF']); 

$q_badge = mysqli_query($conn, "SELECT COUNT(*) as low_stock FROM inventaris WHERE stok < 10");
$row_badge = mysqli_fetch_assoc($q_badge);
$low_stock_count = $row_badge['low_stock'];
?>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet">

<aside class="sidebar">
    <div class="logo">
        <div class="logo-icon">W</div>
        <h2>Fintech.</h2>
    </div>
    <div class="workspace-card">
        <div class="ws-icon"><i class="ri-store-2-line"></i></div>
        <div class="ws-info">
            <h4>Kopi Senja</h4>
            <p>Workspace bisnis</p>
        </div>
        <div class="ws-arrow"><i class="ri-arrow-down-s-line"></i></div>
    </div>
    <p class="menu-label">MENU UTAMA</p>
    <nav class="menu">
        <a href="index.php" class="<?= $page == 'index.php' ? 'active' : '' ?>">
            <i class="ri-dashboard-line"></i> Dashboard
        </a>
        <a href="transaksi.php" class="<?= $page == 'transaksi.php' ? 'active' : '' ?>">
            <i class="ri-exchange-dollar-line"></i> Transaksi
        </a>
        <a href="inventaris.php" class="<?= $page == 'inventaris.php' ? 'active' : '' ?>">
            <i class="ri-box-3-line"></i> Inventaris
            <?php if ($low_stock_count > 0): ?>
                <span class="badge"><?= $low_stock_count ?></span>
            <?php endif; ?>
        </a>
        <a href="laporan.php" class="<?= $page == 'laporan.php' ? 'active' : '' ?>">
            <i class="ri-file-text-line"></i> Laporan
        </a>
    </nav>
</aside>