<?php
require 'koneksi.php';

// Ambil koleksi 'buku'
$bukuCollection = $db->collection('buku');
$documents = $bukuCollection->documents();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Buku Firebase</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; color: white; display: inline-block; }
        .btn-tambah { background: #28a745; margin-bottom: 10px; }
        .btn-hapus { background: #dc3545; }
    </style>
</head>
<body>

    <h2>Katalog Buku (Firebase Firestore)</h2>
    <a href="tambah.php" class="btn btn-tambah">+ Tambah Buku Baru</a>

    <table>
        <thead>
            <tr>
                <th>ID Dokumen</th>
                <th>Judul Buku</th>
                <th>Penulis</th>
                <th>Penerbit</th>
                <th>Tahun</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): ?>
                <?php if ($doc->exists()): $data = $doc->data(); ?>
                    <tr>
                        <td><code><?= $doc->id(); ?></code></td>
                        <td><?= htmlspecialchars($data['judul'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($data['penulis'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($data['penerbit'] ?? '-'); ?></td>
                        <td><?= $data['tahun'] ?? '-'; ?></td>
                        <td><?= $data['stok'] ?? 0; ?></td>
                        <td>
                            <a href="hapus.php?id=<?= $doc->id(); ?>" class="btn btn-hapus" onclick="return confirm('Yakin ingin menghapus buku ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>