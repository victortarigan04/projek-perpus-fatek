<?php session_start();
require_once 'koneksi.php';

$success = '';
$error = '';

// Proses Update Link Download & Status Request Jurnal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $req_id        = $_POST['req_id'] ?? '';
    $status        = $_POST['status'] ?? 'Diproses';
    $link_download = trim($_POST['link_download'] ?? '');

    if (!empty($req_id)) {
        try {
            $db->collection('request_jurnal')->document($req_id)->set([
                'status'        => $status,
                'link_download' => $link_download,
                'updated_at'    => date('Y-m-d H:i:s')
            ], ['merge' => true]);
            $success = "Respon permohonan jurnal berhasil disimpan!";
        } catch (Exception $e) {
            $error = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
}

// Ambil daftar Request Jurnal yang Belum Direspon (Status !== 'Selesai')
$pendingList = [];
try {
    $docs = $db->collection('request_jurnal')->orderBy('created_at', 'DESC')->documents();
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            
            if (($data['status'] ?? 'Menunggu') !== 'Selesai') {
                $pendingList[] = $data;
            }
        }
    }
} catch (Exception $e) {
    $pendingList = [];
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
  /* Style Penyesuaian DataTables */
  div.dataTables_wrapper div.dataTables_filter {
    text-align: right;
  }
</style>

<!-- Main Content Wrapper -->
<div class="content-wrapper p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="font-weight-bold text-dark mb-0">Request Jurnal Belum Direspon</h3>
      <p class="text-muted small mb-0">Kelola daftar permohonan artikel jurnal dosen yang masih dalam antrean respon.</p>
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
        <i class="mdi mdi-clock-outline text-warning mr-2"></i>Daftar Request Belum Direspon
      </h5>
      <span class="badge badge-warning text-dark px-3 py-2 font-weight-bold">
        <?= count($pendingList); ?> Menunggu Respon
      </span>
    </div>
    <div class="card-body p-3">
      <div class="table-responsive">
        <table id="tablePending" class="table table-hover align-middle mb-0 w-100">
          <thead class="thead-light">
            <tr>
              <th style="width: 50px;">No</th>
              <th>Tanggal</th>
              <th>Nama Dosen & Kontak</th>
              <th>Jurusan</th>
              <th>Judul Artikel Jurnal</th>
              <th>DOI / Ref</th>
              <th>Status</th>
              <th class="text-center" style="width: 140px;">Aksi Respon</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($pendingList)): ?>
              <?php foreach ($pendingList as $idx => $item): ?>
                <tr>
                  <td><?= $idx + 1; ?></td>
                  <td><small class="text-muted"><?= htmlspecialchars(substr($item['created_at'] ?? '-', 0, 10)); ?></small></td>
                  <td>
                    <strong><?= htmlspecialchars($item['nama_dosen'] ?? '-'); ?></strong><br>
                    <small class="text-muted">NIP: <?= htmlspecialchars($item['nip_nidn'] ?? '-'); ?></small><br>
                    <small class="text-primary"><i class="mdi mdi-email-outline mr-1"></i><?= htmlspecialchars($item['email'] ?? '-'); ?></small>
                  </td>
                  <td><span class="badge badge-light border"><?= htmlspecialchars($item['jurusan'] ?? '-'); ?></span></td>
                  <td style="max-width: 260px;" class="text-truncate" title="<?= htmlspecialchars($item['judul_artikel'] ?? ''); ?>">
                    <?= htmlspecialchars($item['judul_artikel'] ?? '-'); ?>
                  </td>
                  <td><small><?= htmlspecialchars($item['doi_scopus'] ?? '-'); ?></small></td>
                  <td>
                    <?php 
                      $st = $item['status'] ?? 'Menunggu';
                      $badgeClass = ($st === 'Diproses') ? 'badge-info' : 'badge-warning text-dark';
                    ?>
                    <span class="badge <?= $badgeClass; ?> px-2 py-1"><?= htmlspecialchars($st); ?></span>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-primary font-weight-bold" data-toggle="modal" data-target="#modalRespon<?= $item['id']; ?>">
                      <i class="mdi mdi-reply mr-1"></i> Respon
                    </button>
                  </td>
                </tr>

                <!-- Modal Form Respon -->
                <div class="modal fade" id="modalRespon<?= $item['id']; ?>" tabindex="-1" role="dialog">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <form action="" method="post">
                        <input type="hidden" name="req_id" value="<?= $item['id']; ?>">
                        <div class="modal-header bg-primary text-white">
                          <h5 class="modal-title font-weight-bold">Respon Request Jurnal Dosen</h5>
                          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body text-left">
                          <div class="mb-3 p-2 bg-light rounded border">
                            <p class="small text-muted mb-1"><strong>Pemohon:</strong> <?= htmlspecialchars($item['nama_dosen']); ?> (<?= htmlspecialchars($item['jurusan']); ?>)</p>
                            <p class="small text-muted mb-0"><strong>Judul Artikel:</strong> <?= htmlspecialchars($item['judul_artikel']); ?></p>
                          </div>

                          <div class="form-group mb-3">
                            <label class="font-weight-bold small">Status Request</label>
                            <select name="status" class="form-control" required>
                              <option value="Diproses" <?= ($item['status'] ?? '') === 'Diproses' ? 'selected' : ''; ?>>Diproses (Sedang Dicari)</option>
                              <option value="Selesai" selected>Selesai (Sudah Dikirim Link Artikel)</option>
                            </select>
                          </div>

                          <div class="form-group mb-3">
                            <label class="font-weight-bold small">Link Download Artikel (PDF / Drive / DOI)</label>
                            <input type="url" name="link_download" class="form-control" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($item['link_download'] ?? ''); ?>" required>
                            <small class="text-muted d-block mt-1">Tautan ini dapat diakses oleh dosen untuk mengunduh artikel jurnal yang diminta.</small>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                          <button type="submit" name="update_request" class="btn btn-primary btn-sm font-weight-bold">Simpan Respon</button>
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
    $('#tablePending').DataTable({
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