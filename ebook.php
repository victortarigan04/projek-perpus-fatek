<?php
require_once 'admin/koneksi.php';

/* ---------- Helper ---------- */

// Resolusi path media yang tersimpan di folder admin/
function get_media_url($path, $default = '') {
    if (empty($path)) return $default;
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    $fullPath = (strpos($path, 'admin/') === 0) ? $path : 'admin/' . $path;
    if (file_exists(__DIR__ . '/' . $fullPath)) {
        return $fullPath;
    }
    return $default;
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------- Pengaturan Koleksi ---------- */
$koleksi = 'ebook';
$items   = [];

try {
    $docs = $db->collection($koleksi)->orderBy('created_at', 'DESC')->documents();
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $d = $doc->data();
            $d['id'] = $doc->id();
            $items[] = $d;
        }
    }
} catch (Throwable $e) {
    $items = [];
}

$total = count($items);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Koleksi E-Book Digital - Elibrary Fatek UNSRAT</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
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
    }

    .top-bar { background-color: var(--primary-color); color: #E2E8F0; font-size: 0.85rem; }
    .navbar-light-custom { background-color: #FFFFFF !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }

    .nav-link { color: var(--text-dark) !important; font-weight: 500; padding: 0.5rem 1rem !important; }
    .nav-link:hover, .nav-item.active .nav-link { color: var(--secondary-color) !important; }

    .btn-accent { background-color: var(--accent-color); color: #FFFFFF; font-weight: 600; border: none; }
    .btn-accent:hover { background-color: #D97706; color: #FFFFFF; }

    .btn-primary-custom { background-color: var(--primary-color); color: #FFFFFF; border: none; font-weight: 500; }
    .btn-primary-custom:hover { background-color: #172554; color: #FFFFFF; }

    .page-banner { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); padding: 40px 0 70px; color: #FFFFFF; }
    .search-box-wrapper { margin-top: -50px; position: relative; z-index: 20; }
    .search-card { background: #FFFFFF; border-radius: 12px; border: none; box-shadow: 0 15px 35px rgba(30, 58, 138, 0.1); }

    .card-katalog { border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; transition: all 0.3s ease; }
    .card-katalog:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07) !important; }
    .cover-thumb { height: 220px; object-fit: cover; border-top-left-radius: 12px; border-top-right-radius: 12px; }

    footer { background-color: var(--primary-color); color: #94A3B8; }
    footer a { color: #CBD5E1; text-decoration: none; }
  </style>
</head>
<body>

  <!-- Include Header Navigasi -->
  <?php include 'header.php'; ?>

  <!-- Banner Judul Halaman -->
  <div class="page-banner">
    <div class="container text-center">
      <h2 class="font-weight-bold mb-1"><i class="mdi mdi-book-open-variant mr-2"></i>Koleksi E-Book Digital</h2>
      <p class="mb-0 small" style="opacity: 0.9;">Kumpulan buku elektronik akademik yang dapat diunduh secara daring</p>
    </div>
  </div>

  <!-- Form Pencarian (khusus kategori ini) -->
  <div class="container search-box-wrapper">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card search-card p-3 p-md-4">
          <form action="pencarian.php" method="get">
            <input type="hidden" name="kategori" value="ebook">
            <div class="row no-gutters align-items-center">
              <div class="col-md-10 mb-2 mb-md-0 pr-md-2">
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text bg-light border-light text-muted"><i class="mdi mdi-magnify mdi-24px"></i></span></div>
                  <input type="text" name="q" class="form-control form-control-lg border-light bg-light" placeholder="Cari judul, penulis, atau ISBN e-book..." style="font-size: 0.95rem;" required>
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

  <!-- Daftar Koleksi -->
  <section class="py-5">
    <div class="container">

      <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
          <h5 class="font-weight-bold text-dark mb-1">Koleksi Terbaru</h5>
          <p class="text-muted small mb-0">
            Total <span class="font-weight-bold text-primary"><?= number_format($total); ?></span> koleksi e-book digital tersedia
          </p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm font-weight-bold mt-2 mt-sm-0"><i class="mdi mdi-arrow-left mr-1"></i> Kembali ke Beranda</a>
      </div>

      <?php if (empty($items)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm text-muted">
          <i class="mdi mdi-book-off-outline mdi-48px"></i>
          <p class="mb-0">Belum ada koleksi e-book digital yang tersedia.</p>
        </div>
      <?php else: ?>
        <div class="row" id="katalogGrid">
          <?php foreach ($items as $it):
            $coverUrl = get_media_url($it['cover'] ?? '', 'https://via.placeholder.com/300x400/0284C7/FFFFFF?text=E-Book+PDF');
            $downloadLink = $it['link_download'] ?? ($it['file_url'] ?? '');
          ?>
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-card">
              <div class="card card-katalog h-100">
                <img src="<?= h($coverUrl); ?>" class="card-img-top cover-thumb" alt="Cover">
                <div class="card-body p-3 d-flex flex-column">
                  <span class="badge badge-info mb-2" style="width: fit-content;"><i class="mdi mdi-file-pdf-box mr-1"></i>E-Book</span>
                  <h6 class="font-weight-bold text-dark mb-1 text-truncate" title="<?= h($it['judul'] ?? ''); ?>"><?= h($it['judul'] ?? 'Tanpa Judul'); ?></h6>
                  <p class="small text-muted mb-1 text-truncate">Penulis: <?= h($it['penulis'] ?? ($it['pengarang'] ?? '-')); ?></p>
                  <p class="small text-muted mb-2"><?= (int)($it['jml_halaman'] ?? 0); ?> Halaman</p>
                  <div class="mt-auto d-flex justify-content-between align-items-center">
                    <a href="detail.php?tipe=ebook&id=<?= h($it['id']); ?>" class="btn btn-sm btn-outline-primary font-weight-bold">Detail</a>
                    <?php if (!empty($downloadLink)): ?>
                      <a href="<?= h($downloadLink); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-accent font-weight-bold"><i class="mdi mdi-download mr-1"></i> Unduh</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Tombol Load More -->
        <div id="loadMoreWrapper" class="text-center mt-3 mb-4" style="display: none;">
          <button id="btnLoadMore" class="btn btn-primary-custom btn-lg font-weight-bold px-4 shadow-sm" style="font-size: 0.95rem;">
            <i class="mdi mdi-refresh mr-1"></i> Tampilkan Lebih Banyak (Load More)
          </button>
        </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function() {
      var limit = 12;
      var $cards = $('.item-card');

      if ($cards.length > limit) {
        $cards.slice(limit).hide();
        $('#loadMoreWrapper').show();
      }

      $('#btnLoadMore').on('click', function() {
        var $hidden = $('.item-card:hidden');
        $hidden.slice(0, limit).fadeIn();

        if ($('.item-card:hidden').length === 0) {
          $('#loadMoreWrapper').fadeOut();
        }
      });
    });
  </script>
</body>
</html>