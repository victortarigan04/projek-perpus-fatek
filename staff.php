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

// Inisial nama untuk avatar cadangan (maks. 2 huruf, abaikan gelar di depan seperti "Dr.")
function inisial($nama) {
    $nama  = trim(preg_replace('/\b(Dr|Drs|Dra|Ir|Prof|H|Hj)\.?\s+/iu', '', (string)$nama));
    $kata  = preg_split('/[\s,]+/u', $nama, -1, PREG_SPLIT_NO_EMPTY);
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $hasil !== '' ? $hasil : '?';
}

// Nomor telepon -> format internasional untuk tautan WhatsApp
function nomor_wa($tel) {
    $digit = preg_replace('/\D/', '', (string)$tel);
    if ($digit === '') return '';
    if (strpos($digit, '0') === 0)  return '62' . substr($digit, 1);
    if (strpos($digit, '62') === 0) return $digit;
    return $digit;
}

// Foto staf atau avatar inisial
function foto_staff(array $d, $kelas) {
    $url = get_media_url($d['foto'] ?? '', '');
    if ($url !== '') {
        return '<img src="' . h($url) . '" alt="Foto ' . h($d['nama'] ?? 'Staf') . '" class="' . $kelas . '">';
    }
    return '<div class="' . $kelas . ' avatar-inisial">' . h(inisial($d['nama'] ?? '')) . '</div>';
}

// Tombol kontak (email, telepon, WhatsApp)
function tombol_kontak(array $d) {
    $out = '';
    if (!empty($d['email'])) {
        $out .= '<a href="mailto:' . h($d['email']) . '" class="kontak-btn kontak-email" title="Kirim email"><i class="mdi mdi-email-outline"></i></a>';
    }
    if (!empty($d['telepon'])) {
        $out .= '<a href="tel:' . h(preg_replace('/[^\d+]/', '', $d['telepon'])) . '" class="kontak-btn kontak-telp" title="Telepon"><i class="mdi mdi-phone-outline"></i></a>';
        $wa = nomor_wa($d['telepon']);
        if ($wa !== '') {
            $out .= '<a href="https://wa.me/' . h($wa) . '" target="_blank" rel="noopener" class="kontak-btn kontak-wa" title="WhatsApp"><i class="mdi mdi-whatsapp"></i></a>';
        }
    }
    return $out;
}

/* ---------- Ambil data staf ---------- */
$staffList = [];
try {
    foreach ($db->collection('staff')->documents() as $doc) {
        if (!$doc->exists()) continue;
        $d = $doc->data();
        $d['id'] = $doc->id();
        $staffList[] = $d;
    }
} catch (Throwable $e) {
    $staffList = [];
}

// Urutkan berdasarkan nomor urut (kecil lebih dulu), lalu nama
usort($staffList, function ($a, $b) {
    $u = (int)($a['urutan'] ?? 999) - (int)($b['urutan'] ?? 999);
    return $u !== 0 ? $u : strcasecmp($a['nama'] ?? '', $b['nama'] ?? '');
});

$jumlahStaff = count($staffList);

// Staf pertama berjabatan "Kepala ..." ditampilkan sebagai kartu utama
$featured = null;
if ($jumlahStaff > 0 && preg_match('/kepala/i', $staffList[0]['jabatan'] ?? '')) {
    $featured = array_shift($staffList);
}

