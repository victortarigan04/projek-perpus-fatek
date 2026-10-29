<?php
/* ============================================================
 * KONFIGURASI GOOGLE SPREADSHEET (hasil Google Form)
 * ------------------------------------------------------------
 * 1. Spreadsheet -> Bagikan -> "Siapa saja yang memiliki link" -> Pelihat
 * 2. Salin ID dari URL: https://docs.google.com/spreadsheets/d/<ID_INI>/edit
 * 3. Isi SHEET_ID. Tab diambil dari SHEET_NAME, atau dari SHEET_GID
 *    (angka setelah "gid=" di URL) jika diisi.
 * 4. File upload Google Form (kolom SUMMARY) harus bisa dibuka publik:
 *    folder Drive hasil upload -> Bagikan -> "Siapa saja yang memiliki link".
 * ============================================================ */
const SHEET_ID   = '1MFaf_KNjotP_MmcjdNoXSe17JApQamLvu6B52nF0YTo';
const SHEET_NAME = 'Form_Responses1';   // nama tab (lihat di bagian bawah spreadsheet)
const SHEET_GID  = '1273822944';                 // opsional, mis. '0' atau '123456789'; jika diisi, nama tab diabaikan
const CACHE_TTL  = 300;                // detik (0 = tanpa cache)


/* ---------- Helper ---------- */

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

function ambil_tahun($s) {
    return preg_match('/\b(19|20)\d{2}\b/', (string)$s, $m) ? $m[0] : '';
}

// Normalisasi nama jurusan/prodi agar pencocokan toleran (abaikan "S1", huruf besar, tanda baca)
function norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = preg_replace('/^(s1|s2|d3|d4)\s+/u', '', $s);
    $s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
    return trim($s);
}

// Ambil ID file Google Drive dari link hasil upload Google Form
function drive_id($value) {
    $parts = preg_split('/[\s,]+/', trim((string)$value));
    foreach ($parts as $p) {
        if (preg_match('/[?&]id=([\w-]+)/', $p, $m)) return $m[1];
        if (preg_match('#/d/([\w-]+)#', $p, $m))     return $m[1];
    }
    return '';
}

/* ---------- Google Spreadsheet ---------- */

// Ubah ke false HANYA untuk uji coba di localhost jika muncul error sertifikat SSL
const SSL_VERIFY = true;

$GLOBALS['http_error'] = '';

function http_get($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 ElibraryFatek',
            CURLOPT_SSL_VERIFYPEER => SSL_VERIFY,
            CURLOPT_SSL_VERIFYHOST => SSL_VERIFY ? 2 : 0,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            $GLOBALS['http_error'] = 'cURL: ' . $err;
            return false;
        }
        if ($code < 200 || $code >= 300) {
            $GLOBALS['http_error'] = 'HTTP ' . $code . ' dari Google';
            return false;
        }
        return $res;
    }
    if (!ini_get('allow_url_fopen')) {
        $GLOBALS['http_error'] = 'ekstensi cURL tidak aktif dan allow_url_fopen dimatikan di php.ini';
        return false;
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0 ElibraryFatek\r\n"],
        'ssl'  => ['verify_peer' => SSL_VERIFY, 'verify_peer_name' => SSL_VERIFY],
    ]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) {
        $e = error_get_last();
        $GLOBALS['http_error'] = 'file_get_contents: ' . ($e['message'] ?? 'gagal');
    }
    return $res;
}

// Cocokkan header spreadsheet dengan field yang dipakai website
function petakan_kolom(array $header) {
    $map = [];
    foreach ($header as $i => $hd) {
        $n = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string)$hd)), 'UTF-8');
        if     (strpos($n, 'nama') === 0)                                   $k = 'nama';
        elseif ($n === 'nim')                                               $k = 'nim';
        elseif ($n === 'jurusan')                                           $k = 'jurusan';
        elseif (strpos($n, 'program studi') === 0 || $n === 'prodi')        $k = 'prodi';
        elseif (strpos($n, 'summary') === 0)                                $k = 'summary';
        elseif (strpos($n, 'judul') === 0)                                  $k = 'judul';
        elseif (strpos($n, 'tahun') === 0)                                  $k = 'tahun';
        else continue;
        if (!isset($map[$k])) $map[$k] = $i;
    }
    return $map;
}

