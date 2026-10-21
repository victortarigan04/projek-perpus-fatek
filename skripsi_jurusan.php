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

function url_halaman(array $params) {
    $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
    return 'skripsi_jurusan.php?' . http_build_query($params);
}

/* ---------- Konfigurasi jurusan (sama dengan form admin & index.php) ---------- */
$jurusanCfg = [
    'Teknik Mesin' => [
        'icon' => 'mdi-cog-outline', 'warna' => '#1E3A8A',
        'prodi' => ['S1 Teknik Mesin', 'S1 Teknik Industri'],
    ],
    'Teknik Elektro' => [
        'icon' => 'mdi-lightning-bolt-outline', 'warna' => '#F59E0B',
        'prodi' => ['S1 Teknik Informatika', 'S1 Teknik Elektro'],
    ],
    'Teknik Sipil' => [
        'icon' => 'mdi-office-building-outline', 'warna' => '#10B981',
        'prodi' => ['S1 Teknik Sipil', 'S1 Teknik Lingkungan'],
    ],
    'Arsitektur' => [
        'icon' => 'mdi-compass-outline', 'warna' => '#EF4444',
        'prodi' => ['S1 Arsitektur', 'S1 Perencanaan Wilayah dan Kota'],
    ],
];

/* ---------- Parameter ---------- */
$jurusan = trim($_GET['jurusan'] ?? '');
$valid   = isset($jurusanCfg[$jurusan]);

$prodi = trim($_GET['prodi'] ?? '');
if (!$valid || !in_array($prodi, $jurusanCfg[$jurusan]['prodi'], true)) {
    $prodi = '';
}

$q      = trim($_GET['q'] ?? '');
$terms  = array_values(array_unique(array_filter(
    preg_split('/\s+/u', mb_strtolower($q, 'UTF-8')),
    function ($t) { return $t !== ''; }
)));

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$semua       = [];            // seluruh skripsi pada jurusan ini
$prodiCounts = [];
$hasil       = [];
$total       = 0;
$totalPages  = 1;
$tampil      = [];

if ($valid) {
    try {
        // Filter jurusan saja di Firestore; urutan & filter lain dilakukan di PHP
        // (menghindari kebutuhan composite index)
        $docs = $db->collection('skripsi')->where('jurusan', '==', $jurusan)->documents();
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            $d['id'] = $doc->id();
            $semua[] = $d;
            $p = $d['prodi'] ?? '';
            $prodiCounts[$p] = ($prodiCounts[$p] ?? 0) + 1;
        }
    } catch (Exception $e) {
        $semua = [];
    }

    foreach ($semua as $d) {
        if ($prodi !== '' && ($d['prodi'] ?? '') !== $prodi) continue;

        if (!empty($terms)) {
            $haystack = mb_strtolower(implode(' ', [
                $d['judul'] ?? '', $d['nama_mhs'] ?? '', $d['prodi'] ?? '',
                $d['pembimbing_1'] ?? '', $d['pembimbing_2'] ?? '', $d['abstrak'] ?? '',
            ]), 'UTF-8');
            $cocok = true;
            foreach ($terms as $t) {
                if (mb_strpos($haystack, $t) === false) { $cocok = false; break; }
            }
            if (!$cocok) continue;
        }
        $hasil[] = $d;
    }

    usort($hasil, function ($a, $b) {
        return (int)($b['created_at'] ?? 0) - (int)($a['created_at'] ?? 0);
    });

    $total      = count($hasil);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = min($page, $totalPages);
    $tampil     = array_slice($hasil, ($page - 1) * $perPage, $perPage);
}

