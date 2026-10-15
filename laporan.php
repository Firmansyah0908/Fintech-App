<?php 
include 'koneksi.php'; 

$q_months = mysqli_query($conn, "SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as val, DATE_FORMAT(tanggal, '%M %Y') as label FROM transaksi ORDER BY tanggal DESC");
$available_months = [];
while ($row = mysqli_fetch_assoc($q_months)) {
    $available_months[] = $row;
}

$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : (count($available_months) > 0 ? $available_months[0]['val'] : date('Y-m'));
$active_y = substr($filter_bulan, 0, 4);
$active_m = substr($filter_bulan, 5, 2);
$periode_str = date('F Y', strtotime("$filter_bulan-01"));

$q_pendapatan = mysqli_query($conn, "SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pemasukan' AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");
$q_hpp        = mysqli_query($conn, "SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pengeluaran' AND kategori IN ('Bahan Baku', 'Kemasan') AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");
$q_beban      = mysqli_query($conn, "SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pengeluaran' AND kategori NOT IN ('Bahan Baku', 'Kemasan') AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - uangku.</title>
    <link rel="stylesheet" href="style.css">
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
                                <svg class="calendar-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <select name="filter_bulan" class="custom-select" onchange="this.form.submit()">
                                    <?php foreach ($available_months as $m): ?>
                                        <option value="<?= $m['val'] ?>" <?= $m['val'] == $filter_bulan ? 'selected' : '' ?>><?= $m['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <svg class="chevron-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                        </form>
                        <button class="btn-primary" onclick="window.print()" style="background:#fff; color:var(--text-main); border:1px solid var(--border); font-weight:600;">
                            Export PDF
                        </button>
                    </div>
                </div>

                <div class="tabs"><div class="tab active">Laporan Laba Rugi</div></div>

                <div class="report-wrapper">
                    <div class="laporan-card" style="width: 100%; max-width: 800px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 32px;">
                            <div>
                                <span style="color:var(--primary); font-size:11px; font-weight:700; letter-spacing:1px;">KOPI SENJA</span>
                                <h1 style="font-size:26px; margin: 4px 0;">Laporan Laba Rugi</h1>
                                <span class="text-muted" style="font-size:14px;">Periode: <?= $periode_str ?></span>
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
                        if (mysqli_num_rows($q_pendapatan) > 0) {
                            while ($row = mysqli_fetch_assoc($q_pendapatan)) { 
                                $tot_pendapatan += $row['total'];
                                echo "<div class='report-row'><span class='row-text'>{$row['kategori']}</span><span class='row-value'>" . formatRupiah($row['total']) . "</span></div>";
                            }
                        } else {
                            echo "<div class='report-row'><span class='row-text'>-</span><span class='row-value'>Rp 0</span></div>";
                        }
                        ?>
                        <div class="subtotal-row"><span>Total Pendapatan</span><span><?= formatRupiah($tot_pendapatan) ?></span></div>

                        <!-- 2. HPP -->
                        <div class="group-title" style="margin-top: 32px;">Harga Pokok Penjualan (HPP)</div>
                        <?php 
                        $tot_hpp = 0;
                        if (mysqli_num_rows($q_hpp) > 0) {
                            while ($row = mysqli_fetch_assoc($q_hpp)) { 
                                $tot_hpp += $row['total'];
                                echo "<div class='report-row'><span class='row-text'>{$row['kategori']}</span><span class='row-value'>" . formatRupiah($row['total']) . "</span></div>";
                            }
                        } else {
                            echo "<div class='report-row'><span class='row-text'>-</span><span class='row-value'>Rp 0</span></div>";
                        }
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
                        if (mysqli_num_rows($q_beban) > 0) {
                            while ($row = mysqli_fetch_assoc($q_beban)) { 
                                $tot_beban += $row['total'];
                                echo "<div class='report-row'><span class='row-text'>{$row['kategori']}</span><span class='row-value'>" . formatRupiah($row['total']) . "</span></div>";
                            }
                        } else {
                            echo "<div class='report-row'><span class='row-text'>-</span><span class='row-value'>Rp 0</span></div>";
                        }
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