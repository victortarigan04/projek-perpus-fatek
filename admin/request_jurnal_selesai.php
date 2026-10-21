<?php
// Pencegahan error session_start() ganda
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';

$success = '';
$error = '';

// Proses Edit Link Download Artikel Jurnal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_request'])) {
    $req_id        = $_POST['req_id'] ?? '';
    $link_download = trim($_POST['link_download'] ?? '');

    if (!empty($req_id)) {
        try {
            $db->collection('request_jurnal')->document($req_id)->set([
                'link_download' => $link_download,
                'updated_at'    => date('Y-m-d H:i:s')
            ], ['merge' => true]);
            $success = "Tautan artikel jurnal berhasil diperbarui!";
        } catch (Exception $e) {
            $error = "Gagal memperbarui tautan: " . $e->getMessage();
        }
    }
}

// Ambil daftar Request Jurnal yang Sudah Selesai (Status === 'Selesai')
$completedList = [];
try {
    $docs = $db->collection('request_jurnal')->orderBy('created_at', 'DESC')->documents();
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            
            if (($data['status'] ?? '') === 'Selesai') {
                $completedList[] = $data;
            }
        }
    }
} catch (Exception $e) {
    $completedList = [];
}

require 'layout/header.php';
require 'layout/sidebar.php';
?>

<!-- CDN DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<style>
  /* Perbaikan Warna Teks Menu Sidebar saat Aktif */
  .sidebar .nav-link.active, 
  .nav-pills .nav-link.active {
    background-color: #007bff !important;
    color: #ffffff !important;
  }
  .sidebar .nav-link.active *, 
  .nav-pills .nav-link.active * {
    color: #ffffff !important;
  }
  div.dataTables_wrapper div.dataTables_filter {
    text-align: right;
  }
</style>

<!-- Main Content Wrapper -->
<div class="content-wrapper p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="font-weight-bold text-dark mb-0">Request Jurnal Sudah Dikirim</h3>
      <p class="text-muted small mb-0">Arsip permohonan artikel jurnal dosen yang telah berhasil direspon & dikirimkan file artikelnya.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="mdi mdi-check-circle mr-1"></i> <?= $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle mr-1"></i> <?= $error; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Container Table Card -->
  <div class="card border-0 shadow-sm rounded-lg">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="font-weight-bold text-dark mb-0">
        <i class="mdi mdi-check-circle-outline text-success mr-2"></i>Daftar Request Sudah Dikirim
      </h5>
      <span class="badge badge-success px-3 py-2 font-weight-bold">
        <?= count($completedList); ?> Selesai
      </span>
    </div>
    <div class="card-body p-3">
      <div class="table-responsive">
        <table id="tableSelesai" class="table table-hover align-middle mb-0 w-100">
          <thead class="thead-light">
            <tr>
              <th style="width: 50px;">No</th>
              <th>Tanggal</th>
              <th>Nama Dosen & Kontak</th>
              <th>Jurusan</th>
              <th>Judul Artikel Jurnal</th>
              <th>Link File Artikel</th>
              <th>Status</th>
              <th class="text-center" style="width: 130px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($completedList)): ?>
              <?php foreach ($completedList as $idx => $item): ?>
                <tr>
                  <td><?= $idx + 1; ?></td>
                  <td><small class="text-muted"><?= htmlspecialchars(substr($item['created_at'] ?? '-', 0, 10)); ?></small></td>
                  <td>
                    <strong><?= htmlspecialchars($item['nama_dosen'] ?? '-'); ?></strong><br>
                    <small class="text-muted">NIP: <?= htmlspecialchars($item['nip_nidn'] ?? '-'); ?></small><br>
                    <small class="text-primary"><i class="mdi mdi-email-outline mr-1"></i><?= htmlspecialchars($item['email'] ?? '-'); ?></small>
                  </td>
                  <td><span class="badge badge-light border"><?= htmlspecialchars($item['jurusan'] ?? '-'); ?></span></td>
                  <td style="max-width: 250px;" class="text-truncate" title="<?= htmlspecialchars($item['judul_artikel'] ?? ''); ?>">
                    <?= htmlspecialchars($item['judul_artikel'] ?? '-'); ?>
                  </td>
                  <td>
                    <?php if (!empty($item['link_download'])): ?>
                      <a href="<?= htmlspecialchars($item['link_download']); ?>" target="_blank" class="btn btn-sm btn-outline-success font-weight-bold">
                        <i class="mdi mdi-open-in-new mr-1"></i> Buka File
                      </a>
                    <?php else: ?>
                      <span class="text-muted small">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-success px-2 py-1"><i class="mdi mdi-check-circle-outline mr-1"></i>Selesai</span>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary font-weight-bold" data-toggle="modal" data-target="#modalEdit<?= $item['id']; ?>">
                      <i class="mdi mdi-pencil mr-1"></i> Ubah Link
                    </button>
                  </td>
                </tr>

                <!-- Modal Edit Link Download -->
                <div class="modal fade" id="modalEdit<?= $item['id']; ?>" tabindex="-1" role="dialog">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <form action="" method="post">
                        <input type="hidden" name="req_id" value="<?= $item['id']; ?>">
                        <div class="modal-header bg-primary text-white">
                          <h5 class="modal-title font-weight-bold">Ubah Tautan File Artikel</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body text-left">
                          <div class="mb-3 p-2 bg-light rounded border">
                            <p class="small text-muted mb-1"><strong>Pemohon:</strong> <?= htmlspecialchars($item['nama_dosen']); ?></p>
                            <p class="small text-muted mb-0"><strong>Judul Artikel:</strong> <?= htmlspecialchars($item['judul_artikel']); ?></p>
                          </div>

                          <div class="form-group mb-3">
                            <label class="font-weight-bold small">Link Download Artikel Baru</label>
                            <input type="url" name="link_download" class="form-control" value="<?= htmlspecialchars($item['link_download'] ?? ''); ?>" required>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                          <button type="submit" name="edit_request" class="btn btn-primary btn-sm font-weight-bold">Simpan Perubahan</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>

<!-- CDN DataTables JS & Script Inisialisasi -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(document).ready(function() {
    $('#tableSelesai').DataTable({
      "language": {
        "search": "Cari Data:",
        "lengthMenu": "Tampilkan _MENU_ data",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Tidak ada data yang tersedia",
        "paginate": {
          "first": "Awal",
          "last": "Akhir",
          "next": "&raquo;",
          "previous": "&laquo;"
        }
      }
    });
  });
</script>