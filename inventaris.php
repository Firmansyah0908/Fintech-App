<?php include 'koneksi.php'; 

// ==========================================
// PROSES CRUD (CREATE, UPDATE, DELETE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. TAMBAH PRODUK
    if (isset($_POST['simpan_produk'])) {
        $sku = $_POST['sku'];
        $nama = $_POST['nama_produk'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];

        $stmt = $conn->prepare("INSERT INTO inventaris (sku, nama_produk, harga, stok) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $sku, $nama, $harga, $stok);
        $stmt->execute();
        $stmt->close();
        header("Location: inventaris.php");
        exit;
    }
    
    // 2. EDIT PRODUK
    if (isset($_POST['edit_produk'])) {
        $id = $_POST['edit_id'];
        $sku = $_POST['sku'];
        $nama = $_POST['nama_produk'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];

        $stmt = $conn->prepare("UPDATE inventaris SET sku=?, nama_produk=?, harga=?, stok=? WHERE id=?");
        $stmt->bind_param("ssdii", $sku, $nama, $harga, $stok, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: inventaris.php");
        exit;
    }
    
    // 3. HAPUS PRODUK
    if (isset($_POST['hapus_produk'])) {
        $id = $_POST['id_hapus'];
        $stmt = $conn->prepare("DELETE FROM inventaris WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: inventaris.php");
        exit;
    }
}

// ==========================================
// FILTER PENCARIAN
// ==========================================
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_sql = "";
if (!empty($search_query)) {
    $where_sql = "WHERE sku LIKE '%$search_query%' OR nama_produk LIKE '%$search_query%'";
}

// Jalankan query dengan filter
$res = $conn->query("SELECT * FROM inventaris $where_sql ORDER BY id ASC");
$total_produk = $res->num_rows;

// Hitung stok rendah berdasarkan query yang difilter
$q_low = $conn->query("SELECT COUNT(*) as low_count FROM inventaris $where_sql " . ($where_sql == "" ? "WHERE" : "AND") . " stok < 10");
$low_stock_count_tampil = $q_low->fetch_assoc()['low_count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inventaris - uangku.</title>
    <!-- Pastikan cache busting agar CSS baru terbaca -->
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
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
                    <button class="btn-primary" onclick="document.getElementById('modalAddProduct').style.display='flex'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px; vertical-align:middle;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Product
                    </button>
                </div>

                <!-- Form Search & Indikator Sesuai Desain 2 -->
                <form method="GET" action="inventaris.php" id="searchForm">
                    <div class="inventory-toolbar">
                        <div class="search-box">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" name="search" placeholder="Cari nama produk atau SKU..." value="<?= htmlspecialchars($search_query) ?>" onchange="document.getElementById('searchForm').submit();">
                        </div>
                        <div class="inventory-stats">
                            <span class="text-muted"><?= $total_produk ?> produk</span> 
                            <span style="color: #d1d5db;">|</span> 
                            <span class="text-red font-bold"><?= $low_stock_count_tampil ?> stok rendah</span>
                        </div>
                    </div>
                    <input type="submit" style="display: none;" />
                </form>

                <div class="inventory-grid">
                    <?php
                    if ($total_produk > 0) {
                        while($row = $res->fetch_assoc()):
                            $isLow = $row['stok'] < 10;
                            // Badge menempel di atas gambar sesuai referensi
                            $badgeHTML = $isLow ? '<span class="img-badge badge-red">Low Stock</span>' : '<span class="img-badge badge-gray">In Stock</span>';
                    ?>
                    <div class="card product-card">
                        <div class="product-img">
                            <?= $badgeHTML ?>
                            <!-- Menggunakan placeholder foto generik abu-abu karena database tidak menyimpan file gambar -->
                            <div class="img-placeholder">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                            </div>
                        </div>
                        <div class="product-info">
                            <span class="text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing: 0.5px;">SKU: <?= $row['sku'] ?></span>
                            <h3 style="font-size:16px; margin: 4px 0 8px 0; color: var(--text-main);"><?= htmlspecialchars($row['nama_produk']) ?></h3>
                            <h2 style="font-size:18px; color: var(--primary); margin:0;">Rp <?= number_format($row['harga'], 0, ',', '.') ?></h2>
                            
                            <div class="product-bottom">
                                <span class="text-muted" style="font-size:13px;">Stok: <strong style="color:var(--text-main);"><?= $row['stok'] ?></strong></span>
                                <div class="action-buttons">
                                    <!-- Tombol Edit -->
                                    <button type="button" class="btn-icon outline-icon" onclick="openEditProductModal('<?= $row['id'] ?>', '<?= $row['sku'] ?>', '<?= htmlspecialchars($row['nama_produk'], ENT_QUOTES) ?>', '<?= $row['harga'] ?>', '<?= $row['stok'] ?>')" title="Edit">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <!-- Form Delete -->
                                    <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                        <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                                        <button type="submit" name="hapus_produk" class="btn-icon outline-icon delete-icon" title="Hapus">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<p class='text-muted' style='grid-column: 1 / -1;'>Tidak ada produk ditemukan.</p>";
                    }
                    ?>
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

        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener("keypress", function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    document.getElementById("searchForm").submit();
                }
            });
        }
    </script>
</body>
</html>