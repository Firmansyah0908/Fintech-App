<?php 
include 'koneksi.php'; 

// 1. Ambil daftar bulan dari transaksi
$q_months = mysqli_query($conn, "SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as val, DATE_FORMAT(tanggal, '%M %Y') as label FROM transaksi ORDER BY tanggal DESC");
$available_months = [];
while ($row = mysqli_fetch_assoc($q_months)) {
    $available_months[] = $row;
}

$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : (count($available_months) > 0 ? $available_months[0]['val'] : date('Y-m'));
$active_y = substr($filter_bulan, 0, 4);
$active_m = substr($filter_bulan, 5, 2);

// Bulan lalu untuk perbandingan persentase
$prev_ts = strtotime("$filter_bulan-01 -1 month");
$prev_y = date('Y', $prev_ts);
$prev_m = date('m', $prev_ts);
$last_day_active = date('Y-m-t', strtotime("$filter_bulan-01"));

// Fungsi sederhana untuk hitung total nominal
function hitungTotal($conn, $tipe, $bulan, $tahun) {
    $q = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE tipe='$tipe' AND MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'");
    $r = mysqli_fetch_assoc($q);
    return $r['total'] ? (float)$r['total'] : 0;
}

// Hitung Ringkasan Bulan Ini & Bulan Lalu
$pem_bulan_ini  = hitungTotal($conn, 'Pemasukan', $active_m, $active_y);
$peng_bulan_ini = hitungTotal($conn, 'Pengeluaran', $active_m, $active_y);
$laba_bulan_ini = $pem_bulan_ini - $peng_bulan_ini;

$pem_bulan_lalu  = hitungTotal($conn, 'Pemasukan', $prev_m, $prev_y);
$peng_bulan_lalu = hitungTotal($conn, 'Pengeluaran', $prev_m, $prev_y);
$laba_bulan_lalu = $pem_bulan_lalu - $peng_bulan_lalu;

// Hitung Total Saldo sampai akhir bulan terpilih
$q_masuk_all  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM transaksi WHERE tipe='Pemasukan' AND tanggal <= '$last_day_active'"));
$q_keluar_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as t FROM transaksi WHERE tipe='Pengeluaran' AND tanggal <= '$last_day_active'"));
$total_saldo  = ($q_masuk_all['t'] ?? 0) - ($q_keluar_all['t'] ?? 0);

// Fungsi badge persentase sederhana
function renderTrendBadge($sekarang, $lalu, $is_pengeluaran = false) {
    if ($lalu == 0) {
        $persen = $sekarang > 0 ? 100 : 0;
    } else {
        $persen = (($sekarang - $lalu) / abs($lalu)) * 100;
    }
    $naik = $persen >= 0;
    $sign = $naik ? '+' : '-';
    $class = ($is_pengeluaran ? !$naik : $naik) ? 'positive' : 'negative';
    return "<span class='badge-trend $class'>$sign" . number_format(abs($persen), 1) . "%</span>";
}

