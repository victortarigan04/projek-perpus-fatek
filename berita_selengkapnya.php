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

/**
 * Tampilkan isi berita dengan aman.
 * - Teks biasa  : baris baru diubah menjadi <br>.
 * - Berisi HTML : hanya tag dan atribut yang aman yang dipertahankan.
 */
function bersihkan_isi($isi) {
    $isi = (string)$isi;
    if ($isi === strip_tags($isi)) {
        return nl2br(h($isi));
    }

    if (!class_exists('DOMDocument')) {
        return nl2br(h(strip_tags($isi)));
    }

    $allowed = '<p><br><b><strong><i><em><u><s><ul><ol><li><h2><h3><h4><h5><blockquote><a><img><table><thead><tbody><tr><th><td><span><div>';
    $html = strip_tags($isi, $allowed);

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) return '';

    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('.//*', $root) as $el) {
        $tag = strtolower($el->nodeName);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $val  = trim($attr->nodeValue);
            $ok   = false;

            if ($tag === 'a' && $name === 'href' && preg_match('#^(https?:|mailto:|/|\#)#i', $val)) $ok = true;
            if ($tag === 'img' && $name === 'src' && !preg_match('#^\s*(javascript|data|vbscript):#i', $val)) $ok = true;
            if ($tag === 'img' && $name === 'alt') $ok = true;

            if (!$ok) $el->removeAttribute($attr->nodeName);
        }

        if ($tag === 'a')     { $el->setAttribute('target', '_blank'); $el->setAttribute('rel', 'noopener noreferrer'); }
        if ($tag === 'img')   { $el->setAttribute('class', 'img-fluid rounded my-2'); }
        if ($tag === 'table') { $el->setAttribute('class', 'table table-bordered'); }
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return $out;
}

/* ---------- Ambil data berita ---------- */
$id     = $_GET['id'] ?? '';
$berita = null;

if (preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
    try {
        $snap = $db->collection('berita')->document($id)->snapshot();
        if ($snap->exists()) {
            $berita = $snap->data();
        }
    } catch (Exception $e) {
        $berita = null;
    }
}

if (!$berita) {
    http_response_code(404);
}

/* ---------- Berita lainnya ---------- */
$lainnya = [];
if ($berita) {
    try {
        $docs = $db->collection('berita')->orderBy('created_at', 'DESC')->limit(4)->documents();
        foreach ($docs as $doc) {
            if (!$doc->exists() || $doc->id() === $id) continue;
            $d = $doc->data();
            $d['id'] = $doc->id();
            $lainnya[] = $d;
            if (count($lainnya) >= 3) break;
        }
    } catch (Exception $e) {
        $lainnya = [];
    }
}

