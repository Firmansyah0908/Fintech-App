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

<aside class="sidebar">
    <div class="logo">
        <div class="logo-icon">W</div>
        <h2>Fintech.</h2>
    </div>
    <div class="workspace-card">
        <div class="ws-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
        </div>
        <div class="ws-info">
            <h4>Kopi Senja</h4>
            <p>Workspace bisnis</p>
        </div>
        <div class="ws-arrow">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </div>
    </div>
    <p class="menu-label">MENU UTAMA</p>
    <nav class="menu">
        <a href="index.php" class="<?= $active_index ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        <a href="transaksi.php" class="<?= $active_transaksi ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
            Transaksi
        </a>
        <a href="inventaris.php" class="<?= $active_inventaris ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Inventaris
            <?php if ($low_stock_count > 0) { ?>
                <span class="badge"><?= $low_stock_count ?></span>
            <?php } ?>
        </a>
        <a href="laporan.php" class="<?= $active_laporan ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Laporan
        </a>
    </nav>
</aside>