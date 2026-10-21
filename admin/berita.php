<?php
require 'layout/header.php';
require 'layout/sidebar.php';

// Variabel default untuk Mode Edit
$isEdit = false;
$editId = '';
$editData = [
    'judul'    => '',
    'kategori' => 'Berita Utama',
    'status'   => 'Publish',
    'penulis'  => $_SESSION['admin_nama'] ?? 'Admin',
    'gambar'   => '',
    'isi'      => ''
];

// 1. PROSES AMBIL DATA UNTUK EDIT
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $editId = $_GET['id'];
    $doc = $db->collection('berita')->document($editId)->snapshot();
    if ($doc->exists()) {
        $isEdit = true;
        $editData = array_merge($editData, $doc->data());
    } else {
        echo "<script>alert('Data berita tidak ditemukan!'); location.href='berita.php';</script>";
        exit;
    }
}

// 2. PROSES HAPUS BERITA
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $doc = $db->collection('berita')->document($id)->snapshot();
    if ($doc->exists()) {
        $data = $doc->data();
        if (!empty($data['gambar']) && file_exists(__DIR__ . '/' . $data['gambar'])) {
            @unlink(__DIR__ . '/' . $data['gambar']);
        }
        $db->collection('berita')->document($id)->delete();
    }
    echo "<script>alert('Berita berhasil dihapus!'); location.href='berita.php';</script>";
    exit;
}

// 3. PROSES SIMPAN (TAMBAH / UPDATE)
if (isset($_POST['simpan_berita'])) {
    $judul    = trim($_POST['judul']);
    $kategori = $_POST['kategori'];
    $status   = $_POST['status'];
    $penulis  = trim($_POST['penulis']);
    $isi      = $_POST['isi'];
    $gambarPath = $_POST['gambar_lama'] ?? '';

    // Upload Gambar Baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($ext, $allowed)) {
            $folderUpload = __DIR__ . '/uploads/';
            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0777, true);
            }
            
            $namaGambar = 'berita_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $folderUpload . $namaGambar;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                if (!empty($_POST['gambar_lama']) && file_exists(__DIR__ . '/' . $_POST['gambar_lama'])) {
                    @unlink(__DIR__ . '/' . $_POST['gambar_lama']);
                }
                $gambarPath = 'uploads/' . $namaGambar;
            }
        }
    }

    $payload = [
        'judul'      => $judul,
        'kategori'   => $kategori,
        'status'     => $status,
        'penulis'    => $penulis,
        'gambar'     => $gambarPath,
        'isi'        => $isi,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($_POST['id_berita'])) {
        // Mode Update
        $db->collection('berita')->document($_POST['id_berita'])->set($payload, ['merge' => true]);
        echo "<script>alert('Berita berhasil diperbarui!'); location.href='berita.php';</script>";
    } else {
        // Mode Tambah Baru
        $payload['tanggal']    = date('Y-m-d H:i:s');
        $payload['created_at'] = time();
        $db->collection('berita')->add($payload);
        echo "<script>alert('Berita berhasil diterbitkan!'); location.href='berita.php';</script>";
    }
    exit;
}

// 4. AMBIL DAFTAR BERITA DARI FIRESTORE
// Query PHP
$beritaList = $db->collection('berita')->orderBy('created_at', 'DESC')->documents();
?>

<!-- TinyMCE Editor CDN -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
  tinymce.init({
    selector: '#editor_isi',
    height: 320,
    menubar: false,
    plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'table', 'code', 'help', 'wordcount'],
    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat | table link image | code'
  });
</script>

