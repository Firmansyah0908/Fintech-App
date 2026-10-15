<?php 
include 'koneksi.php'; 

// PROSES CRUD INVENTARIS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['simpan_produk'])) {
        $sku   = mysqli_real_escape_string($conn, $_POST['sku']);
        $nama  = mysqli_real_escape_string($conn, $_POST['nama_produk']);
        $harga = $_POST['harga'];
        $stok  = $_POST['stok'];

        mysqli_query($conn, "INSERT INTO inventaris (sku, nama_produk, harga, stok) VALUES ('$sku', '$nama', '$harga', '$stok')");
        header("Location: inventaris.php");
        exit;
    }
    
    if (isset($_POST['edit_produk'])) {
        $id    = $_POST['edit_id'];
        $sku   = mysqli_real_escape_string($conn, $_POST['sku']);
        $nama  = mysqli_real_escape_string($conn, $_POST['nama_produk']);
        $harga = $_POST['harga'];
        $stok  = $_POST['stok'];

        mysqli_query($conn, "UPDATE inventaris SET sku='$sku', nama_produk='$nama', harga='$harga', stok='$stok' WHERE id='$id'");
        header("Location: inventaris.php");
        exit;
    }
    
    if (isset($_POST['hapus_produk'])) {
        $id = $_POST['id_hapus'];
        mysqli_query($conn, "DELETE FROM inventaris WHERE id='$id'");
        header("Location: inventaris.php");
        exit;
    }
}

// FILTER PENCARIAN
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where  = $search != '' ? "WHERE sku LIKE '%$search%' OR nama_produk LIKE '%$search%'" : "";

$res = mysqli_query($conn, "SELECT * FROM inventaris $where ORDER BY id ASC");
$total_produk = mysqli_num_rows($res);

$where_low = $where == '' ? "WHERE stok < 10" : "$where AND stok < 10";
$q_low = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as low_count FROM inventaris $where_low"));
$low_stock_tampil = $q_low['low_count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inventaris - uangku.</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-layout">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <?php include 'header.php'; ?>
            <div class="content-body">
                <div class="breadcrumb">Workspace / Inventaris</div>
                <div class="page-header" style="margin-bottom: 24px;">
                    <div>
                        <h1>Inventaris</h1>
                        <p class="text-muted">Semua produk dan persediaan bisnis Anda, dalam satu tempat.</p>
                    </div>
                    <button class="btn-primary" onclick="document.getElementById('modalAddProduct').style.display='flex'">+ Add Product</button>
                </div>

                <form method="GET" action="inventaris.php">
                    <div class="inventory-toolbar">
                        <div class="search-box">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" placeholder="Cari nama produk atau SKU..." value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
                        </div>
                        <div class="inventory-stats">
                            <span class="text-muted"><?= $total_produk ?> produk</span> 
                            <span style="color: #d1d5db;">|</span> 
                            <span class="text-red font-bold"><?= $low_stock_tampil ?> stok rendah</span>
                        </div>
                    </div>
                </form>

                <div class="inventory-grid">
                    <?php if ($total_produk > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res)): 
                            $isLow = $row['stok'] < 10;
                        ?>
                        <div class="card product-card">
                            <div class="product-img">
                                <span class="img-badge <?= $isLow ? 'badge-red' : 'badge-gray' ?>">
                                    <?= $isLow ? 'Low Stock' : 'In Stock' ?>
                                </span>
                                <div class="img-placeholder">
                                    <i class="ri-cup-line" style="font-size: 36px; color: #9ca3af;"></i>
                                </div>
                            </div>
                            <div class="product-info">
                                <span class="text-muted" style="font-size:11px; text-transform:uppercase;">SKU: <?= $row['sku'] ?></span>
                                <h3 style="font-size:16px; margin: 4px 0 8px 0;"><?= htmlspecialchars($row['nama_produk']) ?></h3>
                                <h2 style="font-size:18px; color: var(--primary); margin:0;"><?= formatRupiah($row['harga']) ?></h2>
                                
                                <div class="product-bottom">
                                    <span class="text-muted" style="font-size:13px;">Stok: <strong style="color:var(--text-main);"><?= $row['stok'] ?></strong></span>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-icon outline-icon" onclick="openEditProductModal('<?= $row['id'] ?>', '<?= $row['sku'] ?>', '<?= htmlspecialchars($row['nama_produk'], ENT_QUOTES) ?>', '<?= $row['harga'] ?>', '<?= $row['stok'] ?>')">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Hapus produk ini?');">
                                            <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                            <button type="submit" name="hapus_produk" class="btn-icon outline-icon delete-icon">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted" style="grid-column: 1 / -1;">Tidak ada produk ditemukan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Tambah Produk -->
    <div id="modalAddProduct" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Produk</h2>
                <span class="close-btn" onclick="document.getElementById('modalAddProduct').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="inventaris.php">
                <div class="form-group"><label>SKU</label><input type="text" name="sku" required class="form-control" placeholder="Cth: KOP-001"></div>
                <div class="form-group"><label>Nama Produk</label><input type="text" name="nama_produk" required class="form-control" placeholder="Cth: Kopi Susu"></div>
                <div class="form-group"><label>Harga (Rp)</label><input type="number" name="harga" required class="form-control"></div>
                <div class="form-group"><label>Stok Awal</label><input type="number" name="stok" required class="form-control"></div>
                <button type="submit" name="simpan_produk" class="btn-primary w-100">Simpan Produk</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Produk -->
    <div id="modalEditProduct" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Produk</h2>
                <span class="close-btn" onclick="document.getElementById('modalEditProduct').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="inventaris.php">
                <input type="hidden" name="edit_id" id="edit_inv_id">
                <div class="form-group"><label>SKU</label><input type="text" name="sku" id="edit_inv_sku" required class="form-control"></div>
                <div class="form-group"><label>Nama Produk</label><input type="text" name="nama_produk" id="edit_inv_nama" required class="form-control"></div>
                <div class="form-group"><label>Harga (Rp)</label><input type="number" name="harga" id="edit_inv_harga" required class="form-control"></div>
                <div class="form-group"><label>Stok</label><input type="number" name="stok" id="edit_inv_stok" required class="form-control"></div>
                <button type="submit" name="edit_produk" class="btn-primary w-100">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        function openEditProductModal(id, sku, nama, harga, stok) {
            document.getElementById('edit_inv_id').value = id;
            document.getElementById('edit_inv_sku').value = sku;
            document.getElementById('edit_inv_nama').value = nama;
            document.getElementById('edit_inv_harga').value = harga;
            document.getElementById('edit_inv_stok').value = stok;
            document.getElementById('modalEditProduct').style.display = 'flex';
        }
    </script>
</body>
</html>