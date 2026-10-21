<?php
require 'layout/header.php';
require 'layout/sidebar.php';

if (isset($_POST['tambah_koleksi'])) {
    $db->collection('koleksi')->add([
        'judul'     => $_POST['judul'],
        'kategori'  => $_POST['kategori'], // 'buku_fisik' atau 'ebook'
        'penulis'   => $_POST['penulis'],
        'penerbit'  => $_POST['penerbit'],
        'tahun'     => (int)$_POST['tahun'],
        'stok'      => $_POST['kategori'] === 'buku_fisik' ? (int)$_POST['stok'] : 0,
        'link_file' => $_POST['kategori'] === 'ebook' ? $_POST['link_file'] : ''
    ]);
    echo "<script>location.href='koleksi.php';</script>";
}

$koleksi = $db->collection('koleksi')->documents();
?>

<div class="content-wrapper p-3">
  <div class="content-header"><h1>Koleksi Perpustakaan</h1></div>
  <div class="content">
    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#modalTambah">+ Tambah Koleksi</button>
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-bordered">
          <thead>
            <tr><th>Kategori</th><th>Judul</th><th>Penulis</th><th>Penerbit</th><th>Detail</th></tr>
          </thead>
          <tbody>
            <?php foreach ($koleksi as $k): if($k->exists()): $d = $k->data(); ?>
              <tr>
                <td>
                  <span class="badge badge-<?= $d['kategori'] === 'ebook' ? 'warning' : 'primary'; ?>">
                    <?= $d['kategori'] === 'ebook' ? 'E-Book' : 'Buku Fisik'; ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($d['judul']); ?></td>
                <td><?= htmlspecialchars($d['penulis']); ?></td>
                <td><?= htmlspecialchars($d['penerbit']); ?> (<?= $d['tahun']; ?>)</td>
                <td>
                  <?= $d['kategori'] === 'ebook' 
                      ? "<a href='".$d['link_file']."' target='_blank' class='btn btn-sm btn-info'>Buka File</a>" 
                      : "Stok: <b>".$d['stok']."</b>"; ?>
                </td>
              </tr>
            <?php endif; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h4 class="modal-title">Tambah Koleksi Baru</h4></div>
      <div class="modal-body">
        <div class="form-group"><label>Judul</label><input type="text" name="judul" class="form-control" required></div>
        <div class="form-group">
          <label>Kategori</label>
          <select name="kategori" class="form-control" required>
            <option value="buku_fisik">Buku Fisik</option>
            <option value="ebook">E-Book</option>
          </select>
        </div>
        <div class="form-group"><label>Penulis</label><input type="text" name="penulis" class="form-control" required></div>
        <div class="form-group"><label>Penerbit</label><input type="text" name="penerbit" class="form-control" required></div>
        <div class="form-group"><label>Tahun Terbit</label><input type="number" name="tahun" class="form-control" required></div>
        <div class="form-group"><label>Stok (Khusus Buku Fisik)</label><input type="number" name="stok" class="form-control" value="0"></div>
        <div class="form-group"><label>URL/Link File (Khusus E-Book)</label><input type="text" name="link_file" class="form-control" placeholder="https://..."></div>
      </div>
      <div class="modal-footer"><button type="submit" name="tambah_koleksi" class="btn btn-primary">Simpan</button></div>
    </form>
  </div>
</div>

<?php require 'layout/footer.php'; ?>