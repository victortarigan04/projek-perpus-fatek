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

/* ---------- Konfigurasi tipe koleksi ---------- */
$konfigurasi = [
    'buku_fisik' => ['label' => 'Buku Fisik',        'icon' => 'mdi-book-outline',        'warna' => 'primary', 'anchor' => 'buku'],
    'ebook'      => ['label' => 'E-Book Digital',    'icon' => 'mdi-book-open-variant',   'warna' => 'info',    'anchor' => 'ebook'],
    'skripsi'    => ['label' => 'Skripsi Mahasiswa', 'icon' => 'mdi-school-outline',      'warna' => 'success', 'anchor' => 'skripsi'],
];

/* ---------- Ambil data ---------- */
$tipe = $_GET['tipe'] ?? '';
$id   = $_GET['id'] ?? '';
$data = null;

if (isset($konfigurasi[$tipe]) && preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
    try {
        $snap = $db->collection($tipe)->document($id)->snapshot();
        if ($snap->exists()) {
            $data = $snap->data();
        }
    } catch (Exception $e) {
        $data = null;
    }
}

if (!$data) {
    http_response_code(404);
}

$cfg = $konfigurasi[$tipe] ?? null;

/* ---------- Tautan "kembali" (ke hasil pencarian jika asalnya dari sana) ---------- */
$kembaliUrl   = $cfg ? 'index.php#' . $cfg['anchor'] : 'index.php';
$kembaliLabel = $cfg ? 'Katalog ' . $cfg['label'] : 'Beranda';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer !== '') {
    $ref = parse_url($referer);
    if (!empty($ref['host']) && $ref['host'] === ($_SERVER['HTTP_HOST'] ?? '')
        && basename($ref['path'] ?? '') === 'pencarian.php') {
        $kembaliUrl   = $referer;
        $kembaliLabel = 'Hasil Pencarian';
    }
}

/* ---------- Siapkan tampilan ---------- */
$judul     = '';
$rows      = [];   // [label, html]
$deskLabel = '';
$deskripsi = '';
$coverUrl  = '';
$aksi      = '';   // html tombol aksi
$terkait   = [];