$dari   = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
$sampai = ($page - 1) * $perPage + count($tampil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $valid ? 'Skripsi ' . h($jurusan) : 'Repositori Skripsi'; ?> - Elibrary Fatek UNSRAT</title>

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

    .jurusan-card { background: #FFFFFF; border-radius: 10px; border-left: 5px solid var(--secondary-color); transition: all 0.25s ease; }
    .jurusan-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07); }

    .jurusan-tabs .nav-link { border-radius: 30px; font-size: 0.88rem; font-weight: 600; margin-right: 6px; margin-bottom: 6px; background: #FFFFFF; border: 1px solid #E2E8F0; }
    .jurusan-tabs .nav-link.active { color: #FFFFFF !important; }

    .prodi-pills .nav-link { border-radius: 30px; font-size: 0.85rem; font-weight: 600; margin-right: 6px; margin-bottom: 6px; background: #FFFFFF; border: 1px solid #E2E8F0; }
    .prodi-pills .nav-link.active { background-color: var(--primary-color) !important; border-color: var(--primary-color); color: #FFFFFF !important; }

    .card-hasil { border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; transition: all 0.25s ease; }
    .card-hasil:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07) !important; }
    .icon-skripsi {
      width: 90px; height: 120px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #10B981 0%, #047857 100%); color: #FFFFFF;
    }

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

<?php if (!$valid): ?>

  <!-- Jurusan belum dipilih / tidak valid: tampilkan pilihan jurusan -->
  <div class="page-banner">
    <div class="container text-center">
      <h2 class="font-weight-bold mb-1"><i class="mdi mdi-school mr-2"></i>Repositori Skripsi Mahasiswa</h2>
      <p class="mb-0 small" style="opacity: 0.9;">Pilih jurusan untuk menjelajahi karya ilmiah tugas akhir</p>
    </div>
  </div>

  <section class="py-5">
    <div class="container">
      <?php if ($jurusan !== ''): ?>
        <div class="alert alert-warning small"><i class="mdi mdi-alert-circle-outline mr-1"></i>Jurusan &ldquo;<?= h($jurusan); ?>&rdquo; tidak ditemukan. Silakan pilih salah satu jurusan berikut.</div>
      <?php endif; ?>
      <div class="row">
        <?php foreach ($jurusanCfg as $nama => $cfg): ?>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card jurusan-card h-100 p-3" style="border-left-color: <?= $cfg['warna']; ?>;">
              <div class="d-flex align-items-center mb-2">
                <i class="mdi <?= $cfg['icon']; ?> mdi-28px mr-2" style="color: <?= $cfg['warna']; ?>;"></i>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 1.05rem;"><?= h($nama); ?></h5>
              </div>
              <p class="small text-muted mb-3"><?= h(implode(', ', $cfg['prodi'])); ?></p>
              <a href="<?= h(url_halaman(['jurusan' => $nama])); ?>" class="small text-primary font-weight-bold mt-auto"><i class="mdi mdi-arrow-right-circle mr-1"></i>Lihat Skripsi</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

<?php else:
  $cfg = $jurusanCfg[$jurusan];
?>

  <!-- Banner Judul Halaman -->
  <div class="page-banner">
    <div class="container text-center">
      <h2 class="font-weight-bold mb-1"><i class="mdi <?= $cfg['icon']; ?> mr-2"></i>Skripsi <?= h($jurusan); ?></h2>
      <p class="mb-0 small" style="opacity: 0.9;">Repositori karya ilmiah tugas akhir mahasiswa <?= implode(' &amp; ', array_map('h', $cfg['prodi'])); ?></p>
    </div>
  </div>

  <!-- Form Pencarian dalam jurusan -->
  <div class="container search-box-wrapper">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card search-card p-3 p-md-4">
          <form action="skripsi_jurusan.php" method="get">
            <input type="hidden" name="jurusan" value="<?= h($jurusan); ?>">
            <?php if ($prodi !== ''): ?><input type="hidden" name="prodi" value="<?= h($prodi); ?>"><?php endif; ?>
            <div class="row no-gutters align-items-center">
              <div class="col-md-10 mb-2 mb-md-0 pr-md-2">
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text bg-light border-light text-muted"><i class="mdi mdi-magnify mdi-24px"></i></span></div>
                  <input type="text" name="q" value="<?= h($q); ?>" class="form-control form-control-lg border-light bg-light" placeholder="Cari judul, nama mahasiswa, atau pembimbing dalam <?= h($jurusan); ?>..." style="font-size: 0.95rem;">
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

  <section class="py-5">
    <div class="container">

      <!-- Pindah jurusan -->
      <ul class="nav jurusan-tabs mb-2">
        <?php foreach ($jurusanCfg as $nama => $c): ?>
          <li class="nav-item">
            <a class="nav-link <?= $nama === $jurusan ? 'active' : 'text-dark'; ?>"
               style="<?= $nama === $jurusan ? 'background-color:' . $c['warna'] . '; border-color:' . $c['warna'] . ';' : ''; ?>"
               href="<?= h(url_halaman(['jurusan' => $nama])); ?>">
              <i class="mdi <?= $c['icon']; ?> mr-1"></i><?= h($nama); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Filter program studi -->
      <ul class="nav prodi-pills mb-3">
        <li class="nav-item">
          <a class="nav-link <?= $prodi === '' ? 'active' : 'text-dark'; ?>" href="<?= h(url_halaman(['jurusan' => $jurusan, 'q' => $q])); ?>">
            Semua Prodi <span class="badge badge-light ml-1"><?= count($semua); ?></span>
          </a>
        </li>
        <?php foreach ($cfg['prodi'] as $p): ?>
          <li class="nav-item">
            <a class="nav-link <?= $prodi === $p ? 'active' : 'text-dark'; ?>" href="<?= h(url_halaman(['jurusan' => $jurusan, 'prodi' => $p, 'q' => $q])); ?>">
              <?= h($p); ?> <span class="badge badge-light ml-1"><?= (int)($prodiCounts[$p] ?? 0); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <p class="text-muted small mb-0">
          <?php if ($total > 0): ?>
            Menampilkan <?= number_format($dari); ?>&ndash;<?= number_format($sampai); ?> dari <?= number_format($total); ?> skripsi
            <?= $q !== '' ? 'untuk &ldquo;' . h($q) . '&rdquo;' : ''; ?>
          <?php else: ?>
            Tidak ada skripsi ditemukan
          <?php endif; ?>
        </p>
        <a href="index.php#skripsi" class="btn btn-outline-secondary btn-sm font-weight-bold mt-2 mt-sm-0"><i class="mdi mdi-arrow-left mr-1"></i> Kembali ke Beranda</a>
      </div>

      <?php if (empty($tampil)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm text-muted">
          <i class="mdi mdi-file-search-outline mdi-48px"></i>
          <h6 class="font-weight-bold text-dark mt-2">Skripsi tidak ditemukan</h6>
          <p class="small mb-0">Belum ada skripsi pada pilihan ini, atau coba kata kunci lain.</p>
        </div>
      <?php else: ?>

        <?php foreach ($tampil as $r):
          $pdfUrl  = get_media_url($r['file_pdf'] ?? '', '');
          $abstrak = trim(strip_tags($r['abstrak'] ?? ''));
          $collapse = 'abs-' . preg_replace('/[^a-zA-Z0-9]/', '', $r['id']);
        ?>
          <div class="card card-hasil shadow-sm mb-3">
            <div class="card-body p-3">
              <div class="d-flex">
                <div class="mr-3 flex-shrink-0 d-none d-sm-block">
                  <div class="icon-skripsi"><i class="mdi mdi-school mdi-48px"></i></div>
                </div>

                <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
                  <div class="mb-1">
                    <span class="badge badge-success mr-1"><i class="mdi mdi-school-outline mr-1"></i>Skripsi</span>
                    <small class="text-muted"><?= h($r['prodi'] ?? '-'); ?><?= !empty($r['tanggal']) ? ' &middot; ' . h(substr($r['tanggal'], 0, 4)) : ''; ?></small>
                  </div>
                  <h6 class="font-weight-bold text-dark mb-1"><?= highlight($r['judul'] ?? 'Tanpa Judul', $terms); ?></h6>
                  <p class="small text-muted mb-1"><i class="mdi mdi-account-outline mr-1"></i>Mahasiswa: <?= highlight($r['nama_mhs'] ?? '-', $terms); ?></p>
                  <p class="small text-muted mb-2">
                    Pembimbing: <?= highlight($r['pembimbing_1'] ?? '-', $terms); ?><?= !empty($r['pembimbing_2']) ? ', ' . highlight($r['pembimbing_2'], $terms) : ''; ?>
                  </p>

                  <?php if ($abstrak !== ''): ?>
                    <div class="collapse" id="<?= $collapse; ?>">
                      <div class="small text-muted bg-light rounded p-2 mb-2" style="line-height: 1.7;"><?= nl2br(highlight($abstrak, $terms)); ?></div>
                    </div>
                  <?php endif; ?>

                  <div class="mt-auto d-flex align-items-center flex-wrap">
                    <?php if ($abstrak !== ''): ?>
                      <a class="btn btn-sm btn-outline-primary font-weight-bold mr-2 mb-1" data-toggle="collapse" href="#<?= $collapse; ?>" role="button" aria-expanded="false">
                        <i class="mdi mdi-text-box-outline mr-1"></i>Abstrak
                      </a>
                    <?php endif; ?>
                    <a href="detail.php?tipe=skripsi&id=<?= h($r['id']); ?>" class="btn btn-sm btn-outline-success font-weight-bold mr-2 mb-1">Detail</a>
                    <?php if ($pdfUrl !== ''): ?>
                      <a href="<?= h($pdfUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-accent font-weight-bold ml-auto mb-1"><i class="mdi mdi-file-pdf-box mr-1"></i> Buka PDF</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1):
          $awal  = max(1, $page - 2);
          $akhir = min($totalPages, $page + 2);
          $base  = ['jurusan' => $jurusan, 'prodi' => $prodi, 'q' => $q];
        ?>
          <nav class="mt-4">
            <ul class="pagination justify-content-center flex-wrap">
              <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?= h(url_halaman($base + ['page' => $page - 1])); ?>">&laquo;</a>
              </li>
              <?php if ($awal > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= h(url_halaman($base + ['page' => 1])); ?>">1</a></li>
                <?php if ($awal > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
              <?php endif; ?>
              <?php for ($i = $awal; $i <= $akhir; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="<?= h(url_halaman($base + ['page' => $i])); ?>"><?= $i; ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($akhir < $totalPages): ?>
                <?php if ($akhir < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= h(url_halaman($base + ['page' => $totalPages])); ?>"><?= $totalPages; ?></a></li>
              <?php endif; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?= h(url_halaman($base + ['page' => $page + 1])); ?>">&raquo;</a>
              </li>
            </ul>
            <p class="text-center small text-muted mb-0">Halaman <?= $page; ?> dari <?= $totalPages; ?></p>
          </nav>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </section>

<?php endif; ?>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
