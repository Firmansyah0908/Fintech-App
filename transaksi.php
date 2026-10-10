<?php include 'koneksi.php'; 

// ==========================================
// PROSES CRUD (CREATE, UPDATE, DELETE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. TAMBAH TRANSAKSI
    if (isset($_POST['simpan_trx'])) {
        $tgl = $_POST['tanggal'];
        $trx = "TRX-" . rand(100, 999);
        $desc = $_POST['deskripsi'];
        $kat = $_POST['kategori'];
        $tipe = $_POST['tipe'];
        $jml = $_POST['jumlah'];

        $stmt = $conn->prepare("INSERT INTO transaksi (tanggal, trx_id, deskripsi, kategori, tipe, jumlah) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssd", $tgl, $trx, $desc, $kat, $tipe, $jml);
        $stmt->execute();
        $stmt->close();
        header("Location: transaksi.php");
        exit;
    }
    
    // 2. EDIT TRANSAKSI
    if (isset($_POST['edit_trx'])) {
        $id = $_POST['edit_id'];
        $tgl = $_POST['tanggal'];
        $desc = $_POST['deskripsi'];
        $kat = $_POST['kategori'];
        $tipe = $_POST['tipe'];
        $jml = $_POST['jumlah'];

        $stmt = $conn->prepare("UPDATE transaksi SET tanggal=?, deskripsi=?, kategori=?, tipe=?, jumlah=? WHERE id=?");
        $stmt->bind_param("ssssdi", $tgl, $desc, $kat, $tipe, $jml, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: transaksi.php");
        exit;
    }
    
    // 3. HAPUS TRANSAKSI
    if (isset($_POST['hapus_trx'])) {
        $id = $_POST['id_hapus'];
        $stmt = $conn->prepare("DELETE FROM transaksi WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: transaksi.php");
        exit;
    }
}

// ==========================================
// AMBIL DATA & REKAP BULANAN
// ==========================================
$q_latest = $conn->query("SELECT MAX(tanggal) as max_date FROM transaksi");
$row_latest = $q_latest->fetch_assoc();

if ($row_latest['max_date'] != null) {
    $latest_date = $row_latest['max_date'];
} else {
    $latest_date = date('Y-m-d');
}

$active_m = date('m', strtotime($latest_date));
$active_y = date('Y', strtotime($latest_date));

// ==========================================
// FILTER PENCARIAN, TANGGAL & KATEGORI
// ==========================================
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_kategori = isset($_GET['kategori']) ? $conn->real_escape_string($_GET['kategori']) : '';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

// Ambil daftar kategori unik untuk dropdown filter
$q_kategori_list = $conn->query("SELECT DISTINCT kategori FROM transaksi ORDER BY kategori ASC");
$kategori_options = [];
while ($row = $q_kategori_list->fetch_assoc()) {
    $kategori_options[] = $row['kategori'];
}

