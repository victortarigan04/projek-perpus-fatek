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

function tanggal_indo($t) {
    $ts = strtotime((string)$t);
    if (!$ts) return (string)$t;
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/* ---------- Pengaturan ---------- */
$perPage = 9;
$page    = max(1, (int)($_GET['page'] ?? 1));

/* ---------- Hitung total & ambil data halaman ini ---------- */
$total = 0;
try {
    $total = iterator_count(
        $db->collection('berita')->orderBy('created_at', 'DESC')->select(['created_at'])->documents()
    );
} catch (Throwable $e) {
    try {
        $total = iterator_count($db->collection('berita')->orderBy('created_at', 'DESC')->documents());
    } catch (Throwable $e2) {
        $total = 0;
    }
}

$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$beritaList = [];
try {
    $docs = $db->collection('berita')
        ->orderBy('created_at', 'DESC')
        ->offset($offset)
        ->limit($perPage)
        ->documents();
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $d = $doc->data();
            $d['id'] = $doc->id();
            $beritaList[] = $d;
        }
    }
} catch (Throwable $e) {
    $beritaList = [];
}

$dari   = $total > 0 ? $offset + 1 : 0;
$sampai = $offset + count($beritaList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Berita &amp; Pengumuman - Elibrary Fatek UNSRAT</title>

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

    .page-banner { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); padding: 45px 0; color: #FFFFFF; }

    .card-berita { border: none; border-radius: 12px; overflow: hidden; background: #FFFFFF; transition: all 0.3s ease; }
    .card-berita:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.09) !important; }
    .card-berita img { height: 200px; object-fit: cover; }
    .berita-judul { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; min-height: 3em; }

    .page-link { color: var(--primary-color); }
    .page-item.active .page-link { background-color: var(--primary-color); border-color: var(--primary-color); color: #FFFFFF; }

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
      <h2 class="font-weight-bold mb-1"><i class="mdi mdi-newspaper-variant-outline mr-2"></i>Berita &amp; Pengumuman</h2>
      <p class="mb-0 small" style="opacity: 0.9;">Informasi dan kabar terbaru dari Perpustakaan Fakultas Teknik UNSRAT</p>
    </div>
  </div>

  <section class="py-5">
    <div class="container">

      <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <p class="text-muted small mb-0">
          <?php if ($total > 0): ?>
            Menampilkan <?= number_format($dari); ?>&ndash;<?= number_format($sampai); ?> dari <?= number_format($total); ?> berita
          <?php else: ?>
            Belum ada berita
          <?php endif; ?>
        </p>
        <a href="index.php" class="btn btn-outline-secondary btn-sm font-weight-bold mt-2 mt-sm-0"><i class="mdi mdi-arrow-left mr-1"></i> Kembali ke Beranda</a>
      </div>

      <?php if (empty($beritaList)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm text-muted">
          <i class="mdi mdi-newspaper-remove mdi-48px"></i>
          <p class="mb-0">Belum ada berita atau pengumuman terbaru.</p>
        </div>
      <?php else: ?>
        <div class="row">
          <?php foreach ($beritaList as $berita):
            $gambarUrl = get_media_url($berita['gambar'] ?? '', 'https://via.placeholder.com/600x300/1E3A8A/FFFFFF?text=Pengumuman');
            $ringkas   = trim(preg_replace('/\s+/u', ' ', strip_tags($berita['isi'] ?? '')));
            if (mb_strlen($ringkas, 'UTF-8') > 120) {
                $ringkas = mb_substr($ringkas, 0, 120, 'UTF-8') . '...';
            }
          ?>
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="card card-berita shadow-sm h-100">
                <a href="berita_selengkapnya.php?id=<?= h($berita['id']); ?>">
                  <img src="<?= h($gambarUrl); ?>" class="card-img-top" alt="<?= h($berita['judul'] ?? 'Berita'); ?>">
                </a>
                <div class="card-body p-3 d-flex flex-column">
                  <small class="text-primary font-weight-bold mb-1"><i class="mdi mdi-calendar mr-1"></i><?= h(tanggal_indo($berita['created_at'] ?? '')); ?></small>
                  <h6 class="font-weight-bold text-dark mt-1 berita-judul" title="<?= h($berita['judul'] ?? ''); ?>">
                    <a href="berita_selengkapnya.php?id=<?= h($berita['id']); ?>" class="text-dark" style="text-decoration: none;"><?= h($berita['judul'] ?? 'Berita'); ?></a>
                  </h6>
                  <p class="small text-muted mb-3 flex-grow-1"><?= h($ringkas); ?></p>
                  <a href="berita_selengkapnya.php?id=<?= h($berita['id']); ?>" class="small font-weight-bold text-primary">Baca Selengkapnya &rarr;</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1):
          $awal  = max(1, $page - 2);
          $akhir = min($totalPages, $page + 2);
        ?>
          <nav class="mt-3">
            <ul class="pagination justify-content-center flex-wrap">
              <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="berita_all.php?page=<?= $page - 1; ?>">&laquo;</a>
              </li>
              <?php if ($awal > 1): ?>
                <li class="page-item"><a class="page-link" href="berita_all.php?page=1">1</a></li>
                <?php if ($awal > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
              <?php endif; ?>
              <?php for ($i = $awal; $i <= $akhir; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="berita_all.php?page=<?= $i; ?>"><?= $i; ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($akhir < $totalPages): ?>
                <?php if ($akhir < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="berita_all.php?page=<?= $totalPages; ?>"><?= $totalPages; ?></a></li>
              <?php endif; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="berita_all.php?page=<?= $page + 1; ?>">&raquo;</a>
              </li>
            </ul>
            <p class="text-center small text-muted mb-0">Halaman <?= $page; ?> dari <?= $totalPages; ?></p>
          </nav>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </section>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