// 2. Data Grafik Batang & Garis
$rentang_waktu = isset($_GET['rentang_waktu']) ? $_GET['rentang_waktu'] : 'mingguan';
if ($rentang_waktu == 'harian') {
    $q_grafik = mysqli_query($conn, "
        SELECT DATE_FORMAT(tanggal, '%d %b') as label,
               SUM(IF(tipe='Pemasukan', jumlah, 0)) as pem,
               SUM(IF(tipe='Pengeluaran', jumlah, 0)) as peng
        FROM transaksi
        WHERE MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y'
        GROUP BY DATE(tanggal) ORDER BY tanggal ASC
    ");
} else {
    $q_grafik = mysqli_query($conn, "
        SELECT CONCAT('Mg ', WEEK(tanggal, 1)) as label,
               SUM(IF(tipe='Pemasukan', jumlah, 0)) as pem,
               SUM(IF(tipe='Pengeluaran', jumlah, 0)) as peng
        FROM transaksi
        WHERE MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y'
        GROUP BY YEARWEEK(tanggal, 1) ORDER BY MIN(tanggal) ASC
    ");
}

$arr_label = []; $arr_pem = []; $arr_peng = []; $arr_saldo = [];
$max_bar_val = 1; $max_line_val = 1; $saldo_jalan = 0;

while ($r = mysqli_fetch_assoc($q_grafik)) {
    $arr_label[] = $r['label'];
    $pem_jt  = $r['pem'] / 1000000;
    $peng_jt = $r['peng'] / 1000000;
    $arr_pem[]  = $pem_jt;
    $arr_peng[] = $peng_jt;

    $saldo_jalan += ($r['pem'] - $r['peng']);
    $saldo_jt = $saldo_jalan / 1000000;
    $arr_saldo[] = $saldo_jt;

    if ($pem_jt > $max_bar_val) $max_bar_val = ceil($pem_jt * 1.2);
    if ($peng_jt > $max_bar_val) $max_bar_val = ceil($peng_jt * 1.2);
    if ($saldo_jt > $max_line_val) $max_line_val = ceil($saldo_jt * 1.2);
}

// 3. Donut Chart Pengeluaran
$q_donut = mysqli_query($conn, "SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe='Pengeluaran' AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' GROUP BY kategori");
$donut_data = []; $tot_pengeluaran_chart = 0;
$colors = ['#10b981', '#6ee7b7', '#fcd34d', '#f3f4f6', '#ef4444'];
$i = 0;
while ($r = mysqli_fetch_assoc($q_donut)) {
    $donut_data[] = ['kategori' => $r['kategori'], 'total' => $r['total'], 'color' => $colors[$i % 5]];
    $tot_pengeluaran_chart += $r['total'];
    $i++;
}

$conic_gradient = "#e5e7eb 0% 100%";
$legend_html = "<li>Belum ada pengeluaran di bulan ini</li>";
if ($tot_pengeluaran_chart > 0) {
    $conic_gradient = ""; $legend_html = ""; $cur = 0;
    foreach ($donut_data as $d) {
        $pct = ($d['total'] / $tot_pengeluaran_chart) * 100;
        $next = $cur + $pct;
        $conic_gradient .= "{$d['color']} {$cur}% {$next}%, ";
        $legend_html .= "<li><span class='dot' style='background:{$d['color']};'></span> {$d['kategori']} <span class='float-right font-bold'>" . round($pct) . "%</span></li>";
        $cur = $next;
    }
    $conic_gradient = rtrim($conic_gradient, ", ");
}

// 4. Transaksi Terbanyak
$q_top_masuk  = mysqli_query($conn, "SELECT * FROM transaksi WHERE tipe='Pemasukan' AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' ORDER BY jumlah DESC LIMIT 2");
$q_top_keluar = mysqli_query($conn, "SELECT * FROM transaksi WHERE tipe='Pengeluaran' AND MONTH(tanggal)='$active_m' AND YEAR(tanggal)='$active_y' ORDER BY jumlah DESC LIMIT 2");
$jml_keluar_tampil = mysqli_num_rows($q_top_keluar);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - uangku.</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <?php include 'header.php'; ?>
            <div class="content-body">
                <div class="breadcrumb">Workspace / Dashboard</div>
                
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <form method="GET" action="index.php" id="formBulan">
                        <input type="hidden" name="rentang_waktu" value="<?= $rentang_waktu ?>">
                        <div class="custom-select-wrapper">
                            <svg class="calendar-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <select name="filter_bulan" class="custom-select" onchange="document.getElementById('formBulan').submit();">
                                <?php foreach ($available_months as $m): ?>
                                    <option value="<?= $m['val'] ?>" <?= $m['val'] == $filter_bulan ? 'selected' : '' ?>><?= $m['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg class="chevron-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </form>
                </div>
                
                <p class="subtitle">Selamat datang kembali, Aditya! Ini ringkasan keuangan bisnis Anda.</p>

                <div class="dashboard-cards">
                    <div class="card summary-card">
                        <div class="card-top">
                            <span>Total Saldo</span>
                            <div class="icon-box bg-light-green text-green"><i class="ri-wallet-3-line"></i></div>
                        </div>
                        <h2><?= formatRupiah($total_saldo) ?></h2>
                        <div class="card-bottom"><span class="text-muted">Sampai akhir <?= date('M Y', strtotime($last_day_active)) ?></span></div>
                    </div>
                    
                    <div class="card summary-card">
                        <div class="card-top">
                            <span>Pemasukan</span>
                            <div class="icon-box bg-light-green text-green"><i class="ri-arrow-right-down-line"></i></div>
                        </div>
                        <h2><?= formatRupiah($pem_bulan_ini) ?></h2>
                        <div class="card-bottom">
                            <?= renderTrendBadge($pem_bulan_ini, $pem_bulan_lalu, false) ?> 
                            <span class="text-muted">vs. bulan lalu</span>
                        </div>
                    </div>
                    
                    <div class="card summary-card">
                        <div class="card-top">
                            <span>Pengeluaran</span>
                            <div class="icon-box bg-light-orange text-orange"><i class="ri-arrow-right-up-line"></i></div>
                        </div>
                        <h2><?= formatRupiah($peng_bulan_ini) ?></h2>
                        <div class="card-bottom">
                            <?= renderTrendBadge($peng_bulan_ini, $peng_bulan_lalu, true) ?> 
                            <span class="text-muted">vs. bulan lalu</span>
                        </div>
                    </div>
                    
                    <div class="card summary-card">
                        <div class="card-top">
                            <span>Laba Bersih</span>
                            <div class="icon-box bg-light-green text-green"><i class="ri-line-chart-line"></i></div>
                        </div>
                        <h2><?= formatRupiah($laba_bulan_ini) ?></h2>
                        <div class="card-bottom">
                            <?= renderTrendBadge($laba_bulan_ini, $laba_bulan_lalu, false) ?> 
                            <span class="text-muted">vs. bulan lalu</span>
                        </div>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="card chart-card">
                        <div class="card-header-flex">
                            <div><h3>Arus Kas Transaksi</h3><p class="text-muted">Pemasukan dan pengeluaran bisnis Anda</p></div>
                            <form method="GET" action="index.php" id="formRentang">
                                <input type="hidden" name="filter_bulan" value="<?= $filter_bulan ?>">
                                <div class="custom-select-wrapper" style="padding: 4px 32px 4px 12px; height: 32px;">
                                    <select name="rentang_waktu" class="custom-select" style="font-size: 13px;" onchange="document.getElementById('formRentang').submit();">
                                        <option value="mingguan" <?= $rentang_waktu == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                                        <option value="harian" <?= $rentang_waktu == 'harian' ? 'selected' : '' ?>>Harian</option>
                                    </select>
                                    <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" style="right: 8px;"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                            </form>
                        </div>
                        
                        <div class="chart-legend">
                            <span class="dot bg-green"></span> Pemasukan <span class="dot bg-light-green-alt"></span> Pengeluaran <span class="text-muted float-right">Dalam juta rupiah</span>
                        </div>
                        
                        <div class="bar-chart-container">
                            <div class="y-axis">
                                <span data-val="<?= $max_bar_val ?> jt"></span>
                                <span data-val="<?= $max_bar_val / 2 ?> jt"></span>
                                <span data-val="0 jt"></span>
                            </div>
                            <div class="bar-area">
                                <?php if (count($arr_label) == 0): ?>
                                    <div class="text-muted" style="width:100%; text-align:center; padding-top:50px;">Tidak ada data transaksi</div>
                                <?php endif; ?>
                                <?php for ($i = 0; $i < count($arr_label); $i++): 
                                    $h_pem  = ($arr_pem[$i] / $max_bar_val) * 100;
                                    $h_peng = ($arr_peng[$i] / $max_bar_val) * 100;
                                ?>
                                    <div class="bar-group">
                                        <div class="bar-wrapper">
                                            <div class="bar bg-light-green-alt" style="height: <?= $h_peng ?>%;"></div>
                                            <div class="bar bg-green" style="height: <?= $h_pem ?>%;"></div>
                                        </div>
                                        <span class="x-label"><?= $arr_label[$i] ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card chart-card">
                        <div class="card-header-flex">
                            <div><h3>Tren Saldo</h3><p class="text-muted">Pergerakan total uang di rekening</p></div>
                        </div>
                        <div class="saldo-amount"><h2><?= formatRupiah($total_saldo) ?></h2> <span class="text-muted">Total Saldo Saat Ini</span></div>
                        <div class="line-chart-container" id="lineChartContainer"></div>
                    </div>
                </div>

                <div class="bottom-grid">
                    <div class="card">
                        <h3>Rincian Pengeluaran</h3>
                        <p class="text-muted">Alokasi pengeluaran bulan ini (<?= date('F Y', strtotime("$filter_bulan-01")) ?>)</p>
                        <div class="donut-container">
                            <div class="donut-chart" style="background: conic-gradient(<?= $conic_gradient ?>);">
                                <div class="donut-inner">
                                    <span class="text-muted" style="font-size:12px;">Total pengeluaran</span>
                                    <strong style="font-size:16px;"><?= formatRupiah($tot_pengeluaran_chart) ?></strong>
                                </div>
                            </div>
                            <div class="donut-legend"><ul><?= $legend_html ?></ul></div>
                        </div>
                    </div>

                    <div class="right-lists">
                        <div class="card card-list-wrapper">
                            <div class="card-header-flex" style="margin-bottom:15px;">
                                <h3>Pemasukan Terbanyak</h3>
                                <a href="transaksi.php" class="text-green" style="text-decoration:none; font-size:14px;">Lihat semua &rarr;</a>
                            </div>
                            <?php if (mysqli_num_rows($q_top_masuk) == 0): ?>
                                <p class="text-muted">Belum ada transaksi pemasukan bulan ini.</p>
                            <?php endif; ?>
                            <?php while ($p = mysqli_fetch_assoc($q_top_masuk)): ?>
                                <div class="list-item">
                                    <div class="item-icon bg-light-yellow"><i class="ri-line-chart-line text-yellow-dark"></i></div>
                                    <div class="item-info">
                                        <strong><?= htmlspecialchars($p['deskripsi']) ?></strong>
                                        <span class="text-muted"><?= htmlspecialchars($p['kategori']) ?> &middot; <?= date('d M Y', strtotime($p['tanggal'])) ?></span>
                                    </div>
                                    <div class="item-value"><strong class="text-main"><?= formatRupiah($p['jumlah']) ?></strong></div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="card card-list-wrapper" style="margin-top: 24px;">
                            <div class="card-header-flex" style="margin-bottom:15px;">
                                <h3>Pengeluaran Terbanyak</h3>
                                <?php if ($jml_keluar_tampil > 0): ?>
                                    <span class="badge bg-red-light text-red font-bold"><?= $jml_keluar_tampil ?> transaksi</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($jml_keluar_tampil == 0): ?>
                                <p class="text-muted">Belum ada transaksi pengeluaran bulan ini.</p>
                            <?php endif; ?>
                            <?php while ($t = mysqli_fetch_assoc($q_top_keluar)): ?>
                                <div class="list-item">
                                    <div class="item-icon bg-light-gray"><i class="ri-arrow-right-up-line text-muted"></i></div>
                                    <div class="item-info">
                                        <strong><?= htmlspecialchars($t['deskripsi']) ?></strong>
                                        <span class="text-muted"><?= htmlspecialchars($t['kategori']) ?> &middot; <?= date('d M Y', strtotime($t['tanggal'])) ?></span>
                                    </div>
                                    <div class="item-value"><strong class="text-main"><?= formatRupiah($t['jumlah']) ?></strong></div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        window.chartDataFromDB = {
            labels: <?= json_encode($arr_label) ?>,
            data: <?= json_encode($arr_saldo) ?>,
            maxVal: <?= $max_line_val ?>
        };
    </script>
    <script src="script.js"></script>
</body>
</html>