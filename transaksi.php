<?php 
include 'koneksi.php'; 

// PROSES CRUD SEDERHANA
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['simpan_trx'])) {
        $tgl  = $_POST['tanggal'];
        $trx  = "TRX-" . rand(100, 999);
        $desc = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $kat  = mysqli_real_escape_string($conn, $_POST['kategori']);
        $tipe = $_POST['tipe'];
        $jml  = $_POST['jumlah'];

        mysqli_query($conn, "INSERT INTO transaksi (tanggal, trx_id, deskripsi, kategori, tipe, jumlah) VALUES ('$tgl', '$trx', '$desc', '$kat', '$tipe', '$jml')");
        header("Location: transaksi.php");
        exit;
    }
    
    if (isset($_POST['edit_trx'])) {
        $id   = $_POST['edit_id'];
        $tgl  = $_POST['tanggal'];
        $desc = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $kat  = mysqli_real_escape_string($conn, $_POST['kategori']);
        $tipe = $_POST['tipe'];
        $jml  = $_POST['jumlah'];

        mysqli_query($conn, "UPDATE transaksi SET tanggal='$tgl', deskripsi='$desc', kategori='$kat', tipe='$tipe', jumlah='$jml' WHERE id='$id'");
        header("Location: transaksi.php");
        exit;
    }
    
    if (isset($_POST['hapus_trx'])) {
        $id = $_POST['id_hapus'];
        mysqli_query($conn, "DELETE FROM transaksi WHERE id='$id'");
        header("Location: transaksi.php");
        exit;
    }
}

// FILTER PENCARIAN SEDERHANA
$search     = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kategori   = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "WHERE 1=1";
if ($search != '')     $where .= " AND (trx_id LIKE '%$search%' OR deskripsi LIKE '%$search%')";
if ($kategori != '')   $where .= " AND kategori = '$kategori'";
if ($start_date != '') $where .= " AND tanggal >= '$start_date'";
if ($end_date != '')   $where .= " AND tanggal <= '$end_date'";

$q_kategori_list = mysqli_query($conn, "SELECT DISTINCT kategori FROM transaksi ORDER BY kategori ASC");
$q_transaksi     = mysqli_query($conn, "SELECT * FROM transaksi $where ORDER BY tanggal DESC");
$total_trx       = mysqli_num_rows($q_transaksi);

// Hitung total pemasukan & pengeluaran dari hasil filter
$q_rekap = mysqli_query($conn, "
    SELECT SUM(IF(tipe='Pemasukan', jumlah, 0)) as masuk,
           SUM(IF(tipe='Pengeluaran', jumlah, 0)) as keluar
    FROM transaksi $where
");
$rekap = mysqli_fetch_assoc($q_rekap);
$total_masuk  = $rekap['masuk'] ?? 0;
$total_keluar = $rekap['keluar'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi - uangku.</title>
    <link rel="stylesheet" href="style.css">
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
                    <button class="btn-primary" onclick="document.getElementById('modalTrx').style.display='flex'">+ Add Transaction</button>
                </div>

                <div class="trx-summary" style="margin-bottom:24px; display:flex; gap:24px; align-items: center; font-size: 14px;">
                    <div><span class="dot bg-green"></span> Total pemasukan <strong><?= formatRupiah($total_masuk) ?></strong></div>
                    <div><span class="dot bg-red"></span> Total pengeluaran <strong><?= formatRupiah($total_keluar) ?></strong></div>
                </div>

                <div class="card p-0">
                    <form method="GET" action="transaksi.php" id="filterForm">
                        <div class="table-toolbar">
                            <div class="toolbar-group flex-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" name="search" placeholder="Cari transaksi atau ID..." value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
                            </div>
                            
                            <div class="toolbar-group flex-1" style="padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                                <div class="date-range-container">
                                    <div class="date-input-wrapper">
                                        <input type="date" name="start_date" value="<?= $start_date ?>" onchange="this.form.submit()">
                                    </div>
                                    <span class="text-muted">—</span>
                                    <div class="date-input-wrapper">
                                        <input type="date" name="end_date" value="<?= $end_date ?>" onchange="this.form.submit()">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="toolbar-group flex-1">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                <select name="kategori" onchange="this.form.submit()">
                                    <option value="">Semua kategori</option>
                                    <?php while ($k = mysqli_fetch_assoc($q_kategori_list)): ?>
                                        <option value="<?= htmlspecialchars($k['kategori']) ?>" <?= $k['kategori'] == $kategori ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['kategori']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction ID</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th style="text-align:right;">Amount</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_trx > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($q_transaksi)): 
                                    $isMasuk = $row['tipe'] == 'Pemasukan';
                                ?>
                                <tr>
                                    <td class="text-muted"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="text-muted"><?= $row['trx_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['deskripsi']) ?></strong></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td><span class="badge <?= $isMasuk ? 'badge-green' : 'badge-red' ?>"><?= $row['tipe'] ?></span></td>
                                    <td style="text-align:right">
                                        <strong class="<?= $isMasuk ? 'text-green' : 'text-red' ?>">
                                            <?= ($isMasuk ? '+ ' : '- ') . formatRupiah($row['jumlah']) ?>
                                        </strong>
                                    </td>
                                    <td style="text-align:center">
                                        <button type="button" class="btn-icon" onclick="openEditModal('<?= $row['id'] ?>', '<?= $row['tanggal'] ?>', '<?= htmlspecialchars($row['deskripsi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['kategori'], ENT_QUOTES) ?>', '<?= $row['tipe'] ?>', '<?= $row['jumlah'] ?>')">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Hapus transaksi ini?');">
                                            <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                            <button type="submit" name="hapus_trx" class="btn-icon text-red"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center; padding: 30px;" class="text-muted">Tidak ada transaksi ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <span class="text-muted">Menampilkan <?= $total_trx ?> transaksi</span>
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
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" required class="form-control">
                        <optgroup label="Pendapatan">
                            <option value="Penjualan">Penjualan</option>
                            <option value="Penjualan Offline">Penjualan Offline</option>
                            <option value="Penjualan Online">Penjualan Online</option>
                            <option value="Pendapatan Lain">Pendapatan Lain</option>
                        </optgroup>
                        <optgroup label="HPP">
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
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" id="edit_kategori" required class="form-control">
                        <option value="Penjualan">Penjualan</option>
                        <option value="Penjualan Offline">Penjualan Offline</option>
                        <option value="Penjualan Online">Penjualan Online</option>
                        <option value="Pendapatan Lain">Pendapatan Lain</option>
                        <option value="Bahan Baku">Bahan Baku</option>
                        <option value="Kemasan">Kemasan</option>
                        <option value="Gaji">Gaji</option>
                        <option value="Sewa & Listrik">Sewa & Listrik</option>
                        <option value="Pemasaran">Pemasaran</option>
                        <option value="Beban Lainnya">Beban Lainnya</option>
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
            document.getElementById('edit_kategori').value = kategori;
            document.getElementById('edit_tipe').value = tipe;
            document.getElementById('edit_jumlah').value = jumlah;
            document.getElementById('modalEditTrx').style.display = 'flex';
        }
    </script>
</body>
</html>