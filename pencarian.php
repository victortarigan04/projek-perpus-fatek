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

// Beri tanda <mark> pada kata kunci (aman terhadap HTML entity)
function highlight($text, array $terms) {
    $safe = h($text);
    if (empty($terms)) return $safe;

    usort($terms, function ($a, $b) { return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8'); });
    $alt = implode('|', array_map(function ($t) { return preg_quote(h($t), '/'); }, $terms));

    return preg_replace_callback('/(&[a-z#0-9]+;)|(' . $alt . ')/iu', function ($m) {
        return (isset($m[2]) && $m[2] !== '') ? '<mark class="px-0">' . $m[2] . '</mark>' : $m[0];
    }, $safe);
}

function url_pencarian(array $params) {
    return 'pencarian.php?' . http_build_query($params);
}

/* ---------- Konfigurasi koleksi yang dicari ---------- */
$konfigurasi = [
    'buku_fisik' => [
        'label'  => 'Buku Fisik',
        'icon'   => 'mdi-book-outline',
        'fields' => ['isbn', 'judul', 'penulis', 'penerbit', 'deskripsi'],
    ],
    'ebook' => [
        'label'  => 'E-Book Digital',
        'icon'   => 'mdi-book-open-variant',
        'fields' => ['isbn', 'judul', 'penulis', 'penerbit', 'deskripsi'],
    ],
    'skripsi' => [
        'label'  => 'Skripsi Mahasiswa',
        'icon'   => 'mdi-school-outline',
        'fields' => ['judul', 'nama_mhs', 'jurusan', 'prodi', 'pembimbing_1', 'pembimbing_2', 'abstrak'],
    ],
];

/* ---------- Ambil parameter ---------- */
$q        = trim($_GET['q'] ?? '');
$kategori = $_GET['kategori'] ?? 'semua';
if ($kategori !== 'semua' && !isset($konfigurasi[$kategori])) {
    $kategori = 'semua';
}
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$terms = array_values(array_unique(array_filter(
    preg_split('/\s+/u', mb_strtolower($q, 'UTF-8')),
    function ($t) { return $t !== ''; }
)));

$errMsg  = '';
$results = [];
$counts  = ['buku_fisik' => 0, 'ebook' => 0, 'skripsi' => 0];

/* ---------- Proses pencarian (LIKE '%kata%', semua kata harus cocok) ---------- */
if ($q !== '') {
    if (mb_strlen($q, 'UTF-8') < 2) {
        $errMsg = 'Masukkan minimal 2 karakter untuk pencarian.';
    } else {
        foreach ($konfigurasi as $koleksi => $cfg) {
            try {
                $docs = $db->collection($koleksi)->documents();
                foreach ($docs as $doc) {
                    if (!$doc->exists()) continue;
                    $d = $doc->data();

                    // Gabungkan semua kolom yang dicari menjadi satu teks
                    $parts = [];
                    foreach ($cfg['fields'] as $f) {
                        $parts[] = (string)($d[$f] ?? '');
                    }
                    $haystack = mb_strtolower(implode(' ', $parts), 'UTF-8');

                    $cocok = true;
                    foreach ($terms as $t) {
                        if (mb_strpos($haystack, $t) === false) { $cocok = false; break; }
                    }
                    if (!$cocok) continue;

                    // Skor relevansi: kecocokan di judul diprioritaskan
                    $judul = mb_strtolower((string)($d['judul'] ?? ''), 'UTF-8');
                    $score = 1;
                    $semuaDiJudul = true;
                    foreach ($terms as $t) {
                        if (mb_strpos($judul, $t) !== false) { $score += 1; } else { $semuaDiJudul = false; }
                    }
                    if ($semuaDiJudul) $score += 3;

                    $d['id']      = $doc->id();
                    $d['_tipe']   = $koleksi;
                    $d['_score']  = $score;
                    $results[]    = $d;
                    $counts[$koleksi]++;
                }
            } catch (Exception $e) {
                // koleksi gagal dibaca, lanjut ke koleksi berikutnya
            }
        }

        // Urutkan: relevansi, lalu terbaru
        usort($results, function ($a, $b) {
            if ($a['_score'] !== $b['_score']) return $b['_score'] - $a['_score'];
            return (int)($b['created_at'] ?? 0) - (int)($a['created_at'] ?? 0);
        });
    }
}

$totalSemua = array_sum($counts);

// Terapkan filter kategori
if ($kategori !== 'semua') {
    $results = array_values(array_filter($results, function ($r) use ($kategori) {
        return $r['_tipe'] === $kategori;
    }));
}

// Pagination
$totalHasil  = count($results);
$totalPages  = max(1, (int)ceil($totalHasil / $perPage));
$page        = min($page, $totalPages);
$tampil      = array_slice($results, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $q !== '' ? 'Pencarian: ' . h($q) . ' - ' : ''; ?>Elibrary Fatek UNSRAT</title>

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

    .card-hasil { border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; transition: all 0.25s ease; }
    .card-hasil:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07) !important; }
    .cover-hasil { width: 100px; height: 140px; object-fit: cover; border-radius: 8px; }
    .icon-skripsi {
      width: 100px; height: 140px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #10B981 0%, #047857 100%); color: #FFFFFF;
    }

    .kategori-pills .nav-link { border-radius: 30px; font-size: 0.88rem; font-weight: 600; margin-right: 6px; margin-bottom: 6px; background: #FFFFFF; border: 1px solid #E2E8F0; }
    .kategori-pills .nav-link.active { background-color: var(--primary-color) !important; border-color: var(--primary-color); color: #FFFFFF !important; }

    mark { background-color: #FEF3C7; color: inherit; border-radius: 3px; }

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
      <h2 class="font-weight-bold mb-1"><i class="mdi mdi-magnify mr-2"></i>Pencarian Koleksi</h2>
      <p class="mb-0 small" style="opacity: 0.9;">Telusuri buku fisik, e-book digital, dan skripsi mahasiswa Fakultas Teknik UNSRAT</p>
    </div>
  </div>

  <!-- Form Pencarian -->
  <div class="container search-box-wrapper">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card search-card p-3 p-md-4">
          <form action="pencarian.php" method="get">
            <div class="row no-gutters align-items-center">
              <div class="col-md-3 mb-2 mb-md-0 pr-md-2">
                <select name="kategori" class="form-control form-control-lg border-light bg-light" style="font-size: 0.95rem;">
                  <option value="semua" <?= $kategori === 'semua' ? 'selected' : ''; ?>>Semua Katalog</option>
                  <option value="buku_fisik" <?= $kategori === 'buku_fisik' ? 'selected' : ''; ?>>Buku Fisik</option>
                  <option value="ebook" <?= $kategori === 'ebook' ? 'selected' : ''; ?>>E-Book Digital</option>
                  <option value="skripsi" <?= $kategori === 'skripsi' ? 'selected' : ''; ?>>Skripsi Mahasiswa</option>
                </select>
              </div>
              <div class="col-md-7 mb-2 mb-md-0 pr-md-2">
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text bg-light border-light text-muted"><i class="mdi mdi-magnify mdi-24px"></i></span></div>
                  <input type="text" name="q" value="<?= h($q); ?>" class="form-control form-control-lg border-light bg-light" placeholder="Ketik Judul, Penulis, ISBN, atau Nama Mahasiswa..." style="font-size: 0.95rem;" required>
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

  <!-- Hasil Pencarian -->
  <section class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">

          <?php if ($q === ''): ?>
            <div class="text-center py-5 text-muted">
              <i class="mdi mdi-text-search mdi-48px"></i>
              <p class="mb-0">Ketik kata kunci pada kotak pencarian di atas untuk mulai menelusuri koleksi.</p>
            </div>

          <?php elseif ($errMsg !== ''): ?>
            <div class="alert alert-warning"><i class="mdi mdi-alert-circle-outline mr-1"></i><?= h($errMsg); ?></div>

          <?php else: ?>

            <!-- Ringkasan & Filter Kategori -->
            <div class="mb-3">
              <h5 class="font-weight-bold text-dark mb-1">Hasil pencarian untuk &ldquo;<?= h($q); ?>&rdquo;</h5>
              <p class="text-muted small mb-3"><?= number_format($totalSemua); ?> koleksi ditemukan</p>

              <ul class="nav kategori-pills">
                <li class="nav-item">
                  <a class="nav-link <?= $kategori === 'semua' ? 'active' : 'text-dark'; ?>" href="<?= h(url_pencarian(['q' => $q, 'kategori' => 'semua'])); ?>">
                    Semua <span class="badge badge-light ml-1"><?= $totalSemua; ?></span>
                  </a>
                </li>
                <?php foreach ($konfigurasi as $kode => $cfg): ?>
                  <li class="nav-item">
                    <a class="nav-link <?= $kategori === $kode ? 'active' : 'text-dark'; ?>" href="<?= h(url_pencarian(['q' => $q, 'kategori' => $kode])); ?>">
                      <i class="mdi <?= $cfg['icon']; ?> mr-1"></i><?= h($cfg['label']); ?>
                      <span class="badge badge-light ml-1"><?= $counts[$kode]; ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <?php if (empty($tampil)): ?>
              <div class="text-center py-5 bg-white rounded shadow-sm">
                <i class="mdi mdi-book-search-outline mdi-48px text-muted"></i>
                <h6 class="font-weight-bold text-dark mt-2">Koleksi tidak ditemukan</h6>
                <p class="small text-muted mb-0">Coba gunakan kata kunci yang lebih singkat atau ganti kategori pencarian.</p>
              </div>
            <?php else: ?>

              <?php foreach ($tampil as $r):
                $tipe = $r['_tipe'];
              ?>
                <div class="card card-hasil shadow-sm mb-3">
                  <div class="card-body p-3">
                    <div class="d-flex">

                      <!-- Cover / ikon -->
                      <div class="mr-3 flex-shrink-0">
                        <?php if ($tipe === 'skripsi'): ?>
                          <div class="icon-skripsi"><i class="mdi mdi-school mdi-48px"></i></div>
                        <?php else:
                          $placeholder = $tipe === 'ebook'
                            ? 'https://via.placeholder.com/300x400/0284C7/FFFFFF?text=E-Book+PDF'
                            : 'https://via.placeholder.com/300x400/1E3A8A/FFFFFF?text=Buku+Fisik';
                          $coverUrl = get_media_url($r['cover'] ?? '', $placeholder);
                        ?>
                          <img src="<?= h($coverUrl); ?>" alt="Cover" class="cover-hasil">
                        <?php endif; ?>
                      </div>

                      <!-- Isi -->
                      <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">

                        <?php if ($tipe === 'buku_fisik'): ?>
                          <div class="mb-1">
                            <span class="badge badge-primary mr-1"><i class="mdi mdi-book-outline mr-1"></i>Buku Fisik</span>
                            <small class="text-muted"><i class="mdi mdi-barcode mr-1"></i><?= highlight($r['isbn'] ?? '-', $terms); ?></small>
                          </div>
                          <h6 class="font-weight-bold text-dark mb-1"><?= highlight($r['judul'] ?? 'Tanpa Judul', $terms); ?></h6>
                          <p class="small text-muted mb-1">Penulis: <?= highlight($r['penulis'] ?? '-', $terms); ?> &middot; Penerbit: <?= highlight($r['penerbit'] ?? '-', $terms); ?></p>
                          <p class="small text-muted mb-2"><?= (int)($r['jml_halaman'] ?? 0); ?> Halaman</p>
                          <div class="mt-auto d-flex justify-content-between align-items-center flex-wrap">
                            <?php $stok = (int)($r['stok'] ?? 0); ?>
                            <?php if ($stok > 0): ?>
                              <span class="badge px-2 py-1" style="background-color: #FEF3C7; color: #92400E;">Stok: <?= $stok; ?></span>
                            <?php else: ?>
                              <span class="badge badge-danger px-2 py-1">Stok habis</span>
                            <?php endif; ?>
                            <a href="detail_buku.php?id=<?= h($r['id']); ?>" class="btn btn-sm btn-outline-primary font-weight-bold">Detail</a>
                          </div>

                        <?php elseif ($tipe === 'ebook'): ?>
                          <div class="mb-1">
                            <span class="badge badge-info mr-1"><i class="mdi mdi-file-pdf-box mr-1"></i>E-Book</span>
                            <small class="text-muted"><i class="mdi mdi-barcode mr-1"></i><?= highlight($r['isbn'] ?? '-', $terms); ?></small>
                          </div>
                          <h6 class="font-weight-bold text-dark mb-1"><?= highlight($r['judul'] ?? 'Tanpa Judul', $terms); ?></h6>
                          <p class="small text-muted mb-1">Penulis: <?= highlight($r['penulis'] ?? '-', $terms); ?> &middot; Penerbit: <?= highlight($r['penerbit'] ?? '-', $terms); ?></p>
                          <p class="small text-muted mb-2"><?= (int)($r['jml_halaman'] ?? 0); ?> Halaman</p>
                          <div class="mt-auto d-flex justify-content-between align-items-center flex-wrap">
                            <a href="detail_ebook.php?id=<?= h($r['id']); ?>" class="btn btn-sm btn-outline-primary font-weight-bold">Detail</a>
                            <?php if (!empty($r['link_download'])): ?>
                              <a href="<?= h($r['link_download']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-accent font-weight-bold"><i class="mdi mdi-download mr-1"></i> Unduh</a>
                            <?php endif; ?>
                          </div>

                        <?php else: /* skripsi */
                          $pdfUrl   = get_media_url($r['file_pdf'] ?? '', '');
                          $abstrak  = trim(strip_tags($r['abstrak'] ?? ''));
                          $ringkas  = mb_strlen($abstrak, 'UTF-8') > 200 ? mb_substr($abstrak, 0, 200, 'UTF-8') . '...' : $abstrak;
                          $collapse = 'abs-' . preg_replace('/[^a-zA-Z0-9]/', '', $r['id']);
                        ?>
                          <div class="mb-1">
                            <span class="badge badge-success mr-1"><i class="mdi mdi-school-outline mr-1"></i>Skripsi</span>
                            <?php if (!empty($r['jurusan'])): ?>
                              <small class="text-muted"><?= highlight($r['jurusan'], $terms); ?><?= !empty($r['prodi']) ? ' &middot; ' . highlight($r['prodi'], $terms) : ''; ?></small>
                            <?php endif; ?>
                          </div>
                          <h6 class="font-weight-bold text-dark mb-1"><?= highlight($r['judul'] ?? 'Tanpa Judul', $terms); ?></h6>
                          <p class="small text-muted mb-1"><i class="mdi mdi-account-outline mr-1"></i>Mahasiswa: <?= highlight($r['nama_mhs'] ?? '-', $terms); ?></p>
                          <p class="small text-muted mb-2">
                            Pembimbing: <?= highlight($r['pembimbing_1'] ?? '-', $terms); ?><?= !empty($r['pembimbing_2']) ? ', ' . highlight($r['pembimbing_2'], $terms) : ''; ?>
                          </p>

                          <?php if ($ringkas !== ''): ?>
                            <div class="collapse" id="<?= $collapse; ?>">
                              <div class="small text-muted bg-light rounded p-2 mb-2" style="line-height: 1.7;"><?= nl2br(highlight($abstrak, $terms)); ?></div>
                            </div>
                          <?php endif; ?>

                          <div class="mt-auto d-flex justify-content-between align-items-center flex-wrap">
                            <?php if ($ringkas !== ''): ?>
                              <a class="btn btn-sm btn-outline-primary font-weight-bold" data-toggle="collapse" href="#<?= $collapse; ?>" role="button" aria-expanded="false">
                                <i class="mdi mdi-text-box-outline mr-1"></i>Abstrak
                              </a>
                            <?php else: ?>
                              <span></span>
                            <?php endif; ?>
                            <?php if ($pdfUrl !== ''): ?>
                              <a href="<?= h($pdfUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-accent font-weight-bold"><i class="mdi mdi-file-pdf-box mr-1"></i> Buka PDF</a>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>

                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>

              <!-- Pagination -->
              <?php if ($totalPages > 1):
                $awal  = max(1, $page - 2);
                $akhir = min($totalPages, $page + 2);
                $base  = ['q' => $q, 'kategori' => $kategori];
              ?>
                <nav class="mt-4">
                  <ul class="pagination justify-content-center flex-wrap">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                      <a class="page-link" href="<?= h(url_pencarian($base + ['page' => $page - 1])); ?>">&laquo;</a>
                    </li>
                    <?php for ($i = $awal; $i <= $akhir; $i++): ?>
                      <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?= h(url_pencarian($base + ['page' => $i])); ?>"><?= $i; ?></a>
                      </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                      <a class="page-link" href="<?= h(url_pencarian($base + ['page' => $page + 1])); ?>">&raquo;</a>
                    </li>
                  </ul>
                  <p class="text-center small text-muted mb-0">Halaman <?= $page; ?> dari <?= $totalPages; ?> (<?= number_format($totalHasil); ?> hasil)</p>
                </nav>
              <?php endif; ?>

            <?php endif; ?>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </section>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>