<?php
require_once 'admin/koneksi.php';

// Helper function untuk resolusi path media dari folder admin/
function get_media_url($path, $default = 'https://via.placeholder.com/300x400?text=No+Image') {
    if (empty($path)) return $default;
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    $fullPath = (strpos($path, 'admin/') === 0) ? $path : 'admin/' . $path;
    if (file_exists(__DIR__ . '/' . $fullPath)) {
        return $fullPath;
    }
    return $default;
}

// 1. Ambil Slide Banner Aktif
$activeSlides = [];
try {
    $slidesQuery = $db->collection('slides')->where('status', '==', 'aktif')->documents();
    foreach ($slidesQuery as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            $activeSlides[] = $data;
        }
    }
} catch (Exception $e) {
    $activeSlides = [];
}

// 2. Ambil Statistik Hitungan Koleksi Real-Time
try {
    $totalBukuFisik = iterator_count($db->collection('buku_fisik')->documents());
    $totalEbook     = iterator_count($db->collection('ebook')->documents());
    $totalSkripsi   = iterator_count($db->collection('skripsi')->documents());
} catch (Exception $e) {
    $totalBukuFisik = 0; $totalEbook = 0; $totalSkripsi = 0;
}

// 3. Ambil Koleksi Buku Fisik Terbaru
$bukuFisikList = [];
try {
    $bukuDocs = $db->collection('buku_fisik')->orderBy('created_at', 'DESC')->limit(6)->documents();
    foreach ($bukuDocs as $doc) {
        if ($doc->exists()) {
            $d = $doc->data();
            $d['id'] = $doc->id();
            $bukuFisikList[] = $d;
        }
    }
} catch (Exception $e) { $bukuFisikList = []; }

// 4. Ambil Koleksi E-Book Terbaru
$ebookList = [];
try {
    $ebookDocs = $db->collection('ebook')->orderBy('created_at', 'DESC')->limit(6)->documents();
    foreach ($ebookDocs as $doc) {
        if ($doc->exists()) {
            $d = $doc->data();
            $d['id'] = $doc->id();
            $ebookList[] = $d;
        }
    }
} catch (Exception $e) { $ebookList = []; }