/* ---------- URL halaman ini (untuk tombol bagikan) ---------- */
$skema   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlIni  = $skema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
$judul   = $berita['judul'] ?? '';
$gambar  = $berita ? get_media_url($berita['gambar'] ?? '', '') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $berita ? h($judul) . ' - ' : ''; ?>Berita Elibrary Fatek UNSRAT</title>

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

    .artikel-card { background: #FFFFFF; border: none; border-radius: 14px; box-shadow: 0 8px 24px rgba(30, 58, 138, 0.07); overflow: hidden; }
    .artikel-hero { width: 100%; max-height: 420px; object-fit: cover; }
    .artikel-isi { font-size: 1rem; line-height: 1.9; color: #334155; text-align: justify; overflow-wrap: anywhere; }
    .artikel-isi p { margin-bottom: 1rem; }
    .artikel-isi h2, .artikel-isi h3, .artikel-isi h4 { font-weight: 600; margin: 1.5rem 0 0.75rem; color: var(--text-dark); }

    .card-berita { border: none; border-radius: 12px; overflow: hidden; background: #FFFFFF; transition: all 0.3s ease; }
    .card-berita:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.09) !important; }
    .card-berita img { height: 160px; object-fit: cover; }
    .berita-judul { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; min-height: 3em; }

    .breadcrumb { background: transparent; padding: 0; font-size: 0.88rem; }

    footer { background-color: var(--primary-color); color: #94A3B8; }
    footer a { color: #CBD5E1; text-decoration: none; }
  </style>
</head>
<body>

  <!-- Include Header Navigasi -->
  <?php include 'header.php'; ?>

  <section class="py-5">
    <div class="container">

      <?php if (!$berita): ?>
        <!-- Berita tidak ditemukan -->
        <div class="text-center py-5 bg-white rounded shadow-sm">
          <i class="mdi mdi-newspaper-remove mdi-48px text-muted"></i>
          <h5 class="font-weight-bold text-dark mt-2">Berita tidak ditemukan</h5>
          <p class="small text-muted mb-3">Berita yang Anda cari mungkin sudah dihapus atau tautannya tidak valid.</p>
          <a href="berita_all.php" class="btn btn-primary-custom px-4"><i class="mdi mdi-newspaper-variant-outline mr-1"></i> Lihat Semua Berita</a>
        </div>

      <?php else: ?>

        <div class="row justify-content-center">
          <div class="col-lg-9">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="berita_all.php">Berita</a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width: 260px;"><?= h($judul); ?></li>
              </ol>
            </nav>

            <article class="card artikel-card mb-4">
              <?php if ($gambar !== ''): ?>
                <img src="<?= h($gambar); ?>" alt="<?= h($judul); ?>" class="artikel-hero">
              <?php endif; ?>

              <div class="p-3 p-md-5">
                <small class="text-primary font-weight-bold d-block mb-2">
                  <i class="mdi mdi-calendar mr-1"></i><?= h(tanggal_indo($berita['created_at'] ?? '')); ?>
                </small>
                <h2 class="font-weight-bold text-dark mb-4" style="line-height: 1.4;"><?= h($judul); ?></h2>

                <div class="artikel-isi">
                  <?= bersihkan_isi($berita['isi'] ?? ''); ?>
                </div>

                <hr class="my-4">

                <!-- Bagikan -->
                <div class="d-flex align-items-center flex-wrap">
                  <span class="small font-weight-bold text-muted mr-3 mb-2">Bagikan:</span>
                  <a href="https://wa.me/?text=<?= rawurlencode($judul . ' - ' . $urlIni); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success mr-2 mb-2">
                    <i class="mdi mdi-whatsapp mr-1"></i> WhatsApp
                  </a>
                  <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($urlIni); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary mr-2 mb-2">
                    <i class="mdi mdi-facebook mr-1"></i> Facebook
                  </a>
                  <button type="button" id="btnSalin" class="btn btn-sm btn-outline-secondary mb-2">
                    <i class="mdi mdi-link-variant mr-1"></i> <span id="teksSalin">Salin Tautan</span>
                  </button>
                </div>
              </div>
            </article>

            <a href="berita_all.php" class="btn btn-outline-secondary btn-sm font-weight-bold mb-5">
              <i class="mdi mdi-arrow-left mr-1"></i> Kembali ke Semua Berita
            </a>

          </div>
        </div>

        <!-- Berita lainnya -->
        <?php if (!empty($lainnya)): ?>
          <div class="mb-3">
            <h5 class="font-weight-bold text-dark"><i class="mdi mdi-newspaper-variant-outline text-primary mr-1"></i>Berita Lainnya</h5>
          </div>
          <div class="row">
            <?php foreach ($lainnya as $b):
              $img = get_media_url($b['gambar'] ?? '', 'https://via.placeholder.com/500x250/1E3A8A/FFFFFF?text=Pengumuman');
            ?>
              <div class="col-md-4 mb-4">
                <div class="card card-berita shadow-sm h-100">
                  <a href="berita_selengkapnya.php?id=<?= h($b['id']); ?>">
                    <img src="<?= h($img); ?>" class="card-img-top" alt="<?= h($b['judul'] ?? 'Berita'); ?>">
                  </a>
                  <div class="card-body p-3 d-flex flex-column">
                    <small class="text-primary font-weight-bold mb-1"><i class="mdi mdi-calendar mr-1"></i><?= h(tanggal_indo($b['tanggal'] ?? '')); ?></small>
                    <h6 class="font-weight-bold mt-1 berita-judul" title="<?= h($b['judul'] ?? ''); ?>">
                      <a href="berita_selengkapnya.php?id=<?= h($b['id']); ?>" class="text-dark" style="text-decoration: none;"><?= h($b['judul'] ?? 'Berita'); ?></a>
                    </h6>
                    <a href="berita_selengkapnya.php?id=<?= h($b['id']); ?>" class="small font-weight-bold text-primary mt-auto">Baca Selengkapnya &rarr;</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </section>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var btn = document.getElementById('btnSalin');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var teks = document.getElementById('teksSalin');
        var selesai = function () {
          teks.textContent = 'Tautan Disalin!';
          setTimeout(function () { teks.textContent = 'Salin Tautan'; }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(window.location.href).then(selesai);
        } else {
          var t = document.createElement('textarea');
          t.value = window.location.href;
          document.body.appendChild(t);
          t.select();
          document.execCommand('copy');
          document.body.removeChild(t);
          selesai();
        }
      });
    })();
  </script>
</body>
</html>
