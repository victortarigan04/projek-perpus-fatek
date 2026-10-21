<?php
require 'layout/header.php';
require 'layout/sidebar.php';

$isEdit = false;
$editId = '';
$editData = [
    'teks_slide' => '',
    'gambar'     => '',
    'status'     => 'aktif'
];

// 1. PROSES AMBIL DATA EDIT
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $editId = $_GET['id'];
    $doc = $db->collection('slides')->document($editId)->snapshot();
    if ($doc->exists()) {
        $isEdit = true;
        $editData = array_merge($editData, $doc->data());
    } else {
        echo "<script>alert('Data slide tidak ditemukan!'); location.href='slide.php';</script>";
        exit;
    }
}

// 2. PROSES HAPUS SLIDE
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $doc = $db->collection('slides')->document($id)->snapshot();
    if ($doc->exists()) {
        $data = $doc->data();
        if (!empty($data['gambar']) && file_exists(__DIR__ . '/' . $data['gambar'])) {
            @unlink(__DIR__ . '/' . $data['gambar']);
        }
        $db->collection('slides')->document($id)->delete();
    }
    echo "<script>alert('Slide banner berhasil dihapus!'); location.href='slide.php';</script>";
    exit;
}

// 3. PROSES SIMPAN (TAMBAH / UPDATE)
if (isset($_POST['simpan_slide'])) {
    $teksSlide  = trim($_POST['teks_slide']);
    $status     = $_POST['status'] ?? 'aktif';
    $gambarPath = $_POST['gambar_lama'] ?? '';

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $folderUpload = __DIR__ . '/uploads/slide/';
            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0777, true);
            }

            $namaGambar = 'slide_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $folderUpload . $namaGambar;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                if (!empty($_POST['gambar_lama']) && file_exists(__DIR__ . '/' . $_POST['gambar_lama'])) {
                    @unlink(__DIR__ . '/' . $_POST['gambar_lama']);
                }
                $gambarPath = 'uploads/slide/' . $namaGambar;
            }
        }
    }

    $payload = [
        'teks_slide' => $teksSlide,
        'gambar'     => $gambarPath,
        'status'     => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($_POST['id_slide'])) {
        $db->collection('slides')->document($_POST['id_slide'])->set($payload, ['merge' => true]);
        echo "<script>alert('Slide banner berhasil diperbarui!'); location.href='slide.php';</script>";
    } else {
        $payload['created_at'] = time();
        $db->collection('slides')->add($payload);
        echo "<script>alert('Slide banner berhasil ditambahkan!'); location.href='slide.php';</script>";
    }
    exit;
}