<div class="content-wrapper">
  <!-- Content Header -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-newspaper mr-2 text-primary"></i>Kelola Berita & Pengumuman</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Berita</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <section class="content">
    <div class="container-fluid">

      <!-- Tombol Pemicu Buka/Tutup Form Tambah Berita -->
      <div class="mb-3">
        <?php if (!$isEdit): ?>
          <button class="btn btn-primary font-weight-bold shadow-sm" type="button" data-toggle="collapse" data-target="#formBeritaCollapse" aria-expanded="false" aria-controls="formBeritaCollapse">
            <i class="fas fa-plus-circle mr-1"></i> + Tambah Berita Baru
          </button>
        <?php else: ?>
          <a href="berita.php" class="btn btn-secondary font-weight-bold shadow-sm">
            <i class="fas fa-times mr-1"></i> Batal Edit / Reset Form
          </a>
        <?php endif; ?>
      </div>

      <!-- Container Form (Default Hidden, Otomatis Terbuka Jika Mode Edit) -->
      <div class="collapse <?= $isEdit ? 'show' : ''; ?> mb-4" id="formBeritaCollapse">
        <div class="card <?= $isEdit ? 'card-warning' : 'card-primary'; ?> card-outline elevation-2 mb-0">
          <div class="card-header <?= $isEdit ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
            <h3 class="card-title font-weight-bold">
              <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?> mr-1"></i>
              <?= $isEdit ? 'Edit Artikel Berita' : 'Form Buat Berita Baru'; ?>
            </h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#formBeritaCollapse">
                <i class="fas fa-times <?= $isEdit ? 'text-dark' : 'text-white'; ?>"></i>
              </button>
            </div>
          </div>

          <form method="post" enctype="multipart/form-data">
            <!-- Hidden Inputs -->
            <input type="hidden" name="id_berita" value="<?= htmlspecialchars($editId); ?>">
            <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($editData['gambar']); ?>">

            <div class="card-body">
              <div class="row">
                <!-- Judul -->
                <div class="col-md-8">
                  <div class="form-group">
                    <label>Judul Berita / Postingan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul artikel..." value="<?= htmlspecialchars($editData['judul']); ?>" required>
                  </div>
                </div>

                <!-- Kategori -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Kategori Konten <span class="text-danger">*</span></label>
                    <select name="kategori" class="form-control" required>
                      <option value="Berita Utama" <?= $editData['kategori'] === 'Berita Utama' ? 'selected' : ''; ?>>Berita Utama</option>
                      <option value="Pengumuman" <?= $editData['kategori'] === 'Pengumuman' ? 'selected' : ''; ?>>Pengumuman Resmi</option>
                      <option value="Agenda Kegiatan" <?= $editData['kategori'] === 'Agenda Kegiatan' ? 'selected' : ''; ?>>Agenda Kegiatan</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <!-- Penulis -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Penulis / Admin <span class="text-danger">*</span></label>
                    <input type="text" name="penulis" class="form-control" value="<?= htmlspecialchars($editData['penulis']); ?>" required>
                  </div>
                </div>

                <!-- Status Posting -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Status Posting <span class="text-danger">*</span></label>
                    <select name="status" class="form-control" required>
                      <option value="Publish" <?= $editData['status'] === 'Publish' ? 'selected' : ''; ?>>Publish</option>
                      <option value="Draft" <?= $editData['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                  </div>
                </div>

                <!-- Upload Gambar -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Gambar Sampul <?= $isEdit ? '(Biarkan kosong jika tidak diubah)' : '(Opsional)'; ?></label>
                    <input type="file" name="gambar" class="form-control-file mb-1" accept="image/*">
                    <?php if (!empty($editData['gambar']) && file_exists(__DIR__ . '/' . $editData['gambar'])): ?>
                      <small class="text-success d-block"><i class="fas fa-image mr-1"></i> Gambar saat ini: <b><?= htmlspecialchars(basename($editData['gambar'])); ?></b></small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Isi Artikel (TinyMCE) -->
              <div class="form-group">
                <label>Isi Artikel Berita <span class="text-danger">*</span></label>
                <textarea name="isi" id="editor_isi"><?= htmlspecialchars($editData['isi']); ?></textarea>
              </div>
            </div>

            <div class="card-footer text-right bg-light">
              <?php if ($isEdit): ?>
                <a href="berita.php" class="btn btn-secondary mr-2"><i class="fas fa-undo mr-1"></i> Batal</a>
                <button type="submit" name="simpan_berita" class="btn btn-warning px-4 font-weight-bold">
                  <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
              <?php else: ?>
                <button type="submit" name="simpan_berita" class="btn btn-success px-4 font-weight-bold">
                  <i class="fas fa-paper-plane mr-1"></i> Terbitkan Artikel
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Tabel Daftar Berita -->
      <div class="card elevation-2">
        <div class="card-header bg-dark">
          <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Daftar Berita & Pengumuman</h3>
        </div>
        <div class="card-body p-0 table-responsive">
          <!-- Tag Tabel -->
<table id="tableBerita" class="table table-hover table-striped align-middle mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 80px;">Sampul</th>
                <th>Judul & Penulis</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Tanggal Upload</th>
                <th class="text-center" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $adaData = false;
              foreach ($beritaList as $doc):
                if ($doc->exists()):
                  $adaData = true;
                  $d = $doc->data();
                  $gambar = !empty($d['gambar']) && file_exists(__DIR__ . '/' . $d['gambar']) ? $d['gambar'] : 'https://via.placeholder.com/150x100?text=No+Image';
                  $badgeStatus = ($d['status'] ?? 'Publish') === 'Publish' ? 'badge-success' : 'badge-secondary';
              ?>
                <tr>
                  <td class="align-middle text-center">
                    <img src="<?= $gambar; ?>" alt="Cover" class="img-thumbnail" style="width: 70px; height: 50px; object-fit: cover;">
                  </td>
                  <td class="align-middle">
                    <strong class="text-primary font-weight-bold d-block"><?= htmlspecialchars($d['judul'] ?? 'Tanpa Judul'); ?></strong>
                    <small class="text-muted"><i class="fas fa-user-edit mr-1"></i> <?= htmlspecialchars($d['penulis'] ?? 'Admin'); ?></small>
                  </td>
                  <td class="align-middle">
                    <span class="badge badge-info p-2"><?= htmlspecialchars($d['kategori'] ?? 'Berita'); ?></span>
                  </td>
                  <td class="align-middle">
                    <span class="badge <?= $badgeStatus; ?> p-2"><?= htmlspecialchars($d['status'] ?? 'Publish'); ?></span>
                  </td>
                  <td class="align-middle text-muted small">
                    <i class="far fa-calendar-alt mr-1"></i> <?= htmlspecialchars(substr($d['created_at'] ?? '-', 0, 16)); ?>
                  </td>
                  <td class="align-middle text-center">
                    <a href="berita.php?action=edit&id=<?= $doc->id(); ?>" 
                       class="btn btn-sm btn-warning font-weight-bold" 
                       title="Edit Berita">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="berita.php?action=hapus&id=<?= $doc->id(); ?>" 
                       class="btn btn-sm btn-danger ml-1" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus berita ini?')" 
                       title="Hapus Berita">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php
                endif;
              endforeach;

              if (!$adaData):
              ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">Belum ada artikel berita atau pengumuman yang diunggah.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<?php require 'layout/footer.php'; ?>

<script>
  $(document).ready(function() {
    $('#tableBerita').DataTable({
      "order": [],
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
      }
    });
  });
</script>