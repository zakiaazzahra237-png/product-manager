<?php
session_start();
require_once 'db.php';

// Generate Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Variabel Form Edit
$edit_mode = false;
$edit_data = ['id' => '', 'name' => '', 'category' => '', 'price' => '', 'stock' => ''];

// --- HANDLER ACTION (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Token Validation Failed!");
    }

    $action = $_POST['action'] ?? '';

    // Action 1: Create & Update
    if ($action === 'save') {
        $id       = trim($_POST['id'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price    = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $stock    = filter_var($_POST['stock'], FILTER_VALIDATE_INT);

        // Validasi
        if (strlen($name) < 3) {
            $errors[] = "Nama produk minimal 3 karakter!";
        }
        if ($price === false || $price <= 0) {
            $errors[] = "Harga harus berupa angka lebih dari 0!";
        }
        if ($stock === false || $stock < 0) {
            $errors[] = "Stok harus berupa angka 0 atau lebih!";
        }

        // Cek Nama Unik
        if (empty($errors)) {
            if ($id) {
                // Update Check
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = :name AND id != :id");
                $stmt->execute([':name' => $name, ':id' => $id]);
            } else {
                // Create Check
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = :name");
                $stmt->execute([':name' => $name]);
            }
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Nama produk sudah ada, harap gunakan nama unik!";
            }
        }

        // Simpan ke Database jika tidak ada error
        if (empty($errors)) {
            if ($id) {
                // Query UPDATE
                $stmt = $pdo->prepare("UPDATE products SET name = :name, category = :category, price = :price, stock = :stock WHERE id = :id");
                $stmt->execute([
                    ':name'     => $name,
                    ':category' => $category,
                    ':price'    => $price,
                    ':stock'    => $stock,
                    ':id'       => $id
                ]);
                $_SESSION['success'] = "Produk berhasil diperbarui!";
            } else {
                // Query INSERT
                $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock) VALUES (:name, :category, :price, :stock)");
                $stmt->execute([
                    ':name'     => $name,
                    ':category' => $category,
                    ':price'    => $price,
                    ':stock'    => $stock
                ]);
                $_SESSION['success'] = "Produk berhasil ditambahkan!";
            }
            header("Location: index.php");
            exit;
        } else {
            $edit_mode = !empty($id);
            $edit_data = ['id' => $id, 'name' => $name, 'category' => $category, 'price' => $_POST['price'], 'stock' => $_POST['stock']];
        }
    }

    // Action 2: Delete
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = "Produk berhasil dihapus!";
        }
        header("Location: index.php");
        exit;
    }
}

// --- HANDLER FETCH EDIT (GET) ---
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $data = $stmt->fetch();
    if ($data) {
        $edit_mode = true;
        $edit_data = $data;
    }
}

// --- FETCH ALL PRODUCTS (READ) ---
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Manager</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Warna latar belakang utama Mahogany */
        body { background-color: #2c1810; color: #f5e6d3; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { text-align: center; color: #e6b89c; }
        
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 6px; }
        .alert-danger { background-color: #7f1d1d; color: #fecaca; }
        .alert-success { background-color: #14532d; color: #bbf7d0; }
        
        /* Kartu Form & Input */
        .card-form { background-color: #3d2317; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #5c3726; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #d4a373; font-weight: 500; }
        input, select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #5c3726; background-color: #2c1810; color: #fff; }
        input:focus { outline: none; border-color: #dda15e; }
        
        /* Tombol Akses */
        button { background-color: #bc6c25; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        button:hover { background-color: #dda15e; color: #2c1810; }
        .btn-cancel { background-color: #5c3726; text-decoration: none; padding: 10px 20px; color: white; border-radius: 6px; display: inline-block; }
        
        /* Grid Card Produk */
        .grid-products { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background-color: #3d2317; border-radius: 8px; padding: 15px; border: 1px solid #5c3726; display: flex; flex-direction: column; justify-content: space-between; }
        .product-card h3 { margin-top: 0; color: #dda15e; word-break: break-word; }
        .product-info { font-size: 0.9em; color: #faedcd; margin-bottom: 15px; }
        .card-actions { display: flex; gap: 10px; }
        .btn-edit { background-color: #d4a373; color: #2c1810; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .btn-delete { background-color: #99582a; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 0.85em; cursor: pointer; }
        .btn-delete:hover { background-color: #7f1d1d; }
    </style>
</head>
<body>
<div class="container">
    <h1>Product Manager</h1>

    <!-- Notifikasi Error -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Notifikasi Sukses -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Form Create / Update -->
    <div class="card-form">
        <h2><?= $edit_mode ? 'Edit Produk' : 'Tambah Produk Baru' ?></h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label>Nama Produk (Min. 3 Karakter, Unik):</label>
                <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Kategori:</label>
                <input type="text" name="category" value="<?= htmlspecialchars($edit_data['category'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Harga (Rp):</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($edit_data['price'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Stok:</label>
                <input type="number" name="stock" value="<?= htmlspecialchars($edit_data['stock'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <button type="submit"><?= $edit_mode ? 'Update Produk' : 'Simpan Produk' ?></button>
            <?php if ($edit_mode): ?>
                <a href="index.php" class="btn-cancel">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Read: Card Responsif -->
    <h2>Daftar Produk</h2>
    <div class="grid-products">
        <?php if (empty($products)): ?>
            <p>Belum ada produk tersimpan.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div>
                        <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="product-info">
                            <p><strong>Kategori:</strong> <?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Harga:</strong> Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                            <p><strong>Stok:</strong> <?= htmlspecialchars($product['stock'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <div class="card-actions">
                        <a href="index.php?edit_id=<?= $product['id'] ?>" class="btn-edit">Edit</a>
                        
                        <form action="index.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus produk ini?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <button type="submit" class="btn-delete">Hapus</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>