// 5. Ambil Berita Terbaru
$beritaList = [];
try {
    $beritaDocs = $db->collection('berita')->orderBy('created_at', 'DESC')->limit(3)->documents();
    foreach ($beritaDocs as $doc) {
        if ($doc->exists()) {
            $d = $doc->data();
            $d['id'] = $doc->id();
            $beritaList[] = $d;
        }
    }
} catch (Exception $e) { $beritaList = []; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perpustakaan Elibrary Fatek UNSRAT - Portal Digital</title>
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">

  <style>
    :root {
      --primary-color: #1E3A8A;
      --secondary-color: #0284C7;
      --accent-color: #F59E0B;
      --bg-light: #F8FAFC;
      --text-dark: #1E293B;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      scroll-behavior: smooth;
    }

    .top-bar { background-color: var(--primary-color); color: #E2E8F0; font-size: 0.85rem; }
    .navbar-light-custom { background-color: #FFFFFF !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }

    .nav-link { color: var(--text-dark) !important; font-weight: 500; padding: 0.5rem 1rem !important; }
    .nav-link:hover, .nav-item.active .nav-link { color: var(--secondary-color) !important; }

    .hero-carousel .carousel-item {
      height: 460px; min-height: 400px; background-size: cover; background-position: center; position: relative;
    }
    .hero-carousel .carousel-item::before {
      content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.65) 0%, rgba(15, 23, 42, 0.45) 60%, rgba(15, 23, 42, 0.75) 100%);
      z-index: 1;
    }

    .carousel-caption-custom {
      position: absolute; top: 42%; left: 50%; transform: translate(-50%, -50%); z-index: 10; color: #FFFFFF; max-width: 850px; width: 90%;
    }

    .slide-badge {
      display: inline-block; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px);
      color: #FFFFFF; font-size: 0.8rem; font-weight: 600; padding: 5px 16px; border-radius: 30px;
      border: 1px solid rgba(255, 255, 255, 0.35); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1.5px;
    }

    .slide-title { font-size: 2.2rem; font-weight: 700; line-height: 1.35; text-shadow: 0 4px 15px rgba(0, 0, 0, 0.6); }

    .search-box-wrapper { margin-top: -65px; position: relative; z-index: 20; }
    .search-card { background: #FFFFFF; border-radius: 12px; border: none; box-shadow: 0 15px 35px rgba(30, 58, 138, 0.1); }

    .btn-accent { background-color: var(--accent-color); color: #FFFFFF; font-weight: 600; border: none; }
    .btn-accent:hover { background-color: #D97706; color: #FFFFFF; }

    .btn-primary-custom { background-color: var(--primary-color); color: #FFFFFF; border: none; font-weight: 500; }
    .btn-primary-custom:hover { background-color: #172554; color: #FFFFFF; }

    .card-katalog { border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; transition: all 0.3s ease; }
    .card-katalog:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07) !important; }
    .cover-thumb { height: 280px; object-fit: contain; background-color: #F1F5F9; padding: 14px; border-top-left-radius: 12px; border-top-right-radius: 12px; }

    .jurusan-card { background: #FFFFFF; border-radius: 10px; border-left: 5px solid var(--secondary-color); }

    footer { background-color: var(--primary-color); color: #94A3B8; }
    footer a { color: #CBD5E1; text-decoration: none; }
  </style>
</head>
<body>

  <!-- Include Header Navigasi -->
  <?php include 'header.php'; ?>

  <!-- Hero Carousel Slider -->
  <div id="heroCarousel" class="carousel slide hero-carousel" data-ride="carousel">
    <?php if (!empty($activeSlides)): ?>
      <ol class="carousel-indicators mb-5">
        <?php foreach ($activeSlides as $idx => $s): ?>
          <li data-target="#heroCarousel" data-slide-to="<?= $idx; ?>" class="<?= $idx === 0 ? 'active' : ''; ?>"></li>
        <?php endforeach; ?>
      </ol>
      <div class="carousel-inner">
        <?php foreach ($activeSlides as $idx => $s): 
          $gambarUrl = get_media_url($s['gambar'] ?? '', 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=1350&q=80');
        ?>
          <div class="carousel-item <?= $idx === 0 ? 'active' : ''; ?>" style="background-image: url('<?= htmlspecialchars($gambarUrl); ?>');">
            <div class="container text-center carousel-caption-custom">
              <div><span class="slide-badge"><i class="mdi mdi-star-circle-outline mr-1"></i> Elibrary Fatek UNSRAT</span></div>
              <h1 class="slide-title"><?= htmlspecialchars($s['teks_slide'] ?? ''); ?></h1>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="carousel-inner">
        <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=1350&q=80');">
          <div class="container text-center carousel-caption-custom">
            <div><span class="slide-badge"><i class="mdi mdi-star-circle-outline mr-1"></i> Elibrary Fatek UNSRAT</span></div>
            <h1 class="slide-title">Selamat Datang di Website Elibrary Fatek UNSRAT</h1>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <a class="carousel-control-prev" href="#heroCarousel" role="button" data-slide="prev"><span class="carousel-control-prev-icon"></span></a>
    <a class="carousel-control-next" href="#heroCarousel" role="button" data-slide="next"><span class="carousel-control-next-icon"></span></a>
  </div>

  <!-- Search Box -->
  <div class="container search-box-wrapper">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card search-card p-3 p-md-4">
          <form action="pencarian.php" method="get">
            <div class="row no-gutters align-items-center">
              <div class="col-md-3 mb-2 mb-md-0 pr-md-2">
                <select name="kategori" class="form-control form-control-lg border-light bg-light" style="font-size: 0.95rem;">
                  <option value="semua">Semua Katalog</option>
                  <option value="buku_fisik">Buku Fisik</option>
                  <option value="ebook">E-Book Digital</option>
                  <option value="skripsi">Skripsi Mahasiswa</option>
                </select>
              </div>
              <div class="col-md-7 mb-2 mb-md-0 pr-md-2">
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text bg-light border-light text-muted"><i class="mdi mdi-magnify mdi-24px"></i></span></div>
                  <input type="text" name="q" class="form-control form-control-lg border-light bg-light" placeholder="Ketik Judul, Penulis, ISBN, atau Nama Mahasiswa..." style="font-size: 0.95rem;" required>
                </div>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-accent btn-lg btn-block" style="font-size: 0.95rem;"><i class="mdi mdi-magnify mr-1"></i> Cari</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION BANNER INFORMASI REQUEST JURNAL DOSEN -->
  <section class="py-5 bg-light" id="request-jurnal">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-9 mx-auto text-center">
          <div class="p-4 p-md-5 bg-white shadow-sm" style="border-radius: 16px; border-top: 5px solid var(--primary-color);">
            <div class="d-inline-block px-3 py-1 mb-3" style="background-color: #E0F2FE; color: #0369A1; font-weight: 600; font-size: 0.85rem; border-radius: 20px;">
              <i class="mdi mdi-school-outline mr-1"></i> Layanan Akademik Dosen FT UNSRAT
            </div>
            <h3 class="font-weight-bold text-dark mb-3">Layanan Request Jurnal & Literasi Riset</h3>
            <p class="text-muted mb-4" style="line-height: 1.8; font-size: 0.98rem;">
              Perpustakaan Fakultas Teknik Universitas Sam Ratulangi berkomitmen penuh untuk mendukung percepatan penelitian, pengajaran, dan publikasi ilmiah civitas akademika. Bagi Bapak/Ibu Dosen yang memerlukan artikel jurnal ilmiah nasional/internasional, repositori riset khusus, maupun literatur acuan yang belum tersedia di portal Elibrary, silakan mengajukan permintaan melalui halaman layanan khusus.
            </p>
            <a href="request_jurnal.php" class="btn btn-accent btn-lg px-4 py-3 shadow-sm font-weight-bold" style="border-radius: 30px; font-size: 0.98rem;">
              <i class="mdi mdi-file-document-edit-outline mr-2"></i> Ajukan Request Jurnal Dosen
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Statistik Ringkas -->
  <section class="py-5">
    <div class="container">
      <div class="row text-center">
        <div class="col-md-4 mb-3">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px;">
            <i class="mdi mdi-book-open-page-variant mdi-36px text-primary"></i>
            <h3 class="font-weight-bold text-dark mt-1"><?= number_format($totalBukuFisik); ?>+</h3>
            <span class="text-muted small font-weight-bold">Buku Fisik Tersedia</span>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px;">
            <i class="mdi mdi-book-open-variant mdi-36px text-info"></i>
            <h3 class="font-weight-bold text-dark mt-1"><?= number_format($totalEbook); ?>+</h3>
            <span class="text-muted small font-weight-bold">E-Book Digital</span>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white" style="border-radius: 12px;">
            <i class="mdi mdi-school mdi-36px text-success"></i>
            <h3 class="font-weight-bold text-dark mt-1"><?= number_format($totalSkripsi); ?>+</h3>
            <span class="text-muted small font-weight-bold">Dokumen Skripsi</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Showcase Koleksi Terbaru (Buku Fisik & E-Book) -->
  <section class="py-5 bg-white" id="buku">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
          <h3 class="font-weight-bold text-dark mb-1"><i class="mdi mdi-bookmark-star-outline text-warning mr-1"></i>Koleksi Terbaru</h3>
          <p class="text-muted small mb-0">Rekomendasi bahan pustaka dan e-book akademik terkini</p>
        </div>
        <ul class="nav nav-pills mt-2 mt-sm-0" id="katalogTab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active font-weight-bold" id="tab-buku-link" data-toggle="pill" href="#tab-buku"><i class="mdi mdi-book-outline mr-1"></i>Buku Fisik</a>
          </li>
          <li class="nav-item">
            <a class="nav-link font-weight-bold" id="tab-ebook-link" data-toggle="pill" href="#tab-ebook"><i class="mdi mdi-book-open-variant mr-1"></i>E-Book Digital</a>
          </li>
        </ul>
      </div>

      <div class="tab-content" id="katalogTabContent">
        <!-- Tab 1: Buku Fisik -->
        <div class="tab-pane fade show active" id="tab-buku">
          <div class="row">
            <?php if (!empty($bukuFisikList)): ?>
              <?php foreach ($bukuFisikList as $buku): 
                $coverUrl = get_media_url($buku['cover'] ?? '', 'https://via.placeholder.com/300x400/1E3A8A/FFFFFF?text=Buku+Fisik');
              ?>
                <div class="col-lg-4 col-sm-6 mb-4">
                  <div class="card card-katalog h-100">
                    <img src="<?= htmlspecialchars($coverUrl); ?>" class="card-img-top cover-thumb" alt="Cover">
                    <div class="card-body p-3 d-flex flex-column">
                      <small class="text-muted d-block mb-1"><i class="mdi mdi-barcode mr-1"></i><?= htmlspecialchars($buku['isbn'] ?? '-'); ?></small>
                      <h6 class="font-weight-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($buku['judul'] ?? ''); ?>"><?= htmlspecialchars($buku['judul'] ?? 'Tanpa Judul'); ?></h6>
                      <p class="small text-muted mb-2 text-truncate">Penulis: <?= htmlspecialchars($buku['penulis'] ?? '-'); ?></p>
                      <div class="mt-auto d-flex justify-content-between align-items-center">
                        <span class="badge px-2 py-1" style="background-color: #FEF3C7; color: #92400E;">Stok: <?= $buku['stok'] ?? 0; ?></span>
                        <a href="detail.php?tipe=buku_fisik&id=<?= $buku['id']; ?>" class="btn btn-sm btn-outline-primary font-weight-bold">Detail</a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12 py-4 text-center text-muted">Belum ada koleksi buku fisik yang tersedia.</div>
            <?php endif; ?>
          </div>
          <div class="text-center mt-2">
            <a href="buku_fisik.php" class="btn btn-primary-custom px-4 py-2 rounded-pill font-weight-bold">Lihat Semua Buku Fisik <i class="mdi mdi-arrow-right ml-1"></i></a>
          </div>
        </div>

        <!-- Tab 2: E-Book dengan Tombol Detail & Unduh -->
        <div class="tab-pane fade" id="tab-ebook">
          <div class="row">
            <?php if (!empty($ebookList)): ?>
              <?php foreach ($ebookList as $eb): 
                $coverUrl = get_media_url($eb['cover'] ?? '', 'https://via.placeholder.com/300x400/0284C7/FFFFFF?text=E-Book+PDF');
              ?>
                <div class="col-lg-4 col-sm-6 mb-4">
                  <div class="card card-katalog h-100">
                    <img src="<?= htmlspecialchars($coverUrl); ?>" class="card-img-top cover-thumb" alt="Cover">
                    <div class="card-body p-3 d-flex flex-column">
                      <span class="badge badge-info mb-2 w-50"><i class="mdi mdi-file-pdf-box mr-1"></i>E-Book</span>
                      <h6 class="font-weight-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($eb['judul'] ?? ''); ?>"><?= htmlspecialchars($eb['judul'] ?? 'Tanpa Judul'); ?></h6>
                      <p class="small text-muted mb-2"><?= $eb['jml_halaman'] ?? 0; ?> Halaman</p>
                      <div class="mt-auto d-flex justify-content-between align-items-center">
                        <a href="detail.php?tipe=ebook&id=<?= $eb['id']; ?>" class="btn btn-sm btn-outline-primary font-weight-bold">Detail</a>
                        <a href="<?= htmlspecialchars($eb['link_download'] ?? '#'); ?>" target="_blank" class="btn btn-sm btn-accent font-weight-bold"><i class="mdi mdi-download mr-1"></i> Unduh</a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12 py-4 text-center text-muted">Belum ada koleksi e-book digital yang tersedia.</div>
            <?php endif; ?>
          </div>
          <div class="text-center mt-2">
            <a href="ebook.php" class="btn btn-primary-custom px-4 py-2 rounded-pill font-weight-bold">Lihat Semua E-Book <i class="mdi mdi-arrow-right ml-1"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Repositori Skripsi -->
  <section class="py-5 bg-light" id="skripsi">
    <div class="container">
      <div class="text-center mb-5">
        <h3 class="font-weight-bold text-dark">Repositori Skripsi Mahasiswa</h3>
        <p class="text-muted small">Pilih rumpun jurusan untuk menjelajahi karya ilmiah tugas akhir</p>
      </div>
      <div class="row">
        <div class="col-md-3 mb-3">
          <div class="card jurusan-card h-100 p-3">
            <div class="d-flex align-items-center mb-2">
              <i class="mdi mdi-cog-outline mdi-28px text-primary mr-2"></i>
              <h5 class="font-weight-bold text-dark mb-0" style="font-size: 1.05rem;">Teknik Mesin</h5>
            </div>
            <p class="small text-muted mb-3">S1 Teknik Mesin, S1 Teknik Industri</p>
            <a href="skripsi_jurusan.php?jurusan=Teknik+Mesin" class="small text-primary font-weight-bold mt-auto"><i class="mdi mdi-arrow-right-circle mr-1"></i>Lihat Skripsi</a>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card jurusan-card h-100 p-3" style="border-left-color: #F59E0B;">
            <div class="d-flex align-items-center mb-2">
              <i class="mdi mdi-lightning-bolt-outline mdi-28px text-warning mr-2"></i>
              <h5 class="font-weight-bold text-dark mb-0" style="font-size: 1.05rem;">Teknik Elektro</h5>
            </div>
            <p class="small text-muted mb-3">S1 Teknik Informatika, S1 Teknik Elektro</p>
            <a href="skripsi_jurusan.php?jurusan=Teknik+Elektro" class="small text-primary font-weight-bold mt-auto"><i class="mdi mdi-arrow-right-circle mr-1"></i>Lihat Skripsi</a>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card jurusan-card h-100 p-3" style="border-left-color: #10B981;">
            <div class="d-flex align-items-center mb-2">
              <i class="mdi mdi-office-building-outline mdi-28px text-success mr-2"></i>
              <h5 class="font-weight-bold text-dark mb-0" style="font-size: 1.05rem;">Teknik Sipil</h5>
            </div>
            <p class="small text-muted mb-3">S1 Teknik Sipil, S1 Teknik Lingkungan</p>
            <a href="skripsi_jurusan.php?jurusan=Teknik+Sipil" class="small text-primary font-weight-bold mt-auto"><i class="mdi mdi-arrow-right-circle mr-1"></i>Lihat Skripsi</a>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card jurusan-card h-100 p-3" style="border-left-color: #EF4444;">
            <div class="d-flex align-items-center mb-2">
              <i class="mdi mdi-compass-outline mdi-28px text-danger mr-2"></i>
              <h5 class="font-weight-bold text-dark mb-0" style="font-size: 1.05rem;">Arsitektur</h5>
            </div>
            <p class="small text-muted mb-3">S1 Arsitektur, S1 PWK</p>
            <a href="skripsi_jurusan.php?jurusan=Arsitektur" class="small text-primary font-weight-bold mt-auto"><i class="mdi mdi-arrow-right-circle mr-1"></i>Lihat Skripsi</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Fasilitas Perpustakaan -->
  <section class="py-5 bg-white" id="fasilitas">
    <div class="container">
      <div class="text-center mb-5">
        <h3 class="font-weight-bold text-dark"><i class="mdi mdi-domain text-primary mr-1"></i>Fasilitas Unggulan Perpustakaan</h3>
        <p class="text-muted small">Menyediakan sarana pendukung kenyamanan belajar dan riset pemustaka</p>
      </div>
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm p-4 h-100 bg-light" style="border-radius: 12px;">
            <i class="mdi mdi-wifi-check mdi-36px text-primary mb-2"></i>
            <h5 class="font-weight-bold text-dark">Akses High-Speed Wi-Fi</h5>
            <p class="small text-muted mb-0">Koneksi internet nirkabel berkecepatan tinggi yang dapat diakses di seluruh area perpustakaan.</p>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm p-4 h-100 bg-light" style="border-radius: 12px;">
            <i class="mdi mdi-desk mdi-36px text-info mb-2"></i>
            <h5 class="font-weight-bold text-dark">Ruang Diskusi & Qube</h5>
            <p class="small text-muted mb-0">Ruang privat ber-AC yang nyaman untuk kegiatan diskusi kelompok dan pengerjaan tugas bersama.</p>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm p-4 h-100 bg-light" style="border-radius: 12px;">
            <i class="mdi mdi-laptop mdi-36px text-success mb-2"></i>
            <h5 class="font-weight-bold text-dark">Anjungan Komputer Mandiri</h5>
            <p class="small text-muted mb-0">Fasilitas komputer OPAC untuk penelusuran katalog online dan pencarian cepat repositori.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Berita & Pengumuman -->
  <section class="py-5 bg-light" id="berita">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-weight-bold text-dark"><i class="mdi mdi-newspaper-variant-outline text-primary mr-1"></i>Berita & Pengumuman</h3>
        <a href="berita_all.php" class="btn btn-outline-secondary btn-sm font-weight-bold">Lihat Semua Berita</a>
      </div>
      <div class="row">
        <?php if (!empty($beritaList)): ?>
          <?php foreach ($beritaList as $berita): 
            $gambarUrl = get_media_url($berita['gambar'] ?? '', 'https://via.placeholder.com/400x200/1E3A8A/FFFFFF?text=Pengumuman');
          ?>
            <div class="col-md-4 mb-3">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
                <img src="<?= htmlspecialchars($gambarUrl); ?>" class="card-img-top" alt="Berita" style="height: 180px; object-fit: cover;">
                <div class="card-body p-3 d-flex flex-column">
                  <small class="text-primary font-weight-bold mb-1"><i class="mdi mdi-calendar mr-1"></i><?= htmlspecialchars(substr($berita['created_at'] ?? date('Y-m-d'), 0, 10)); ?></small>
                  <h6 class="font-weight-bold text-dark mt-1 text-truncate" title="<?= htmlspecialchars($berita['judul'] ?? ''); ?>"><?= htmlspecialchars($berita['judul'] ?? 'Berita'); ?></h6>
                  <p class="small text-muted mb-3 flex-grow-1"><?= htmlspecialchars(substr(strip_tags($berita['isi'] ?? ''), 0, 90)); ?>...</p>
                  <a href="berita_selengkapnya.php?id=<?= $berita['id']; ?>" class="small font-weight-bold text-primary">Baca Selengkapnya &rarr;</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 py-4 text-center text-muted">Belum ada berita atau pengumuman terbaru.</div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>