/** Mengembalikan [rows, errorMessage] */
function ambil_data_sheet() {
    $sel = (SHEET_GID !== '') ? 'gid=' . rawurlencode(SHEET_GID) : 'sheet=' . rawurlencode(SHEET_NAME);
    $url = 'https://docs.google.com/spreadsheets/d/' . SHEET_ID . '/gviz/tq?tqx=out:csv&headers=1&' . $sel;
    $cacheFile = sys_get_temp_dir() . '/skripsi_sheet_' . md5($url) . '.csv';

    $csv = false;
    if (CACHE_TTL > 0 && is_file($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
        $csv = file_get_contents($cacheFile);
    } else {
        $csv = http_get($url);
        if ($csv !== false && $csv !== '') {
            if (CACHE_TTL > 0) @file_put_contents($cacheFile, $csv, LOCK_EX);
        } elseif (is_file($cacheFile)) {
            $csv = file_get_contents($cacheFile);
        }
    }

    if ($csv === false || trim($csv) === '') {
        $detail = $GLOBALS['http_error'] !== '' ? ' Detail: ' . $GLOBALS['http_error'] . '.' : '';
        return [[], 'Data spreadsheet tidak dapat diambil. Periksa koneksi server atau SHEET_ID.' . $detail];
    }
    if (stripos(ltrim($csv), '<!doctype') === 0 || stripos(ltrim($csv), '<html') === 0) {
        return [[], 'Spreadsheet belum dibagikan sebagai "Siapa saja yang memiliki link (Pelihat)", atau ID/nama tab salah.'];
    }

    $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $csv);
    rewind($fh);

    $header = fgetcsv($fh, 0, ',', '"', '');
    if (!$header) { fclose($fh); return [[], 'Header spreadsheet kosong.']; }
    $map = petakan_kolom($header);
    foreach (['nama', 'nim', 'judul'] as $wajib) {
        if (!isset($map[$wajib])) {
            fclose($fh);
            return [[], 'Kolom "' . strtoupper($wajib) . '" tidak ditemukan di header spreadsheet.'];
        }
    }

    $rows  = [];
    $nomor = 1;
    while (($line = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        $nomor++;
        $d = [];
        foreach ($map as $k => $i) $d[$k] = trim((string)($line[$i] ?? ''));
        if (($d['judul'] ?? '') === '' && ($d['nama'] ?? '') === '') continue; // lewati baris kosong
        $d['_row'] = $nomor;
        $rows[] = $d;
    }
    fclose($fh);
    return [$rows, ''];
}

/* ---------- Konfigurasi jurusan ---------- */
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
$aliasProdi = ['pwk' => 'S1 Perencanaan Wilayah dan Kota'];

// Cari nama prodi baku dari teks di spreadsheet
function cari_prodi_baku($raw, array $cfg, array $alias) {
    $n = norm($raw);
    if ($n === '') return '';
    if (isset($alias[$n])) return $alias[$n];
    foreach ($cfg as $j) foreach ($j['prodi'] as $p) if (norm($p) === $n) return $p;
    foreach ($cfg as $j) foreach ($j['prodi'] as $p) {
        $np = norm($p);
        if ($np !== '' && (strpos($n, $np) !== false || strpos($np, $n) !== false)) return $p;
    }
    return '';
}

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

$semua = []; $prodiCounts = []; $hasil = []; $tampil = [];
$total = 0; $totalPages = 1; $errorSheet = '';

if ($valid) {
    list($rows, $errorSheet) = ambil_data_sheet();

    foreach ($rows as $d) {
        // Prodi baku & jurusan (jurusan dari kolom JURUSAN; jika tak cocok, diturunkan dari prodi)
        $prodiBaku = cari_prodi_baku($d['prodi'] ?? '', $jurusanCfg, $aliasProdi);
        $jur = '';
        $nj  = norm($d['jurusan'] ?? '');
        foreach ($jurusanCfg as $nama => $c) if (norm($nama) === $nj) { $jur = $nama; break; }
        if ($jur === '' && $prodiBaku !== '') {
            foreach ($jurusanCfg as $nama => $c) if (in_array($prodiBaku, $c['prodi'], true)) { $jur = $nama; break; }
        }
        if ($jur !== $jurusan) continue;

        $d['_prodi']   = $prodiBaku !== '' ? $prodiBaku : ($d['prodi'] ?? '');
        $d['_tahun']   = ambil_tahun($d['tahun'] ?? '');
        $d['_file_id'] = drive_id($d['summary'] ?? '');
        $semua[] = $d;
        $prodiCounts[$d['_prodi']] = ($prodiCounts[$d['_prodi']] ?? 0) + 1;
    }

    foreach ($semua as $d) {
        if ($prodi !== '' && $d['_prodi'] !== $prodi) continue;

        if (!empty($terms)) {
            $haystack = mb_strtolower(implode(' ', [
                $d['judul'] ?? '', $d['nama'] ?? '', $d['nim'] ?? '', $d['_prodi'], $d['_tahun'],
            ]), 'UTF-8');
            $cocok = true;
            foreach ($terms as $t) {
                if (mb_strpos($haystack, $t) === false) { $cocok = false; break; }
            }
            if (!$cocok) continue;
        }
        $hasil[] = $d;
    }

    // Tahun terbaru di atas; jika sama, yang baru masuk (baris paling bawah) di atas
    usort($hasil, function ($a, $b) {
        $cmp = (int)$b['_tahun'] <=> (int)$a['_tahun'];
        return $cmp !== 0 ? $cmp : ($b['_row'] <=> $a['_row']);
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

  <div class="page-banner">
    <div class="container text-center">
      <h2 class="font-weight-bold mb-1"><i class="mdi <?= $cfg['icon']; ?> mr-2"></i>Skripsi <?= h($jurusan); ?></h2>
      <p class="mb-0 small" style="opacity: 0.9;">Repositori karya ilmiah tugas akhir mahasiswa <?= implode(' &amp; ', array_map('h', $cfg['prodi'])); ?></p>
    </div>
  </div>

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
                  <input type="text" name="q" value="<?= h($q); ?>" class="form-control form-control-lg border-light bg-light" placeholder="Cari judul, nama, atau NIM dalam <?= h($jurusan); ?>..." style="font-size: 0.95rem;">
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

      <?php if ($errorSheet !== ''): ?>
        <div class="alert alert-danger small"><i class="mdi mdi-alert-circle-outline mr-1"></i><?= h($errorSheet); ?></div>
      <?php endif; ?>

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
          $fid = $r['_file_id'];
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
                    <small class="text-muted"><?= h($r['_prodi'] !== '' ? $r['_prodi'] : '-'); ?><?= $r['_tahun'] !== '' ? ' &middot; ' . h($r['_tahun']) : ''; ?></small>
                  </div>
                  <h6 class="font-weight-bold text-dark mb-1"><?= highlight($r['judul'] !== '' ? $r['judul'] : 'Tanpa Judul', $terms); ?></h6>
                  <p class="small text-muted mb-1"><i class="mdi mdi-account-outline mr-1"></i><?= highlight($r['nama'] !== '' ? $r['nama'] : '-', $terms); ?></p>
                  <p class="small text-muted mb-2"><i class="mdi mdi-card-account-details-outline mr-1"></i>NIM: <?= highlight($r['nim'] !== '' ? $r['nim'] : '-', $terms); ?></p>

                  <div class="mt-auto d-flex align-items-center flex-wrap">
                    <?php if ($fid !== ''): ?>
                      <a href="https://drive.google.com/file/d/<?= h($fid); ?>/view" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary font-weight-bold mr-2 mb-1">
                        <i class="mdi mdi-eye-outline mr-1"></i>Lihat Summary
                      </a>
                      <a href="https://drive.google.com/uc?export=download&amp;id=<?= h($fid); ?>" rel="noopener" class="btn btn-sm btn-accent font-weight-bold ml-auto mb-1">
                        <i class="mdi mdi-download mr-1"></i> Unduh Summary (PDF)
                      </a>
                    <?php else: ?>
                      <span class="small text-muted ml-auto mb-1"><i class="mdi mdi-file-hidden mr-1"></i>Summary belum tersedia</span>
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