// Susun kondisi WHERE dinamis
$where_clauses = [];
if (!empty($search_query)) {
    $where_clauses[] = "(trx_id LIKE '%$search_query%' OR deskripsi LIKE '%$search_query%')";
}
if (!empty($filter_kategori)) {
    $where_clauses[] = "kategori = '$filter_kategori'";
}
if (!empty($start_date)) {
    $where_clauses[] = "tanggal >= '$start_date'";
}
if (!empty($end_date)) {
    $where_clauses[] = "tanggal <= '$end_date'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Jalankan query transaksi dengan filter
$q_transaksi = $conn->query("SELECT * FROM transaksi $where_sql ORDER BY tanggal DESC");
$total_trx_tampil = $q_transaksi->num_rows;

// Rekap angka hanya menghitung data yang tampil di tabel (Ter-filter)
$q_total_filtered = $conn->query("
    SELECT 
        SUM(CASE WHEN tipe='Pemasukan' THEN jumlah ELSE 0 END) as total_masuk,
        SUM(CASE WHEN tipe='Pengeluaran' THEN jumlah ELSE 0 END) as total_keluar
    FROM transaksi 
    $where_sql
");
$data_total = $q_total_filtered->fetch_assoc();

$total_masuk_tampil = $data_total['total_masuk'] != null ? $data_total['total_masuk'] : 0;
$total_keluar_tampil = $data_total['total_keluar'] != null ? $data_total['total_keluar'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi - uangku.</title>
    <!-- Cache busting -->
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <style>
        .date-range-container { display: flex; align-items: center; gap: 8px; width: 100%; }
        .date-range-container input[type="date"] {
            border: none; background: transparent; color: var(--text-main); font-family: 'Inter', sans-serif;
            font-size: 13px; width: 100%; padding: 0; outline: none; cursor: pointer;
        }
        .date-input-wrapper { position: relative; flex: 1; display: flex; align-items: center; }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <?php include 'header.php'; ?>
            <div class="content-body">
                <div class="breadcrumb">Workspace / Transaksi</div>
                <div class="page-header" style="margin-bottom: 24px;">
                    <div>
                        <h1>Transaksi</h1>
                        <p class="text-muted">Kelola dan pantau setiap pemasukan serta pengeluaran bisnis Anda.</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('modalTrx').style.display='flex'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:middle;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Transaction
                    </button>
                </div>

                <div class="trx-summary" style="margin-bottom:24px; display:flex; gap:24px; align-items: center; font-size: 14px;">
                    <div><span class="dot bg-green"></span> Total pemasukan <strong style="color:var(--text-main);">Rp <?= number_format($total_masuk_tampil, 0, ',', '.') ?></strong></div>
                    <div><span class="dot bg-red"></span> Total pengeluaran <strong style="color:var(--text-main);">Rp <?= number_format($total_keluar_tampil, 0, ',', '.') ?></strong></div>
                </div>

                <div class="card p-0">
                    <form method="GET" action="transaksi.php" id="filterForm">
                        <div class="table-toolbar" style="border-bottom: 1px solid var(--border);">
                            <!-- Search -->
                            <div class="toolbar-group flex-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" name="search" placeholder="Cari transaksi atau ID..." class="input-search" value="<?= htmlspecialchars($search_query) ?>">
                            </div>
                            
                            <!-- Date Range -->
                            <div class="toolbar-group flex-1" style="padding: 10px 12px 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                                <div class="date-range-container">
                                    <div class="date-input-wrapper">
                                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" onchange="document.getElementById('filterForm').submit();" title="Dari Tanggal">
                                    </div>
                                    <span class="text-muted">—</span>
                                    <div class="date-input-wrapper">
                                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" onchange="document.getElementById('filterForm').submit();" title="Sampai Tanggal">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Category Select (Filter Toolbar) -->
                            <div class="toolbar-group flex-1">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                <select name="kategori" class="input-search" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">Semua kategori</option>
                                    <?php foreach ($kategori_options as $kat) { 
                                        $selected = ($kat === $filter_kategori) ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($kat) ?>" <?= $selected ?>><?= htmlspecialchars($kat) ?></option>
                                    <?php } ?>
                                </select>
                                <svg class="chevron-down" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </div>
                        </div>
                        <input type="submit" style="display: none;" />
                    </form>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 13%;">Date</th>
                                <th style="width: 15%;">Transaction ID</th>
                                <th style="width: 27%;">Description</th>
                                <th style="width: 17%;">Category</th>
                                <th style="width: 10%;">Type</th>
                                <th style="text-align:right; width: 12%;">Amount</th>
                                <th style="text-align:center; width: 6%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($total_trx_tampil > 0) {
                                while($row = $q_transaksi->fetch_assoc()) {
                                    $dateObj = date_create($row['tanggal']);
                                    $dateStr = date_format($dateObj, "d M Y");
                                    
                                    if ($row['tipe'] == 'Pemasukan') {
                                        $amountClass = 'text-green';
                                        $amountSign  = '+ ';
                                        $badgeClass  = 'badge-green';
                                        $arrow       = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px; vertical-align:text-bottom;"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>';
                                    } else {
                                        $amountClass = 'text-red';
                                        $amountSign  = '- ';
                                        $badgeClass  = 'badge-red';
                                        $arrow       = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px; vertical-align:text-bottom;"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>';
                                    }
                            ?>
                            <tr>
                                <td><span class="text-muted"><?= $dateStr ?></span></td>
                                <td><span class="text-muted"><?= $row['trx_id'] ?></span></td>
                                <td><strong style="color:var(--text-main); font-weight: 600;"><?= htmlspecialchars($row['deskripsi']) ?></strong></td>
                                <td style="white-space: nowrap;"><span class="text-muted"><?= htmlspecialchars($row['kategori']) ?></span></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= $arrow ?> <?= $row['tipe'] ?></span></td>
                                <td style="text-align:right"><strong class="<?= $amountClass ?>"><?= $amountSign ?>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></strong></td>
                                <td style="text-align:center">
                                    <button type="button" class="btn-icon" title="Edit" onclick="openEditModal('<?= $row['id'] ?>', '<?= $row['tanggal'] ?>', '<?= htmlspecialchars($row['deskripsi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['kategori'], ENT_QUOTES) ?>', '<?= $row['tipe'] ?>', '<?= $row['jumlah'] ?>')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                        <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                        <button type="submit" name="hapus_trx" class="btn-icon text-red" title="Hapus">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; padding: 30px;' class='text-muted'>Tidak ada transaksi ditemukan pada filter tersebut.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <span class="text-muted">Menampilkan <?= $total_trx_tampil ?> transaksi</span>
                        <div class="pagination"><button class="active">1</button></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Tambah Transaksi -->
    <div id="modalTrx" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Transaksi</h2>
                <span class="close-btn" onclick="document.getElementById('modalTrx').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="transaksi.php">
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" required class="form-control"></div>
                <div class="form-group"><label>Deskripsi</label><input type="text" name="deskripsi" required class="form-control"></div>
                
                <!-- Kategori yang Dikunci untuk Laporan Laba Rugi -->
                <div class="form-group">
                    <label>Kategori (Pilih sesuai Laporan)</label>
                    <select name="kategori" required class="form-control">
                        <optgroup label="Pendapatan">
                            <option value="Penjualan Offline">Penjualan Offline</option>
                            <option value="Penjualan Online">Penjualan Online</option>
                            <option value="Pendapatan Lain">Pendapatan Lain</option>
                        </optgroup>
                        <optgroup label="Harga Pokok Penjualan (HPP)">
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Kemasan">Kemasan</option>
                        </optgroup>
                        <optgroup label="Beban Operasional">
                            <option value="Gaji">Gaji</option>
                            <option value="Sewa & Listrik">Sewa & Listrik</option>
                            <option value="Pemasaran">Pemasaran</option>
                            <option value="Beban Lainnya">Beban Lainnya</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group"><label>Tipe</label>
                    <select name="tipe" class="form-control">
                        <option value="Pemasukan">Pemasukan</option>
                        <option value="Pengeluaran">Pengeluaran</option>
                    </select>
                </div>
                <div class="form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" required class="form-control"></div>
                <button type="submit" name="simpan_trx" class="btn-primary w-100">Simpan Transaksi</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Transaksi -->
    <div id="modalEditTrx" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Transaksi</h2>
                <span class="close-btn" onclick="document.getElementById('modalEditTrx').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="transaksi.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="edit_tanggal" required class="form-control"></div>
                <div class="form-group"><label>Deskripsi</label><input type="text" name="deskripsi" id="edit_deskripsi" required class="form-control"></div>
                
                <!-- Kategori yang Dikunci untuk Laporan Laba Rugi -->
                <div class="form-group">
                    <label>Kategori (Pilih sesuai Laporan)</label>
                    <select name="kategori" id="edit_kategori" required class="form-control">
                        <optgroup label="Pendapatan">
                            <option value="Penjualan Offline">Penjualan Offline</option>
                            <option value="Penjualan Online">Penjualan Online</option>
                            <option value="Pendapatan Lain">Pendapatan Lain</option>
                        </optgroup>
                        <optgroup label="Harga Pokok Penjualan (HPP)">
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Kemasan">Kemasan</option>
                        </optgroup>
                        <optgroup label="Beban Operasional">
                            <option value="Gaji">Gaji</option>
                            <option value="Sewa & Listrik">Sewa & Listrik</option>
                            <option value="Pemasaran">Pemasaran</option>
                            <option value="Beban Lainnya">Beban Lainnya</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group"><label>Tipe</label>
                    <select name="tipe" id="edit_tipe" class="form-control">
                        <option value="Pemasukan">Pemasukan</option>
                        <option value="Pengeluaran">Pengeluaran</option>
                    </select>
                </div>
                <div class="form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" id="edit_jumlah" required class="form-control"></div>
                <button type="submit" name="edit_trx" class="btn-primary w-100">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, tanggal, deskripsi, kategori, tipe, jumlah) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_tanggal').value = tanggal;
            document.getElementById('edit_deskripsi').value = deskripsi;
            
            // Validasi: Jika kategori dari database lawas tidak ada di select list baru, 
            // biarkan user memilih ulang. Jika ada, set otomatis.
            let selectKat = document.getElementById('edit_kategori');
            let optionExists = Array.from(selectKat.options).some(opt => opt.value === kategori);
            if (optionExists) {
                selectKat.value = kategori;
            } else {
                selectKat.selectedIndex = 0; // Reset jika tidak match
            }

            document.getElementById('edit_tipe').value = tipe;
            document.getElementById('edit_jumlah').value = jumlah;
            document.getElementById('modalEditTrx').style.display = 'flex';
        }

        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    document.getElementById("filterForm").submit();
                }
            });
        }
    </script>
</body>
</html>