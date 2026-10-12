<?php include 'koneksi.php'; 

// 1. FILTER BULAN AKTIF (DROPDOWN ATAS)
// Ambil semua kombinasi Bulan & Tahun unik dari transaksi
$q_months = $conn->query("SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as value, DATE_FORMAT(tanggal, '%M %Y') as label FROM transaksi ORDER BY tanggal DESC");
$available_months = [];
while ($row = $q_months->fetch_assoc()) {
    $available_months[] = $row;
}

$filter_bulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : '';
if ($filter_bulan == '' && count($available_months) > 0) {
    $filter_bulan = $available_months[0]['value']; 
}

if ($filter_bulan != '') {
    $active_y = substr($filter_bulan, 0, 4);
    $active_m = substr($filter_bulan, 5, 2);
} else {
    $active_y = date('Y');
    $active_m = date('m');
}

// 2. SUMMARY CARDS (Dinamis & Perbandingan Bulan Lalu)
// A. Hitung variabel bulan sebelumnya untuk mencari Tren
$prev_date = date('Y-m-d', strtotime($active_y . '-' . $active_m . '-01 -1 month'));
$prev_y = date('Y', strtotime($prev_date));
$prev_m = date('m', strtotime($prev_date));

// B. Cari tanggal terakhir di bulan aktif (untuk melimit Total Saldo)
$last_day_active = date('Y-m-t', strtotime($active_y . '-' . $active_m . '-01'));