$gradien = [
    'linear-gradient(135deg, #1E3A8A 0%, #0284C7 100%)',
    'linear-gradient(135deg, #0284C7 0%, #06B6D4 100%)',
    'linear-gradient(135deg, #1E3A8A 0%, #7C3AED 100%)',
    'linear-gradient(135deg, #047857 0%, #0EA5E9 100%)',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staf Perpustakaan - Elibrary Fatek UNSRAT</title>

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

    /* ----- Banner ----- */
    .page-banner {
      position: relative; overflow: hidden; color: #FFFFFF; padding: 60px 0 70px;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    }
    .page-banner::before, .page-banner::after { content: ''; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.07); }
    .page-banner::before { width: 320px; height: 320px; top: -120px; right: -80px; }
    .page-banner::after  { width: 220px; height: 220px; bottom: -110px; left: -60px; }
    .page-banner .container { position: relative; z-index: 2; }
    .banner-chip { display: inline-block; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 30px; padding: 4px 16px; font-size: 0.8rem; font-weight: 500; margin-bottom: 14px; }

    /* ----- Kartu utama (kepala) ----- */
    .featured-wrap { margin-top: -45px; position: relative; z-index: 5; }
    .featured-card { border: none; border-radius: 22px; overflow: hidden; background: #FFFFFF; box-shadow: 0 20px 45px rgba(30, 58, 138, 0.15); }
    .featured-photo-panel { background: linear-gradient(160deg, #1E3A8A 0%, #0284C7 100%); display: flex; align-items: center; justify-content: center; padding: 36px 20px; position: relative; }
    .featured-photo { width: 220px; height: 220px; border-radius: 50%; object-fit: cover; object-position: center top; border: 6px solid rgba(255, 255, 255, 0.9); box-shadow: 0 14px 30px rgba(0, 0, 0, 0.25); }
    .featured-photo.avatar-inisial { font-size: 4.5rem; }
    .featured-body { padding: 36px 40px; }
    .info-baris { display: flex; align-items: flex-start; font-size: 0.92rem; color: #475569; margin-bottom: 8px; }
    .info-baris i { color: var(--secondary-color); font-size: 1.15rem; margin-right: 10px; line-height: 1.4; }

    /* ----- Kartu staf ----- */
    .staff-card { border: none; border-radius: 18px; background: #FFFFFF; overflow: hidden; height: 100%; box-shadow: 0 6px 20px rgba(30, 58, 138, 0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .staff-card:hover { transform: translateY(-8px); box-shadow: 0 18px 36px rgba(30, 58, 138, 0.17); }
    .staff-cover { height: 92px; }
    .staff-avatar { width: 128px; height: 128px; border-radius: 50%; border: 5px solid #FFFFFF; object-fit: cover; object-position: center top; margin: -64px auto 0; display: block; position: relative; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); background: #E2E8F0; }
    .avatar-inisial { display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 2.3rem; color: #FFFFFF; background: linear-gradient(135deg, #0284C7, #1E3A8A); }
    .staff-nama { font-weight: 700; font-size: 1.05rem; line-height: 1.4; color: var(--text-dark); }
    .jabatan-pill { display: inline-block; background: #EFF6FF; color: var(--primary-color); font-weight: 600; font-size: 0.78rem; padding: 5px 14px; border-radius: 30px; }
    .staff-detail { font-size: 0.85rem; color: #64748B; }
    .staff-detail i { color: var(--secondary-color); margin-right: 6px; }

    .kontak-btn { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 4px; font-size: 1.15rem; transition: all 0.25s ease; text-decoration: none !important; }
    .kontak-email { background: #EFF6FF; color: #1E3A8A; }
    .kontak-telp  { background: #FEF3C7; color: #B45309; }
    .kontak-wa    { background: #DCFCE7; color: #15803D; }
    .kontak-btn:hover { transform: scale(1.12); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12); }

    /* ----- Info layanan ----- */
    .layanan-card { background: linear-gradient(135deg, #1E3A8A 0%, #0284C7 100%); border-radius: 18px; color: #FFFFFF; position: relative; overflow: hidden; }
    .layanan-card::after { content: ''; position: absolute; width: 240px; height: 240px; border-radius: 50%; background: rgba(255, 255, 255, 0.07); right: -70px; top: -90px; }
    .layanan-item { display: flex; align-items: center; }
    .layanan-ikon { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-right: 14px; flex-shrink: 0; }

    @keyframes munculNaik { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
    .muncul { animation: munculNaik 0.55s ease both; }

    footer { background-color: var(--primary-color); color: #94A3B8; }
    footer a { color: #CBD5E1; text-decoration: none; }
  </style>
</head>
<body>

  <!-- Include Header Navigasi -->
  <?php include 'header.php'; ?>

  <!-- Banner -->
  <div class="page-banner">
    <div class="container text-center">
      <span class="banner-chip"><i class="mdi mdi-account-group-outline mr-1"></i> Tim Kami</span>
      <h2 class="font-weight-bold mb-2">Staf Perpustakaan</h2>
      <p class="mb-0" style="opacity: 0.92; max-width: 560px; margin: 0 auto;">
        Kenali tim Perpustakaan Fakultas Teknik UNSRAT yang siap membantu kebutuhan literasi dan penelitian Anda.
      </p>
    </div>
  </div>

<?php if ($jumlahStaff === 0): ?>

  <section class="py-5">
    <div class="container">
      <div class="text-center py-5 bg-white rounded shadow-sm text-muted">
        <i class="mdi mdi-account-off-outline mdi-48px"></i>
        <p class="mb-0">Data staf perpustakaan belum tersedia.</p>
      </div>
    </div>
  </section>

<?php else: ?>

  <?php if ($featured): ?>
    <!-- Kartu utama: pimpinan -->
    <div class="container featured-wrap muncul">
      <div class="card featured-card">
        <div class="row no-gutters">
          <div class="col-md-4 featured-photo-panel">
            <?= foto_staff($featured, 'featured-photo'); ?>
          </div>
          <div class="col-md-8">
            <div class="featured-body">
              <span class="badge badge-warning px-3 py-2 mb-3" style="font-size: 0.78rem; color: #78350F;"><i class="mdi mdi-star-circle mr-1"></i>Pimpinan Perpustakaan</span>
              <h3 class="font-weight-bold text-dark mb-1" style="line-height: 1.35;"><?= h($featured['nama'] ?? '-'); ?></h3>
              <p class="font-weight-bold mb-3" style="color: var(--secondary-color);"><?= h($featured['jabatan'] ?? ''); ?></p>

              <?php if (!empty($featured['nip'])): ?>
                <div class="info-baris"><i class="mdi mdi-card-account-details-outline"></i><span>NIP. <?= h($featured['nip']); ?></span></div>
              <?php endif; ?>
              <?php if (!empty($featured['pendidikan'])): ?>
                <div class="info-baris"><i class="mdi mdi-school-outline"></i><span><?= h($featured['pendidikan']); ?></span></div>
              <?php endif; ?>
              <?php if (!empty($featured['email'])): ?>
                <div class="info-baris"><i class="mdi mdi-email-outline"></i><span><?= h($featured['email']); ?></span></div>
              <?php endif; ?>
              <?php if (!empty($featured['telepon'])): ?>
                <div class="info-baris"><i class="mdi mdi-phone-outline"></i><span><?= h($featured['telepon']); ?></span></div>
              <?php endif; ?>
              <?php if (!empty($featured['bidang'])): ?>
                <div class="info-baris"><i class="mdi mdi-briefcase-outline"></i><span><?= nl2br(h($featured['bidang'])); ?></span></div>
              <?php endif; ?>

              <div class="mt-3"><?= tombol_kontak($featured); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Daftar staf -->
  <section class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <h4 class="font-weight-bold text-dark mb-1">Tim Pelayanan Perpustakaan</h4>
        <p class="text-muted small mb-0"><?= $jumlahStaff; ?> staf siap melayani Anda</p>
      </div>

      <?php if (!empty($staffList)): ?>
        <div class="row justify-content-center">
          <?php foreach ($staffList as $i => $s): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 muncul" style="animation-delay: <?= min($i, 7) * 0.07; ?>s;">
              <div class="staff-card">
                <div class="staff-cover" style="background: <?= $gradien[$i % count($gradien)]; ?>;"></div>
                <?= foto_staff($s, 'staff-avatar'); ?>

                <div class="p-3 pt-3 text-center">
                  <div class="staff-nama mb-2"><?= h($s['nama'] ?? '-'); ?></div>
                  <span class="jabatan-pill mb-3"><?= h($s['jabatan'] ?? 'Staf'); ?></span>

                  <div class="staff-detail text-left mt-2 px-1">
                    <?php if (!empty($s['nip'])): ?>
                      <div class="mb-1"><i class="mdi mdi-card-account-details-outline"></i>NIP. <?= h($s['nip']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['pendidikan'])): ?>
                      <div class="mb-1"><i class="mdi mdi-school-outline"></i><?= h($s['pendidikan']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['bidang'])): ?>
                      <div class="mb-1"><i class="mdi mdi-briefcase-outline"></i><?= nl2br(h($s['bidang'])); ?></div>
                    <?php endif; ?>
                  </div>

                  <?php $kontak = tombol_kontak($s); ?>
                  <?php if ($kontak !== ''): ?>
                    <div class="mt-3 pt-3 border-top"><?= $kontak; ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

<?php endif; ?>

  <!-- Info layanan -->
  <section class="pb-5">
    <div class="container">
      <div class="layanan-card p-4 p-md-5">
        <div class="row align-items-center" style="position: relative; z-index: 2;">
          <div class="col-lg-4 mb-4 mb-lg-0">
            <h4 class="font-weight-bold mb-1">Butuh Bantuan?</h4>
            <p class="mb-0 small" style="opacity: 0.9;">Hubungi kami atau kunjungi langsung perpustakaan pada jam layanan.</p>
          </div>
          <div class="col-lg-8">
            <div class="row">
              <div class="col-md-4 mb-3 mb-md-0">
                <div class="layanan-item">
                  <div class="layanan-ikon"><i class="mdi mdi-clock-outline"></i></div>
                  <div><small style="opacity: 0.8;">Jam Layanan</small><div class="font-weight-bold small">Senin - Jumat<br>08:00 - 16:00 WITA</div></div>
                </div>
              </div>
              <div class="col-md-4 mb-3 mb-md-0">
                <div class="layanan-item">
                  <div class="layanan-ikon"><i class="mdi mdi-phone-outline"></i></div>
                  <div><small style="opacity: 0.8;">Telepon</small><div class="font-weight-bold small">(0431) 863-886</div></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="layanan-item">
                  <div class="layanan-ikon"><i class="mdi mdi-email-outline"></i></div>
                  <div style="min-width: 0;"><small style="opacity: 0.8;">Email</small><div class="font-weight-bold small" style="overflow-wrap: anywhere;">perpustakaan.fatek@unsrat.ac.id</div></div>
                </div>
              </div>
            </div>
          </div>
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
