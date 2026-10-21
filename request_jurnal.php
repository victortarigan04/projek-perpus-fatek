<?php
session_start();
require_once 'admin/koneksi.php';

$reqMsg = '';
$reqErr = '';

// Inisialisasi Captcha jika belum ada di Session
if (!isset($_SESSION['captcha_req_a']) || !isset($_SESSION['captcha_req_b'])) {
    $_SESSION['captcha_req_a'] = rand(1, 9);
    $_SESSION['captcha_req_b'] = rand(1, 9);
}

if (!isset($_SESSION['captcha_track_a']) || !isset($_SESSION['captcha_track_b'])) {
    $_SESSION['captcha_track_a'] = rand(1, 9);
    $_SESSION['captcha_track_b'] = rand(1, 9);
}

// Handling Kirim Permintaan Request Jurnal dari Dosen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_request_jurnal'])) {
    $user_captcha  = intval($_POST['captcha_req'] ?? -1);
    $correct_ans   = $_SESSION['captcha_req_a'] + $_SESSION['captcha_req_b'];

    $nama_dosen    = trim($_POST['nama_dosen'] ?? '');
    $nip_nidn      = trim($_POST['nip_nidn'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $jurusan       = trim($_POST['jurusan'] ?? '');
    $judul_artikel = trim($_POST['judul_artikel'] ?? '');
    $doi_scopus    = trim($_POST['doi_scopus'] ?? '');
    $catatan       = trim($_POST['catatan'] ?? '');

    // Reset Captcha untuk pengiriman berikutnya
    $_SESSION['captcha_req_a'] = rand(1, 9);
    $_SESSION['captcha_req_b'] = rand(1, 9);

    if ($user_captcha !== $correct_ans) {
        $reqErr = "Jawaban Captcha keamanan tidak sesuai. Silakan coba lagi.";
    } elseif (!empty($nama_dosen) && !empty($judul_artikel)) {
        try {
            $db->collection('request_jurnal')->add([
                'nama_dosen'    => $nama_dosen,
                'nip_nidn'      => $nip_nidn,
                'email'         => $email,
                'jurusan'       => $jurusan,
                'judul_artikel' => $judul_artikel,
                'doi_scopus'    => $doi_scopus,
                'catatan'       => $catatan,
                'status'        => 'Menunggu',
                'link_download' => '',
                'created_at'    => date('Y-m-d H:i:s')
            ]);
            $reqMsg = "Permintaan artikel jurnal Anda berhasil dikirim! Silakan lacak statusnya menggunakan Judul Artikel yang telah diajukan.";
        } catch (Exception $e) {
            $reqErr = "Gagal mengirim permintaan: " . $e->getMessage();
        }
    } else {
        $reqErr = "Mohon lengkapi Nama Dosen dan Judul Artikel Jurnal.";
    }
}

// Handling Pelacakan Status Berdasarkan Judul Artikel Jurnal (pencarian LIKE / contains)
$trackingResults = [];
$trackingQuery = '';
$trackErr = '';

if (isset($_GET['cek_judul'])) {
    $user_track_captcha = intval($_GET['captcha_track'] ?? -1);
    $correct_track_ans  = $_SESSION['captcha_track_a'] + $_SESSION['captcha_track_b'];
    $trackingQuery      = trim($_GET['cek_judul'] ?? '');

    // Reset Captcha Lacak
    $_SESSION['captcha_track_a'] = rand(1, 9);
    $_SESSION['captcha_track_b'] = rand(1, 9);

    if ($user_track_captcha !== $correct_track_ans) {
        $trackErr = "Jawaban Captcha keamanan tidak sesuai. Silakan coba lagi.";
    } elseif (mb_strlen($trackingQuery, 'UTF-8') < 3) {
        $trackErr = "Masukkan minimal 3 karakter dari judul artikel.";
    } else {
        try {
            $keyword = mb_strtolower($trackingQuery, 'UTF-8');
            $docs = $db->collection('request_jurnal')->documents();

            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                $data  = $doc->data();
                $judul = mb_strtolower($data['judul_artikel'] ?? '', 'UTF-8');

                // Setara dengan LIKE '%keyword%' (tidak peka huruf besar/kecil)
                if (mb_strpos($judul, $keyword) !== false) {
                    $trackingResults[] = $data;
                }
            }

            // Urutkan terbaru di atas, lalu batasi maksimal 10 hasil
            usort($trackingResults, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });
            $trackingResults = array_slice($trackingResults, 0, 10);
        } catch (Exception $e) {
            $trackingResults = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Jurnal Dosen - Elibrary Fatek UNSRAT</title>
  
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
      background-color: #EEF2F6;
      color: var(--text-dark);
    }

    .top-bar { background-color: var(--primary-color); color: #E2E8F0; font-size: 0.85rem; }
    .navbar-light-custom { background-color: #FFFFFF !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }

    .nav-link { color: var(--text-dark) !important; font-weight: 500; padding: 0.5rem 1rem !important; }
    .nav-link:hover, .nav-item.active .nav-link { color: var(--secondary-color) !important; }

    .btn-navy { background-color: #1B2A4A; color: #FFFFFF; font-weight: 600; border: none; }
    .btn-navy:hover { background-color: #0F172A; color: #FFFFFF; }

    .btn-accent { background-color: var(--accent-color); color: #FFFFFF; font-weight: 600; border: none; }
    .btn-accent:hover { background-color: #D97706; color: #FFFFFF; }

    .btn-primary-custom { background-color: var(--primary-color); color: #FFFFFF; border: none; font-weight: 500; }
    .btn-primary-custom:hover { background-color: #172554; color: #FFFFFF; }

    .captcha-badge {
      background: #E2E8F0;
      color: #1E293B;
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 2px;
      padding: 6px 16px;
      border-radius: 8px;
      display: inline-block;
      user-select: none;
    }

    footer { background-color: var(--primary-color); color: #94A3B8; }
    footer a { color: #CBD5E1; text-decoration: none; }
  </style>
</head>
<body>

  <!-- Include Header Navigasi -->
  <?php include 'header.php'; ?>

  <!-- Content Request Jurnal & Pelacakan -->
  <div class="container py-5">
    <div class="mb-4 text-center">
      <h2 class="font-weight-bold text-dark mb-2"><i class="mdi mdi-file-document-edit-outline text-primary mr-2"></i>Layanan Request Jurnal Dosen FT UNSRAT</h2>
      <p class="text-muted small">Khusus dosen Fakultas Teknik Unsrat. Silakan isi formulir atau melacak status permintaan berdasarkan Judul Artikel Jurnal.</p>
    </div>

    <?php if (!empty($reqMsg)): ?>
      <div class="alert alert-success alert-dismissible fade show mb-4"><?= $reqMsg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if (!empty($reqErr)): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-4"><?= $reqErr; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div class="row">
      <!-- FORM KIRIM REQUEST -->
      <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 12px; background: #FFFFFF;">
          <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2">Formulir Pengajuan Jurnal</h5>
          
          <form action="request_jurnal.php" method="post">
            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">Nama dosen</label>
              <input type="text" name="nama_dosen" class="form-control" required>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">NIP / NIDN</label>
              <input type="text" name="nip_nidn" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">Email</label>
              <input type="email" name="email" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">Jurusan</label>
              <select name="jurusan" class="form-control">
                <option value="">Pilih...</option>
                <option value="Teknik Sipil">Teknik Sipil</option>
                <option value="Teknik Arsitektur">Teknik Arsitektur</option>
                <option value="Teknik Mesin">Teknik Mesin</option>
                <option value="Teknik Elektro">Teknik Elektro</option>
                <option value="Teknik Perencanaan Wilayah & Kota">Teknik Perencanaan Wilayah & Kota</option>
                <option value="Teknik Lingkungan">Teknik Lingkungan</option>
              </select>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">Judul artikel</label>
              <input type="text" name="judul_artikel" class="form-control" required>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">DOI atau tautan Scopus</label>
              <input type="text" name="doi_scopus" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">Catatan (keperluan)</label>
              <textarea name="catatan" class="form-control" rows="4"></textarea>
            </div>

            <!-- CAPTCHA FORM REQUEST -->
            <div class="form-group mb-4 p-3 bg-light rounded border">
              <label class="font-weight-bold text-dark small d-block mb-2"><i class="mdi mdi-shield-check-outline text-primary mr-1"></i> Verifikasi Keamanan (Captcha)</label>
              <div class="d-flex align-items-center flex-wrap">
                <span class="captcha-badge mr-3 mb-2"><?= $_SESSION['captcha_req_a']; ?> + <?= $_SESSION['captcha_req_b']; ?> = ?</span>
                <input type="number" name="captcha_req" class="form-control mb-2" style="max-width: 150px;" placeholder="Hasil penjumlahan" required>
              </div>
              <small class="text-muted d-block mt-1">Masukkan hasil penjumlahan angka di atas untuk memverifikasi Anda bukan robot.</small>
            </div>

            <button type="submit" name="kirim_request_jurnal" class="btn btn-navy px-4 py-2">Kirim permintaan</button>
          </form>
        </div>
      </div>

      <!-- FORM PELACAKAN BERDASARKAN JUDUL ARTIKEL -->
      <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 12px; background: #FFFFFF;">
          <h5 class="font-weight-bold text-dark mb-3 border-bottom pb-2">Lacak Status Permintaan</h5>

          <?php if (!empty($trackErr)): ?>
            <div class="alert alert-danger alert-dismissible fade show small mb-3"><?= $trackErr; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
          <?php endif; ?>
          
          <form action="request_jurnal.php" method="get">
            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small">Judul artikel jurnal</label>
              <input type="text" name="cek_judul" class="form-control" placeholder="Ketik judul atau sebagian kata dari judul artikel (min. 3 karakter)" value="<?= htmlspecialchars($trackingQuery); ?>" required>
            </div>

            <!-- CAPTCHA FORM TRACKING -->
            <div class="form-group mb-3 p-3 bg-light rounded border">
              <label class="font-weight-bold text-dark small d-block mb-2"><i class="mdi mdi-shield-check-outline text-primary mr-1"></i> Verifikasi Keamanan (Captcha)</label>
              <div class="d-flex align-items-center flex-wrap">
                <span class="captcha-badge mr-2 mb-2" style="font-size: 0.95rem; padding: 4px 12px;"><?= $_SESSION['captcha_track_a']; ?> + <?= $_SESSION['captcha_track_b']; ?> = ?</span>
                <input type="number" name="captcha_track" class="form-control mb-2" style="max-width: 120px;" placeholder="Hasil" required>
              </div>
            </div>

            <button type="submit" class="btn btn-navy px-4 py-2">Cek Status</button>
          </form>

          <?php if (isset($_GET['cek_judul']) && empty($trackErr)): ?>
            <div class="mt-4 pt-3 border-top">
              <?php if (!empty($trackingResults)): ?>
                <small class="text-muted d-block mb-2"><?= count($trackingResults); ?> hasil ditemukan</small>

                <?php foreach ($trackingResults as $row): ?>
                  <div class="p-3 bg-light rounded border mb-3">
                    <small class="text-muted d-block mb-1">Judul Artikel:</small>
                    <strong class="text-dark d-block mb-2"><?= htmlspecialchars($row['judul_artikel'] ?? '-'); ?></strong>
                    
                    <small class="text-muted d-block mb-1">Status Permintaan:</small>
                    <?php 
                      $st = $row['status'] ?? 'Menunggu';
                      $badge = ($st === 'Selesai') ? 'badge-success' : (($st === 'Diproses') ? 'badge-warning' : 'badge-secondary');
                    ?>
                    <span class="badge <?= $badge; ?> px-3 py-1 mb-3" style="font-size: 0.85rem;"><?= htmlspecialchars($st); ?></span>

                    <?php if (!empty($row['link_download'])): ?>
                      <div class="mt-2">
                        <small class="text-success font-weight-bold d-block mb-2"><i class="mdi mdi-check-circle mr-1"></i>Artikel Jurnal Siap Diunduh:</small>
                        <a href="<?= htmlspecialchars($row['link_download']); ?>" target="_blank" class="btn btn-accent font-weight-bold btn-block">
                          <i class="mdi mdi-download mr-1"></i> Unduh Artikel Jurnal
                        </a>
                      </div>
                    <?php else: ?>
                      <p class="small text-muted mb-0 mt-2"><i class="mdi mdi-clock-outline mr-1"></i>Artikel masih diproses oleh tim admin perpustakaan. Silakan cek secara berkala.</p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="alert alert-warning mb-0 small">
                  <i class="mdi mdi-alert-circle-outline mr-1"></i> Tidak ada permintaan yang judulnya mengandung kata tersebut. Coba kata kunci lain.
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Include Footer -->
  <?php include 'footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>