// C. Query Kompleks Native (Menghitung bulan ini dan bulan lalu sekaligus)
$q_summary = $conn->query("
    SELECT 
        -- Total Saldo: Semua uang masuk & keluar HINGGA akhir bulan yang difilter
        SUM(CASE WHEN tanggal <= '$last_day_active' THEN 
            (CASE WHEN tipe = 'Pemasukan' THEN jumlah ELSE -jumlah END) 
        ELSE 0 END) as total_saldo,
        
        -- Bulan Aktif
        SUM(CASE WHEN tipe = 'Pemasukan' AND MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y' THEN jumlah ELSE 0 END) as pem_bulan_ini,
        SUM(CASE WHEN tipe = 'Pengeluaran' AND MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y' THEN jumlah ELSE 0 END) as peng_bulan_ini,
        
        -- Bulan Sebelumnya (Untuk Hitung Persentase Tren)
        SUM(CASE WHEN tipe = 'Pemasukan' AND MONTH(tanggal) = '$prev_m' AND YEAR(tanggal) = '$prev_y' THEN jumlah ELSE 0 END) as pem_bulan_lalu,
        SUM(CASE WHEN tipe = 'Pengeluaran' AND MONTH(tanggal) = '$prev_m' AND YEAR(tanggal) = '$prev_y' THEN jumlah ELSE 0 END) as peng_bulan_lalu
    FROM transaksi
");
$summary = $q_summary->fetch_assoc();

// Pengamanan nilai Null
$total_saldo = $summary['total_saldo'] != null ? $summary['total_saldo'] : 0;

$pem_bulan_ini = $summary['pem_bulan_ini'] != null ? $summary['pem_bulan_ini'] : 0;
$peng_bulan_ini = $summary['peng_bulan_ini'] != null ? $summary['peng_bulan_ini'] : 0;
$laba_bulan_ini = $pem_bulan_ini - $peng_bulan_ini;

$pem_bulan_lalu = $summary['pem_bulan_lalu'] != null ? $summary['pem_bulan_lalu'] : 0;
$peng_bulan_lalu = $summary['peng_bulan_lalu'] != null ? $summary['peng_bulan_lalu'] : 0;
$laba_bulan_lalu = $pem_bulan_lalu - $peng_bulan_lalu;

// D. Helper Fungsi PHP untuk Merender HTML Persentase (Trend Badge)
function renderTrendBadge($nilai_sekarang, $nilai_lalu, $is_pengeluaran = false) {
    if ($nilai_lalu == 0) {
        $persentase = $nilai_sekarang > 0 ? 100 : 0;
    } else {
        $persentase = (($nilai_sekarang - $nilai_lalu) / abs($nilai_lalu)) * 100;
    }
    
    $is_naik = $persentase >= 0;
    $format_persen = number_format(abs($persentase), 1) . '%';
    $sign = $is_naik ? '+' : '-';
    
    if ($is_pengeluaran) {
        $css_class = $is_naik ? 'negative' : 'positive';
    } else {
        $css_class = $is_naik ? 'positive' : 'negative';
    }
    
    $svg = $is_naik 
        ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>'
        : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="7" x2="17" y2="17"></line><polyline points="17 7 17 17 7 17"></polyline></svg>';
        
    return "<span class=\"badge-trend {$css_class}\">{$svg} {$sign}{$format_persen}</span>";
}

// 3. LOGIKA CHART (BATANG & GARIS)
$rentang_waktu = isset($_GET['rentang_waktu']) ? $_GET['rentang_waktu'] : 'mingguan';

$sql_group = "";
$sql_select_label = "";
$limit_chart = 6;
$filter_grafik = "";

if ($rentang_waktu == 'bulanan') {
    $sql_group = "DATE_FORMAT(tanggal, '%Y-%m')";
    $sql_select_label = "DATE_FORMAT(tanggal, '%b') as label";
    $limit_chart = 6;
    $filter_grafik = "WHERE DATE_FORMAT(tanggal, '%Y-%m') <= '$filter_bulan'";
} else if ($rentang_waktu == 'mingguan') {
    $sql_group = "YEARWEEK(tanggal, 1)";
    $sql_select_label = "CONCAT('Mg ', WEEK(tanggal, 1)) as label"; 
    $limit_chart = 5;
    $filter_grafik = "WHERE MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y'";
} else if ($rentang_waktu == 'harian') {
    $sql_group = "DATE(tanggal)";
    $sql_select_label = "DATE_FORMAT(tanggal, '%d %b') as label"; 
    $limit_chart = 31;
    $filter_grafik = "WHERE MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y'";
}

$q_trend = $conn->query("
    SELECT 
        $sql_select_label,
        $sql_group as sort_key,
        MIN(tanggal) as tanggal_mulai_titik,
        SUM(CASE WHEN tipe = 'Pemasukan' THEN jumlah ELSE 0 END) as total_pem,
        SUM(CASE WHEN tipe = 'Pengeluaran' THEN jumlah ELSE 0 END) as total_peng
    FROM transaksi 
    $filter_grafik
    GROUP BY $sql_group, label
    ORDER BY sort_key DESC LIMIT $limit_chart
");

$data_grafik_temp = [];
while($row = $q_trend->fetch_assoc()) {
    $data_grafik_temp[] = $row;
}

$data_grafik_render = array_reverse($data_grafik_temp);

$arr_label = []; 
$arr_pem = []; 
$arr_peng = []; 
$arr_saldo = [];
$max_bar_val = 0;
$max_line_val = 0;

$saldo_berjalan = 0;
if (count($data_grafik_render) > 0) {
    $tanggal_awal_grafik = $data_grafik_render[0]['tanggal_mulai_titik'];
    $q_saldo_awal = $conn->query("
        SELECT SUM(CASE WHEN tipe = 'Pemasukan' THEN jumlah ELSE -jumlah END) as saldo_awal 
        FROM transaksi 
        WHERE tanggal < '$tanggal_awal_grafik'
    ");
    $row_saldo_awal = $q_saldo_awal->fetch_assoc();
    $saldo_berjalan = $row_saldo_awal['saldo_awal'] != null ? $row_saldo_awal['saldo_awal'] : 0;
}

foreach ($data_grafik_render as $row) {
    $arr_label[] = $row['label'];
    
    $pem_asli = $row['total_pem'];
    $peng_asli = $row['total_peng'];
    
    $pem_juta = $pem_asli / 1000000;
    $peng_juta = $peng_asli / 1000000;
    
    $arr_pem[] = $pem_juta;
    $arr_peng[] = $peng_juta;
    
    if ($pem_juta > $max_bar_val) { $max_bar_val = $pem_juta; }
    if ($peng_juta > $max_bar_val) { $max_bar_val = $peng_juta; }
    
    $saldo_berjalan = $saldo_berjalan + $pem_asli - $peng_asli;
    
    $saldo_juta = $saldo_berjalan / 1000000;
    $arr_saldo[] = $saldo_juta; 
    
    if ($saldo_juta > $max_line_val) { $max_line_val = $saldo_juta; }
}

if ($max_bar_val <= 0) { $max_bar_val = 1; }
$max_bar_val = ceil($max_bar_val * 1.2); 

$js_max_val = 1;
if ($max_line_val > 0) {
    $js_max_val = ceil($max_line_val * 1.2); 
} else {
    if ($max_bar_val > 1) { $js_max_val = $max_bar_val; }
}

// 4. DONUT CHART (Alokasi Pengeluaran)
$q_donut = $conn->query("SELECT kategori, SUM(jumlah) as total FROM transaksi WHERE tipe = 'Pengeluaran' AND MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y' GROUP BY kategori");
$tot_pengeluaran_chart = 0;
$donut_data = [];
$colors = ['#10b981', '#6ee7b7', '#fcd34d', '#f3f4f6', '#ef4444']; 
$i = 0;

while($row = $q_donut->fetch_assoc()) {
    $donut_data[] = ['kategori' => $row['kategori'], 'total' => $row['total'], 'color' => $colors[$i % count($colors)]];
    $tot_pengeluaran_chart += $row['total'];
    $i++;
}

$conic_gradient = ""; $current_pct = 0; $legend_html = "";
if ($tot_pengeluaran_chart > 0) {
    foreach($donut_data as $d) {
        $pct = ($d['total'] / $tot_pengeluaran_chart) * 100;
        $next_pct = $current_pct + $pct;
        $conic_gradient .= $d['color'] . " {$current_pct}% {$next_pct}%, ";
        $legend_html .= '<li><span class="dot" style="background:'.$d['color'].';"></span> '.$d['kategori'].' <span class="float-right font-bold">'.round($pct).'%</span></li>';
        $current_pct = $next_pct;
    }
    $conic_gradient = rtrim($conic_gradient, ", ");
} else {
    $conic_gradient = "#e5e7eb 0% 100%";
    $legend_html = '<li>Belum ada pengeluaran di bulan ini</li>';
}

// 5. PEMASUKAN TERBANYAK & 6. PENGELUARAN TERBANYAK
$q_top_masuk = $conn->query("SELECT * FROM transaksi WHERE tipe='Pemasukan' AND MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y' ORDER BY jumlah DESC LIMIT 2");
$q_top_keluar = $conn->query("SELECT * FROM transaksi WHERE tipe='Pengeluaran' AND MONTH(tanggal) = '$active_m' AND YEAR(tanggal) = '$active_y' ORDER BY jumlah DESC LIMIT 2");
$jml_keluar_tampil = $q_top_keluar->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - uangku.</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css"rel="stylesheet"
/>
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
                    
                    <!-- FORM FILTER BULAN CUSTOM -->
                    <form method="GET" action="index.php" id="formBulan">
                        <input type="hidden" name="rentang_waktu" value="<?= $rentang_waktu ?>">
                        
                        <div class="custom-select-wrapper">
                            <svg class="calendar-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            
                            <select name="filter_bulan" class="custom-select" onchange="document.getElementById('formBulan').submit();">
                                <?php foreach($available_months as $m) { 
                                    $selected = ($m['value'] == $filter_bulan) ? 'selected' : '';
                                ?>
                                    <option value="<?= $m['value'] ?>" <?= $selected ?>><?= $m['label'] ?></option>
                                <?php } ?>
                            </select>
                            
                            <svg class="chevron-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </form>
                </div>
                
                <p class="subtitle">Selamat datang kembali, Aditya! Ini ringkasan keuangan bisnis Anda.</p>

                <!-- Summary Cards Dinamis -->
                <div class="dashboard-cards">
                    <div class="card summary-card">
                        <div class="card-top">
                            <span>Total Saldo</span>
                            <div class="icon-box bg-light-green text-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                        </div>
                        <h2><?= formatRupiah($total_saldo) ?></h2>
                        <div class="card-bottom"><span class="text-muted">Sampai akhir <?= date('M Y', strtotime($last_day_active)) ?></span></div>
                    </div>
                    
                    <div class="card summary-card">
                        <div class="card-top">
                            <span>Pemasukan</span>
                            <div class="icon-box bg-light-green text-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="5" x2="19" y2="19"></line><polyline points="19 9 19 19 9 19"></polyline></svg></div>
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
                            <div class="icon-box bg-light-orange text-orange"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="19" x2="19" y2="5"></line><polyline points="9 5 19 5 19 15"></polyline></svg></div>
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
                            <div class="icon-box bg-light-green text-green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></div>
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
                                    <select name="rentang_waktu" class="custom-select" style="padding: 0; font-size: 13px;" onchange="document.getElementById('formRentang').submit();">
                                        <option value="mingguan" <?= $rentang_waktu == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                                        <option value="harian" <?= $rentang_waktu == 'harian' ? 'selected' : '' ?>>Harian</option>
                                    </select>
                                    <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="right: 8px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </div>
                            </form>
                        </div>
                        
                        <div class="chart-legend">
                            <span class="dot bg-green"></span> Pemasukan <span class="dot bg-light-green-alt"></span> Pengeluaran <span class="text-muted float-right">Dalam juta rupiah</span>
                        </div>
                        
                        <div class="bar-chart-container">
                            <div class="y-axis">
                                <span data-val="<?= $max_bar_val ?> jt"></span>
                                <span data-val="<?= $max_bar_val/2 ?> jt"></span>
                                <span data-val="0 jt"></span>
                            </div>
                            <div class="bar-area">
                                <?php
                                if (count($arr_label) == 0) {
                                    echo "<div class='text-muted' style='width:100%; text-align:center; padding-top:50px;'>Tidak ada data transaksi di rentang ini</div>";
                                }
                                for($i = 0; $i < count($arr_label); $i++) {
                                    $h_pem = ($arr_pem[$i] / $max_bar_val) * 100;
                                    $h_peng = ($arr_peng[$i] / $max_bar_val) * 100;
                                    echo "<div class='bar-group'>
                                            <div class='bar-wrapper'>
                                                <div class='bar bg-light-green-alt' style='height: {$h_peng}%;'></div>
                                                <div class='bar bg-green' style='height: {$h_pem}%;'></div>
                                            </div>
                                            <span class='x-label'>{$arr_label[$i]}</span>
                                          </div>";
                                }
                                ?>
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
                        <p class="text-muted">Alokasi pengeluaran bulan ini (<?= date('F Y', strtotime($filter_bulan."-01")) ?>)</p>
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
                        <!-- Card Pemasukan Terbanyak -->
                        <div class="card card-list-wrapper">
                            <div class="card-header-flex" style="margin-bottom:15px;">
                                <h3>Pemasukan Terbanyak</h3><a href="transaksi.php" class="text-green" style="text-decoration:none; font-size:14px;">Lihat semua &rarr;</a>
                            </div>
                            <?php 
                            if ($q_top_masuk->num_rows == 0) {
                                echo "<p class='text-muted'>Belum ada transaksi pemasukan bulan ini.</p>";
                            }
                            while($p = $q_top_masuk->fetch_assoc()) { 
                                $dateStr = date('d M Y', strtotime($p['tanggal']));
                            ?>
                            <div class="list-item">
                                <div class="item-icon bg-light-yellow">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-dark"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                </div>
                                <div class="item-info">
                                    <strong><?= htmlspecialchars($p['deskripsi']) ?></strong>
                                    <span class="text-muted"><?= htmlspecialchars($p['kategori']) ?> &middot; <?= $dateStr ?></span>
                                </div>
                                <div class="item-value"><strong class="text-main">Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></strong></div>
                            </div>
                            <?php } ?>
                        </div>

                        <!-- Card Pengeluaran Terbanyak -->
                        <div class="card card-list-wrapper" style="margin-top: 24px;">
                            <div class="card-header-flex" style="margin-bottom:15px;">
                                <h3>Pengeluaran Terbanyak</h3>
                                <?php if($jml_keluar_tampil > 0): ?>
                                <span class="badge bg-red-light text-red font-bold"><?= $jml_keluar_tampil ?> transaksi</span>
                                <?php endif; ?>
                            </div>
                            <?php 
                            if ($jml_keluar_tampil == 0) {
                                echo "<p class='text-muted'>Belum ada transaksi pengeluaran bulan ini.</p>";
                            }
                            while($t = $q_top_keluar->fetch_assoc()) { 
                                $dateStr = date('d M Y', strtotime($t['tanggal']));
                            ?>
                            <div class="list-item">
                                <div class="item-icon bg-light-gray">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><line x1="5" y1="19" x2="19" y2="5"></line><polyline points="9 5 19 5 19 15"></polyline></svg>
                                </div>
                                <div class="item-info">
                                    <strong><?= htmlspecialchars($t['deskripsi']) ?></strong>
                                    <span class="text-muted"><?= htmlspecialchars($t['kategori']) ?> &middot; <?= $dateStr ?></span>
                                </div>
                                <div class="item-value"><strong class="text-main">Rp <?= number_format($t['jumlah'], 0, ',', '.') ?></strong></div>
                            </div>
                            <?php } ?>
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
            maxVal: <?= $js_max_val ?>
        };
    </script>
    <script src="script.js"></script>
</body>
</html>