if ($data) {
    $judul = $data['judul'] ?? 'Tanpa Judul';

    if ($tipe === 'skripsi') {
        $pdfUrl = get_media_url($data['file_pdf'] ?? '', '');

        $rows[] = ['Nama Mahasiswa', h($data['nama_mhs'] ?? '-')];
        $rows[] = ['Jurusan', h($data['jurusan'] ?? '-')];
        $rows[] = ['Program Studi', h($data['prodi'] ?? '-')];
        $rows[] = ['Pembimbing I', h($data['pembimbing_1'] ?? '-')];
        if (!empty($data['pembimbing_2'])) {
            $rows[] = ['Pembimbing II', h($data['pembimbing_2'])];
        }
        if (!empty($data['tanggal'])) {
            $rows[] = ['Tanggal Unggah', h(substr($data['tanggal'], 0, 10))];
        }
        $deskLabel = 'Abstrak';
        $deskripsi = $data['abstrak'] ?? '';

        if ($pdfUrl !== '') {
            $aksi = '<a href="' . h($pdfUrl) . '" target="_blank" rel="noopener" class="btn btn-accent btn-block font-weight-bold py-2"><i class="mdi mdi-file-pdf-box mr-1"></i> Buka PDF Skripsi</a>';
        } else {
            $aksi = '<div class="small text-muted text-center"><i class="mdi mdi-file-alert-outline mr-1"></i>File PDF belum tersedia.</div>';
        }
    } else {
        $placeholder = ($tipe === 'ebook')
            ? 'https://via.placeholder.com/300x400/0284C7/FFFFFF?text=E-Book+PDF'
            : 'https://via.placeholder.com/300x400/1E3A8A/FFFFFF?text=Buku+Fisik';
        $coverUrl = get_media_url($data['cover'] ?? '', $placeholder);

        $rows[] = ['ISBN', h($data['isbn'] ?? '-')];
        $rows[] = ['Penulis', h($data['penulis'] ?? '-')];
        $rows[] = ['Penerbit', h($data['penerbit'] ?? '-')];
        $rows[] = ['Jumlah Halaman', (int)($data['jml_halaman'] ?? 0) . ' halaman'];

        if ($tipe === 'buku_fisik') {
            $stok = (int)($data['stok'] ?? 0);
            $rows[] = ['Ketersediaan', $stok > 0
                ? '<span class="badge badge-success px-3 py-1">Tersedia (' . $stok . ' eksemplar)</span>'
                : '<span class="badge badge-danger px-3 py-1">Stok habis</span>'];
            $aksi = '<div class="small text-muted text-center"><i class="mdi mdi-map-marker-outline mr-1"></i>Buku dapat dipinjam langsung di Perpustakaan Fakultas Teknik UNSRAT.</div>';
        } else {
            if (!empty($data['link_download'])) {
                $aksi = '<a href="' . h($data['link_download']) . '" target="_blank" rel="noopener" class="btn btn-accent btn-block font-weight-bold py-2"><i class="mdi mdi-download mr-1"></i> Unduh E-Book</a>';
            } else {
                $aksi = '<div class="small text-muted text-center"><i class="mdi mdi-file-alert-outline mr-1"></i>Tautan unduhan belum tersedia.</div>';
            }
        }
        $deskLabel = 'Deskripsi';
        $deskripsi = $data['deskripsi'] ?? '';
    }

    // Koleksi lain dari kategori yang sama
    try {
        $docs = $db->collection($tipe)->orderBy('created_at', 'DESC')->limit(5)->documents();
        foreach ($docs as $doc) {
            if (!$doc->exists() || $doc->id() === $id) continue;
            $d = $doc->data();
            $d['id'] = $doc->id();
            $terkait[] = $d;
            if (count($terkait) >= 4) break;
        }
    } catch (Exception $e) {
        $terkait = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $data ? h($judul) . ' - ' : ''; ?>Detail Koleksi - Elibrary Fatek UNSRAT</title>

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

    .detail-card { background: #FFFFFF; border: none; border-radius: 12px; box-shadow: 0 8px 24px rgba(30, 58, 138, 0.07); }
    .cover-detail { width: 100%; max-width: 260px; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
    .icon-skripsi-detail {
      width: 100%; max-width: 260px; aspect-ratio: 3 / 4; border-radius: 10px; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #10B981 0%, #047857 100%); color: #FFFFFF; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .meta-table th { width: 38%; font-weight: 500; color: #64748B; font-size: 0.9rem; border-top: 1px solid #F1F5F9; }
    .meta-table td { font-weight: 500; font-size: 0.92rem; border-top: 1px solid #F1F5F9; }

    .card-katalog { border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; transition: all 0.3s ease; }
    .card-katalog:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.07) !important; }
    .cover-thumb { height: 180px; object-fit: cover; border-top-left-radius: 12px; border-top-right-radius: 12px; }
    .icon-thumb {
      height: 180px; display: flex; align-items: center; justify-content: center; color: #FFFFFF;
      background: linear-gradient(135deg, #10B981 0%, #047857 100%); border-top-left-radius: 12px; border-top-right-radius: 12px;
    }

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

      <?php if (!$data): ?>
        <!-- Data tidak ditemukan -->
        <div class="text-center py-5 bg-white rounded shadow-sm">
          <i class="mdi mdi-file-search-outline mdi-48px text-muted"></i>
          <h5 class="font-weight-bold text-dark mt-2">Koleksi tidak ditemukan</h5>
          <p class="small text-muted mb-3">Data yang Anda cari mungkin sudah dihapus atau tautannya tidak valid.</p>
          <a href="index.php" class="btn btn-primary-custom px-4"><i class="mdi mdi-home-outline mr-1"></i> Kembali ke Beranda</a>
        </div>

      <?php else: ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= h($kembaliUrl); ?>"><?= h($kembaliLabel); ?></a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 280px;"><?= h($judul); ?></li>
          </ol>
        </nav>

        <div class="card detail-card p-3 p-md-4 mb-5">
          <div class="row">

            <!-- Kolom kiri: cover + aksi -->
            <div class="col-md-4 text-center mb-4 mb-md-0">
              <?php if ($tipe === 'skripsi'): ?>
                <div class="icon-skripsi-detail mx-auto"><i class="mdi mdi-school mdi-48px" style="font-size: 5rem;"></i></div>
              <?php else: ?>
                <img src="<?= h($coverUrl); ?>" alt="Cover <?= h($judul); ?>" class="cover-detail">
              <?php endif; ?>

              <div class="mt-4 mx-auto" style="max-width: 260px;">
                <?= $aksi; ?>
              </div>
            </div>

            <!-- Kolom kanan: informasi -->
            <div class="col-md-8">
              <span class="badge badge-<?= $cfg['warna']; ?> px-3 py-2 mb-2">
                <i class="mdi <?= $cfg['icon']; ?> mr-1"></i><?= h($cfg['label']); ?>
              </span>
              <h3 class="font-weight-bold text-dark mb-3" style="line-height: 1.4;"><?= h($judul); ?></h3>

              <table class="table table-borderless table-sm meta-table mb-4">
                <tbody>
                  <?php foreach ($rows as $row): ?>
                    <tr>
                      <th><?= h($row[0]); ?></th>
                      <td><?= $row[1]; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

              <h6 class="font-weight-bold text-dark mb-2"><i class="mdi mdi-text-box-outline text-primary mr-1"></i><?= h($deskLabel); ?></h6>
              <?php if (trim($deskripsi) !== ''): ?>
                <p class="text-muted" style="line-height: 1.85; font-size: 0.95rem; text-align: justify;"><?= nl2br(h($deskripsi)); ?></p>
              <?php else: ?>
                <p class="text-muted small">Belum ada <?= strtolower(h($deskLabel)); ?> untuk koleksi ini.</p>
              <?php endif; ?>

              <a href="<?= h($kembaliUrl); ?>" class="btn btn-outline-secondary btn-sm font-weight-bold mt-2">
                <i class="mdi mdi-arrow-left mr-1"></i> Kembali ke <?= h($kembaliLabel); ?>
              </a>
            </div>
          </div>
        </div>

        <!-- Koleksi lainnya -->
        <?php if (!empty($terkait)): ?>
          <div class="mb-3">
            <h5 class="font-weight-bold text-dark"><i class="mdi mdi-bookmark-multiple-outline text-warning mr-1"></i>Koleksi <?= h($cfg['label']); ?> Lainnya</h5>
          </div>
          <div class="row">
            <?php foreach ($terkait as $t):
              $subjudul = ($tipe === 'skripsi') ? ($t['nama_mhs'] ?? '-') : ($t['penulis'] ?? '-');
            ?>
              <div class="col-md-3 col-6 mb-4">
                <div class="card card-katalog h-100">
                  <?php if ($tipe === 'skripsi'): ?>
                    <div class="icon-thumb"><i class="mdi mdi-school mdi-48px"></i></div>
                  <?php else:
                    $ph = ($tipe === 'ebook')
                      ? 'https://via.placeholder.com/300x400/0284C7/FFFFFF?text=E-Book+PDF'
                      : 'https://via.placeholder.com/300x400/1E3A8A/FFFFFF?text=Buku+Fisik';
                  ?>
                    <img src="<?= h(get_media_url($t['cover'] ?? '', $ph)); ?>" class="card-img-top cover-thumb" alt="Cover">
                  <?php endif; ?>
                  <div class="card-body p-3 d-flex flex-column">
                    <h6 class="font-weight-bold text-dark mb-1 text-truncate" title="<?= h($t['judul'] ?? ''); ?>"><?= h($t['judul'] ?? 'Tanpa Judul'); ?></h6>
                    <p class="small text-muted mb-2 text-truncate"><?= h($subjudul); ?></p>
                    <a href="detail.php?tipe=<?= h($tipe); ?>&id=<?= h($t['id']); ?>" class="btn btn-sm btn-outline-primary font-weight-bold mt-auto">Detail</a>
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
</body>
</html>
