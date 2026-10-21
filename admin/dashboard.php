<?php
require 'layout/header.php';
require 'layout/sidebar.php';

// Ambil statistik data dari Firestore
try {
    $totalBukuFisik = iterator_count($db->collection('buku_fisik')->documents());
    $totalEbook     = iterator_count($db->collection('ebook')->documents());
    $totalSkripsi   = iterator_count($db->collection('skripsi')->documents());
    $totalBerita    = iterator_count($db->collection('berita')->documents());
    $totalAdmin     = iterator_count($db->collection('admins')->documents());

    // Ambil data terbaru untuk ringkasan dashboard
    $bukuFisikQuery = $db->collection('buku_fisik')->limit(5)->documents();
    $ebookQuery     = $db->collection('ebook')->limit(5)->documents();
    $skripsiQuery   = $db->collection('skripsi')->limit(5)->documents();
    $beritaQuery    = $db->collection('berita')->limit(3)->documents();
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>

<div class="content-wrapper">
  <!-- Content Header -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-tachometer-alt mr-2 text-primary"></i>Dashboard Utama</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">

      <!-- Welcome Banner -->
      <div class="row">
        <div class="col-12">
          <div class="alert alert-primary alert-dismissible fade show elevation-1 mb-4" role="alert">
            <h5><i class="icon fas fa-user-circle"></i> Selamat Datang, <b><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin'); ?></b>!</h5>
            Anda login sebagai <b><span class="badge badge-light text-uppercase"><?= htmlspecialchars($_SESSION['admin_level'] ?? 'admin'); ?></span></b>.
            <?php if (($_SESSION['admin_level'] ?? '') === 'superadmin'): ?>
              Sebagai <b>Superadmin</b>, Anda memiliki akses penuh ke seluruh modul sistem termasuk kelola data admin.
            <?php else: ?>
              Sebagai <b>Admin</b>, Anda dapat mengelola modul Buku Fisik, E-Book Digital, Skripsi Mahasiswa, dan Berita/Pengumuman.
            <?php endif; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="row">
        <!-- Box 1: Buku Fisik -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-primary elevation-2">
            <div class="inner">
              <h3><?= $totalBukuFisik ?? 0; ?></h3>
              <p>Koleksi Buku Fisik</p>
            </div>
            <div class="icon"><i class="fas fa-book"></i></div>
            <a href="buku_fisik.php" class="small-box-footer">Kelola Buku Fisik <i class="fas fa-arrow-circle-right ml-1"></i></a>
          </div>
        </div>

        <!-- Box 2: E-Book Digital -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info elevation-2">
            <div class="inner">
              <h3><?= $totalEbook ?? 0; ?></h3>
              <p>Koleksi E-Book Digital</p>
            </div>
            <div class="icon"><i class="fas fa-book-reader"></i></div>
            <a href="ebook.php" class="small-box-footer">Kelola E-Book <i class="fas fa-arrow-circle-right ml-1"></i></a>
          </div>
        </div>

        <!-- Box 3: Skripsi Mahasiswa -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success elevation-2">
            <div class="inner">
              <h3><?= $totalSkripsi ?? 0; ?></h3>
              <p>Skripsi Mahasiswa</p>
            </div>
            <div class="icon"><i class="fas fa-graduation-cap"></i></div>
            <a href="skripsi.php" class="small-box-footer">Kelola Skripsi <i class="fas fa-arrow-circle-right ml-1"></i></a>
          </div>
        </div>

        <!-- Box 4: Berita & Pengumuman -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning elevation-2">
            <div class="inner">
              <h3 class="text-white"><?= $totalBerita ?? 0; ?></h3>
              <p class="text-white">Berita & Pengumuman</p>
            </div>
            <div class="icon"><i class="fas fa-newspaper"></i></div>
            <a href="berita.php" class="small-box-footer text-white" style="color: #fff !important;">Kelola Berita <i class="fas fa-arrow-circle-right ml-1"></i></a>
          </div>
        </div>
      </div>

      <!-- Bar Aksi Cepat -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="card card-outline card-secondary mb-0">
            <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between">
              <span class="font-weight-bold ml-2 text-secondary"><i class="fas fa-bolt text-warning mr-1"></i> Aksi Cepat:</span>
              <div>
                <a href="buku_fisik.php" class="btn btn-sm btn-primary m-1"><i class="fas fa-plus mr-1"></i> Tambah Buku Fisik</a>
                <a href="ebook.php" class="btn btn-sm btn-info m-1"><i class="fas fa-plus mr-1"></i> Tambah E-Book</a>
                <a href="skripsi.php" class="btn btn-sm btn-success m-1"><i class="fas fa-plus mr-1"></i> Tambah Skripsi</a>
                <a href="berita.php" class="btn btn-sm btn-warning text-white m-1"><i class="fas fa-bullhorn mr-1"></i> Buat Berita</a>
                <?php if (($_SESSION['admin_level'] ?? '') === 'superadmin'): ?>
                  <a href="admin.php" class="btn btn-sm btn-danger m-1"><i class="fas fa-user-plus mr-1"></i> Kelola Admin (<?= $totalAdmin; ?>)</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Row -->
      <div class="row">
        <!-- Kolom Kiri -->
        <section class="col-lg-6">

          <!-- Buku Fisik Terbaru -->
          <div class="card card-primary card-outline elevation-2">
            <div class="card-header border-0">
              <h3 class="card-title font-weight-bold"><i class="fas fa-book mr-1 text-primary"></i> Buku Fisik Terbaru</h3>
              <div class="card-tools">
                <a href="buku_fisik.php" class="btn btn-tool btn-sm"><i class="fas fa-external-link-alt"></i> Lihat Semua</a>
              </div>
            </div>
            <div class="card-body p-0">
              <ul class="products-list product-list-in-card pl-3 pr-3">
                <?php
                $hasBuku = false;
                if (isset($bukuFisikQuery)):
                  foreach ($bukuFisikQuery as $doc):
                    if ($doc->exists()):
                      $hasBuku = true;
                      $data = $doc->data();
                ?>
                  <li class="item py-2">
                    <div class="product-info ml-1">
                      <a href="buku_fisik.php" class="product-title text-primary font-weight-bold">
                        <?= htmlspecialchars($data['judul'] ?? 'Buku'); ?>
                        <span class="badge badge-success float-right">Stok: <?= $data['stok'] ?? 0; ?></span>
                      </a>
                      <span class="product-description text-muted">
                        Penulis: <?= htmlspecialchars($data['penulis'] ?? '-'); ?> | Penerbit: <?= htmlspecialchars($data['penerbit'] ?? '-'); ?>
                      </span>
                    </div>
                  </li>
                <?php
                    endif;
                  endforeach;
                endif;
                if (!$hasBuku):
                ?>
                  <li class="item p-3 text-center text-muted">Belum ada koleksi buku fisik.</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>

          <!-- Berita & Pengumuman Terbaru -->
          <div class="card card-warning card-outline elevation-2">
            <div class="card-header border-0">
              <h3 class="card-title font-weight-bold"><i class="fas fa-bullhorn mr-1 text-warning"></i> Berita & Pengumuman Terbaru</h3>
              <div class="card-tools">
                <a href="berita.php" class="btn btn-tool btn-sm"><i class="fas fa-external-link-alt"></i></a>
              </div>
            </div>
            <div class="card-body p-0">
              <ul class="products-list product-list-in-card pl-3 pr-3">
                <?php
                $hasBerita = false;
                if (isset($beritaQuery)):
                  foreach ($beritaQuery as $doc):
                    if ($doc->exists()):
                      $hasBerita = true;
                      $data = $doc->data();
                ?>
                  <li class="item p-2">
                    <div class="product-info ml-1">
                      <a href="berita.php" class="product-title font-weight-bold text-dark">
                        <?= htmlspecialchars($data['judul'] ?? 'Berita'); ?>
                        <span class="badge badge-info float-right"><i class="far fa-clock"></i> <?= htmlspecialchars(substr($data['tanggal'] ?? '', 0, 10)); ?></span>
                      </a>
                      <span class="product-description text-muted">
                        <?= htmlspecialchars(substr($data['isi'] ?? '', 0, 80)); ?>...
                      </span>
                    </div>
                  </li>
                <?php
                    endif;
                  endforeach;
                endif;
                if (!$hasBerita):
                ?>
                  <li class="item p-3 text-center text-muted">Belum ada berita/pengumuman.</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>

        </section>

        <!-- Kolom Kanan -->
        <section class="col-lg-6">

          <!-- E-Book Digital Terbaru -->
          <div class="card card-info card-outline elevation-2">
            <div class="card-header border-0">
              <h3 class="card-title font-weight-bold"><i class="fas fa-book-reader mr-1 text-info"></i> E-Book Digital Terbaru</h3>
              <div class="card-tools">
                <a href="ebook.php" class="btn btn-tool btn-sm"><i class="fas fa-external-link-alt"></i> Lihat Semua</a>
              </div>
            </div>
            <div class="card-body p-0">
              <ul class="products-list product-list-in-card pl-3 pr-3">
                <?php
                $hasEbook = false;
                if (isset($ebookQuery)):
                  foreach ($ebookQuery as $doc):
                    if ($doc->exists()):
                      $hasEbook = true;
                      $data = $doc->data();
                ?>
                  <li class="item py-2">
                    <div class="product-info ml-1">
                      <a href="ebook.php" class="product-title text-info font-weight-bold">
                        <?= htmlspecialchars($data['judul'] ?? 'E-Book'); ?>
                        <span class="badge badge-secondary float-right"><?= $data['jml_halaman'] ?? 0; ?> Hal</span>
                      </a>
                      <span class="product-description text-muted">
                        Penulis: <?= htmlspecialchars($data['penulis'] ?? '-'); ?> | Penerbit: <?= htmlspecialchars($data['penerbit'] ?? '-'); ?>
                      </span>
                    </div>
                  </li>
                <?php
                    endif;
                  endforeach;
                endif;
                if (!$hasEbook):
                ?>
                  <li class="item p-3 text-center text-muted">Belum ada koleksi e-book digital.</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>

          <!-- Skripsi Terbaru -->
          <div class="card card-success card-outline elevation-2">
            <div class="card-header border-0">
              <h3 class="card-title font-weight-bold"><i class="fas fa-graduation-cap mr-1 text-success"></i> Skripsi Terbaru</h3>
              <div class="card-tools">
                <a href="skripsi.php" class="btn btn-tool btn-sm"><i class="fas fa-external-link-alt"></i></a>
              </div>
            </div>
            <div class="card-body p-0">
              <ul class="products-list product-list-in-card pl-3 pr-3">
                <?php
                $hasSkripsi = false;
                if (isset($skripsiQuery)):
                  foreach ($skripsiQuery as $doc):
                    if ($doc->exists()):
                      $hasSkripsi = true;
                      $data = $doc->data();
                ?>
                  <li class="item py-2">
                    <div class="product-info ml-1">
                      <a href="skripsi.php" class="product-title text-success font-weight-bold">
                        <?= htmlspecialchars($data['judul'] ?? 'Skripsi'); ?>
                      </a>
                      <span class="product-description text-muted">
                        Mhs: <?= htmlspecialchars($data['nama_mhs'] ?? '-'); ?> (<?= htmlspecialchars($data['jurusan'] ?? '-'); ?> - <?= htmlspecialchars($data['prodi'] ?? '-'); ?>)
                      </span>
                    </div>
                  </li>
                <?php
                    endif;
                  endforeach;
                endif;
                if (!$hasSkripsi):
                ?>
                  <li class="item p-3 text-center text-muted">Belum ada skripsi tersimpan.</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>

        </section>
      </div>

    </div>
  </section>
</div>

<?php require 'layout/footer.php'; ?>