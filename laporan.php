<?php include 'koneksi.php'; 

// 1. FILTER BULAN Laporan
$q_latest = $conn->query("SELECT MAX(tanggal) as max_date FROM transaksi");
$row_latest = $q_latest->fetch_assoc();

if ($row_latest['max_date'] != null) {
    $latest_date = $row_latest['max_date'];
} else {
    $latest_date = date('Y-m-d');
}

$active_m = isset($_GET['filter_bulan']) ? substr($_GET['filter_bulan'], 5, 2) : date('m', strtotime($latest_date));
$active_y = isset($_GET['filter_bulan']) ? substr($_GET['filter_bulan'], 0, 4) : date('Y', strtotime($latest_date));
$periode_str = date('F Y', strtotime("$active_y-$active_m-01"));

// Query Bulan Unik untuk Dropdown
$q_months = $conn->query("SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as value, DATE_FORMAT(tanggal, '%M %Y') as label FROM transaksi ORDER BY tanggal DESC");
$available_months = [];
while ($row = $q_months->fetch_assoc()) {
    $available_months[] = $row;
}

// 2. AMBIL DATA UNTUK LAPORAN (Dikelompokkan berdasarkan Kategori Fix)
// PENDAPATAN
$q_pendapatan = $conn->query("SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pemasukan' AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");

// HPP (Bahan Baku, Kemasan)
$q_hpp = $conn->query("SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pengeluaran' AND kategori IN ('Bahan Baku', 'Kemasan') AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");

// BEBAN OPERASIONAL (Pengeluaran selain HPP)
$q_beban = $conn->query("SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pengeluaran' AND kategori NOT IN ('Bahan Baku', 'Kemasan') AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - uangku.</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>
<body>
    <div class="app-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <?php include 'header.php'; ?>
            <div class="content-body">
                <div class="breadcrumb">Workspace / Laporan</div>
                <div class="page-header">
                    <div>
                        <h1>Laporan</h1>
                        <p class="text-muted">Lihat performa keuangan dan buat keputusan bisnis yang lebih baik.</p>
                    </div>
                    
                    <div style="display:flex; gap:16px;">
                        <form method="GET" action="laporan.php" id="formBulanLap">
                            <div class="custom-select-wrapper">
                                <svg class="calendar-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <select name="filter_bulan" class="custom-select" onchange="document.getElementById('formBulanLap').submit();">
                                    <?php foreach($available_months as $m) { 
                                        $selected = ($m['value'] == "$active_y-$active_m") ? 'selected' : '';
                                    ?>
                                        <option value="<?= $m['value'] ?>" <?= $selected ?>><?= $m['label'] ?></option>
                                    <?php } ?>
                                </select>
                                <svg class="chevron-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </div>
                        </form>
                        <button class="btn-primary" style="background:#fff; color:var(--text-main); border:1px solid var(--border); font-weight:600;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px; vertical-align:text-bottom;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export PDF
                        </button>
                    </div>
                </div>

                <div class="tabs"><div class="tab active">Laporan Laba Rugi</div></div>

                <div class="report-wrapper">
                    <div class="laporan-card" style="width: 100%; max-width: 800px;">
                        
                        <!-- Header Laporan -->
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 32px;">
                            <div>
                                <span style="color:var(--primary); font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">KOPI SENJA</span>
                                <h1 style="font-size:26px; margin: 4px 0;">Laporan Laba Rugi</h1>
                                <span class="text-muted" style="font-size:14px;">Periode: <?= $periode_str ?></span>
                            </div>
                            <div style="background: #ecfdf5; padding: 12px; border-radius: 12px; color: var(--primary);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                            </div>
                        </div>

                        <div class="header-laporan">
                            <span>Rincian</span>
                            <span>Jumlah (IDR)</span>
                        </div>

                        <!-- 1. PENDAPATAN -->
                        <div class="group-title">Pendapatan Kotor</div>
                        <?php 
                        $tot_pendapatan = 0;
                        if ($q_pendapatan->num_rows > 0) {
                            while($row = $q_pendapatan->fetch_assoc()) { 
                                $tot_pendapatan += $row['total'];
                        ?>
                            <div class="report-row"><span class="row-text"><?= htmlspecialchars($row['kategori']) ?></span><span class="row-value"><?= formatRupiah($row['total']) ?></span></div>
                        <?php 
                            }
                        } else { echo '<div class="report-row"><span class="row-text">-</span><span class="row-value">Rp 0</span></div>'; } 
                        ?>
                        <div class="subtotal-row"><span>Total Pendapatan</span><span><?= formatRupiah($tot_pendapatan) ?></span></div>

                        <!-- 2. HPP -->
                        <div class="group-title" style="margin-top: 32px;">Harga Pokok Penjualan (HPP)</div>
                        <?php 
                        $tot_hpp = 0;
                        if ($q_hpp->num_rows > 0) {
                            while($row = $q_hpp->fetch_assoc()) { 
                                $tot_hpp += $row['total'];
                        ?>
                            <div class="report-row"><span class="row-text"><?= htmlspecialchars($row['kategori']) ?></span><span class="row-value"><?= formatRupiah($row['total']) ?></span></div>
                        <?php 
                            }
                        } else { echo '<div class="report-row"><span class="row-text">-</span><span class="row-value">Rp 0</span></div>'; } 
                        ?>
                        <div class="subtotal-row"><span>Total HPP</span><span><?= formatRupiah($tot_hpp) ?></span></div>

                        <!-- 3. LABA KOTOR -->
                        <?php $laba_kotor = $tot_pendapatan - $tot_hpp; ?>
                        <div class="laba-kotor-box">
                            <span>Laba Kotor</span>
                            <span><?= formatRupiah($laba_kotor) ?></span>
                        </div>

                        <!-- 4. BEBAN OPERASIONAL -->
                        <div class="group-title">Beban Operasional</div>
                        <?php 
                        $tot_beban = 0;
                        if ($q_beban->num_rows > 0) {
                            while($row = $q_beban->fetch_assoc()) { 
                                $tot_beban += $row['total'];
                        ?>
                            <div class="report-row"><span class="row-text"><?= htmlspecialchars($row['kategori']) ?></span><span class="row-value"><?= formatRupiah($row['total']) ?></span></div>
                        <?php 
                            }
                        } else { echo '<div class="report-row"><span class="row-text">-</span><span class="row-value">Rp 0</span></div>'; } 
                        ?>
                        <div class="subtotal-row"><span>Total Beban</span><span><?= formatRupiah($tot_beban) ?></span></div>

                        <!-- 5. LABA BERSIH -->
                        <?php $laba_bersih = $laba_kotor - $tot_beban; ?>
                        <div class="laba-bersih-box">
                            <div>
                                <span class="label">Laba Bersih</span>
                                <span class="sub">Net Profit</span>
                            </div>
                            <div class="value"><?= formatRupiah($laba_bersih) ?></div>
                        </div>

                        <div class="footer-note">
                            Laporan ini dibuat otomatis berdasarkan transaksi yang tercatat.<br>
                            Seluruh nilai ditampilkan dalam Rupiah (IDR).
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>