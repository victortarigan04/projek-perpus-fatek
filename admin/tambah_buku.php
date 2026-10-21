<?php
require 'koneksi.php';

if (isset($_POST['submit'])) {
    $judul    = $_POST['judul'];
    $penulis  = $_POST['penulis'];
    $penerbit = $_POST['penerbit'];
    $tahun    = (int)$_POST['tahun'];
    $stok     = (int)$_POST['stok'];

    // Menambah data ke koleksi 'buku'
    $db->collection('buku')->add([
        'judul'      => $judul,
        'penulis'    => $penulis,
        'penerbit'   => $penerbit,
        'tahun'      => $tahun,
        'stok'       => $stok,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Kembalikan ke halaman utama setelah berhasil
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 400px; }
        div { margin-bottom: 12px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <h2>Tambah Buku Baru</h2>
    <form method="POST">
        <div>
            <label>Judul Buku:</label>
            <input type="text" name="judul" required>
        </div>
        <div>
            <label>Penulis:</label>
            <input type="text" name="penulis" required>
        </div>
        <div>
            <label>Penerbit:</label>
            <input type="text" name="penerbit" required>
        </div>
        <div>
            <label>Tahun Terbit:</label>
            <input type="number" name="tahun" required>
        </div>
        <div>
            <label>Stok Buku:</label>
            <input type="number" name="stok" required>
        </div>
        <button type="submit" name="submit">Simpan ke Firebase</button>
        <a href="index.php">Batal</a>
    </form>

</body>
</html>