$slideList = $db->collection('slides')->orderBy('created_at', 'DESC')->documents();
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-images mr-2 text-primary"></i>Kelola Slide Banner Front-End</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Slide Banner</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="mb-3">
        <?php if (!$isEdit): ?>
          <button class="btn btn-primary font-weight-bold shadow-sm" type="button" data-toggle="collapse" data-target="#formSlideCollapse">
            <i class="fas fa-plus-circle mr-1"></i> + Tambah Slide Baru
          </button>
        <?php else: ?>
          <a href="slide.php" class="btn btn-secondary font-weight-bold shadow-sm">
            <i class="fas fa-times mr-1"></i> Batal Edit / Reset Form
          </a>
        <?php endif; ?>
      </div>

      <div class="collapse <?= $isEdit ? 'show' : ''; ?> mb-4" id="formSlideCollapse">
        <div class="card <?= $isEdit ? 'card-warning' : 'card-primary'; ?> card-outline elevation-2 mb-0">
          <div class="card-header <?= $isEdit ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
            <h3 class="card-title font-weight-bold">
              <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?> mr-1"></i>
              <?= $isEdit ? 'Edit Slide Banner' : 'Form Input Slide Banner Baru'; ?>
            </h3>
          </div>

          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_slide" value="<?= htmlspecialchars($editId); ?>">
            <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($editData['gambar']); ?>">

            <div class="card-body">
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label>Judul / Teks Utama Slide <span class="text-danger">*</span></label>
                    <input type="text" name="teks_slide" class="form-control" placeholder="Contoh: Pusat Literasi & Riset Ilmiah..." value="<?= htmlspecialchars($editData['teks_slide']); ?>" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Status Slide <span class="text-danger">*</span></label>
                    <select name="status" class="form-control" required>
                      <option value="aktif" <?= $editData['status'] === 'aktif' ? 'selected' : ''; ?>>Aktif (Tampil di Front-End)</option>
                      <option value="tidak_aktif" <?= $editData['status'] === 'tidak_aktif' ? 'selected' : ''; ?>>Tidak Aktif (Disembunyikan)</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label>Upload Gambar Banner <?= $isEdit ? '(Biarkan kosong jika tidak diubah)' : '<span class="text-danger">*</span>'; ?></label>
                    <input type="file" name="gambar" class="form-control-file mb-1" accept="image/*" <?= $isEdit ? '' : 'required'; ?>>
                    <small class="text-muted d-block">Rekomendasi ukuran gambar: 1920 x 800 piksel (Format JPG, PNG, WEBP).</small>
                    <?php if (!empty($editData['gambar']) && file_exists(__DIR__ . '/' . $editData['gambar'])): ?>
                      <img src="<?= htmlspecialchars($editData['gambar']); ?>" alt="Preview" class="img-thumbnail mt-2" style="max-height: 120px;">
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-footer text-right bg-light">
              <?php if ($isEdit): ?>
                <a href="slide.php" class="btn btn-secondary mr-2"><i class="fas fa-undo mr-1"></i> Batal</a>
                <button type="submit" name="simpan_slide" class="btn btn-warning px-4 font-weight-bold">
                  <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
              <?php else: ?>
                <button type="submit" name="simpan_slide" class="btn btn-primary px-4 font-weight-bold">
                  <i class="fas fa-paper-plane mr-1"></i> Simpan Slide
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <div class="card elevation-2">
        <div class="card-header bg-dark">
          <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Daftar Slide Banner</h3>
        </div>
        <div class="card-body p-0 table-responsive">
          <table id="tableSlide" class="table table-hover table-striped align-middle mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 140px;" class="text-center">Gambar Banner</th>
                <th>Judul / Teks Utama Slide</th>
                <th class="text-center" style="width: 130px;">Status</th>
                <th class="text-center" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $adaData = false;
              foreach ($slideList as $doc):
                if ($doc->exists()):
                  $adaData = true;
                  $d = $doc->data();
                  $gambar = !empty($d['gambar']) && file_exists(__DIR__ . '/' . $d['gambar']) ? $d['gambar'] : 'https://via.placeholder.com/200x100?text=No+Image';
                  $isAktif = ($d['status'] ?? 'aktif') === 'aktif';
              ?>
                <tr>
                  <td class="align-middle text-center">
                    <img src="<?= $gambar; ?>" alt="Slide" class="img-thumbnail" style="width: 120px; height: 60px; object-fit: cover;">
                  </td>
                  <td class="align-middle">
                    <strong class="text-primary font-weight-bold"><?= htmlspecialchars($d['teks_slide'] ?? 'Tanpa Judul'); ?></strong>
                  </td>
                  <td class="align-middle text-center">
                    <span class="badge <?= $isAktif ? 'badge-success' : 'badge-secondary'; ?> px-2 py-1">
                      <?= $isAktif ? 'Aktif' : 'Tidak Aktif'; ?>
                    </span>
                  </td>
                  <td class="align-middle text-center">
                    <a href="slide.php?action=edit&id=<?= $doc->id(); ?>" class="btn btn-sm btn-warning font-weight-bold"><i class="fas fa-edit"></i> Edit</a>
                    <a href="slide.php?action=hapus&id=<?= $doc->id(); ?>" class="btn btn-sm btn-danger ml-1" onclick="return confirm('Apakah Anda yakin ingin menghapus slide banner ini?')"><i class="fas fa-trash"></i></a>
                  </td>
                </tr>
              <?php
                endif;
              endforeach;

              if (!$adaData):
              ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">Belum ada slide banner yang diunggah.</td>
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
    $('#tableSlide').DataTable({
      "order": [],
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
      }
    });
  });
</script>