<?php
require 'layout/header.php';
require 'layout/sidebar.php';

// Variabel default untuk Mode Edit
$isEdit = false;
$editId = '';
$editData = [
    'isbn'          => '',
    'judul'         => '',
    'jml_halaman'   => '',
    'penulis'       => '',
    'penerbit'      => '',
    'cover'         => '',
    'deskripsi'     => '',
    'link_download' => ''
];

// 1. PROSES AMBIL DATA UNTUK EDIT
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $editId = $_GET['id'];
    $doc = $db->collection('ebook')->document($editId)->snapshot();
    if ($doc->exists()) {
        $isEdit = true;
        $editData = array_merge($editData, $doc->data());
    } else {
        echo "<script>alert('Data E-Book tidak ditemukan!'); location.href='ebook.php';</script>";
        exit;
    }
}

// 2. PROSES HAPUS E-BOOK
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $doc = $db->collection('ebook')->document($id)->snapshot();
    if ($doc->exists()) {
        $data = $doc->data();
        if (!empty($data['cover']) && file_exists(__DIR__ . '/' . $data['cover'])) {
            @unlink(__DIR__ . '/' . $data['cover']);
        }
        $db->collection('ebook')->document($id)->delete();
    }
    echo "<script>alert('E-Book berhasil dihapus!'); location.href='ebook.php';</script>";
    exit;
}

// 3. PROSES SIMPAN (TAMBAH / UPDATE)
if (isset($_POST['simpan_ebook'])) {
    $isbn         = trim($_POST['isbn']);
    $judul        = trim($_POST['judul']);
    $jmlHalaman   = (int)$_POST['jml_halaman'];
    $penulis      = trim($_POST['penulis']);
    $penerbit     = trim($_POST['penerbit']);
    $deskripsi    = trim($_POST['deskripsi']);
    $linkDownload = trim($_POST['link_download']);
    $coverPath    = $_POST['cover_lama'] ?? '';

    // Upload Cover Gambar
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $folderUpload = __DIR__ . '/uploads/ebook/';
            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0777, true);
            }
            
            $namaCover = 'ebook_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $folderUpload . $namaCover;
            
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $targetPath)) {
                if (!empty($_POST['cover_lama']) && file_exists(__DIR__ . '/' . $_POST['cover_lama'])) {
                    @unlink(__DIR__ . '/' . $_POST['cover_lama']);
                }
                $coverPath = 'uploads/ebook/' . $namaCover;
            }
        }
    }

    $payload = [
        'isbn'          => $isbn,
        'judul'         => $judul,
        'jml_halaman'   => $jmlHalaman,
        'penulis'       => $penulis,
        'penerbit'      => $penerbit,
        'cover'         => $coverPath,
        'deskripsi'     => $deskripsi,
        'link_download' => $linkDownload,
        'updated_at'    => date('Y-m-d H:i:s')
    ];

    if (!empty($_POST['id_ebook'])) {
        $db->collection('ebook')->document($_POST['id_ebook'])->set($payload, ['merge' => true]);
        echo "<script>alert('Data E-Book berhasil diperbarui!'); location.href='ebook.php';</script>";
    } else {
        $payload['created_at'] = time();
        $db->collection('ebook')->add($payload);
        echo "<script>alert('E-Book berhasil ditambahkan!'); location.href='ebook.php';</script>";
    }
    exit;
}

$ebookList = $db->collection('ebook')->orderBy('created_at', 'DESC')->documents();
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-book-reader mr-2 text-info"></i>Kelola E-Book Digital</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">E-Book</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="mb-3">
        <?php if (!$isEdit): ?>
          <button class="btn btn-info font-weight-bold shadow-sm" type="button" data-toggle="collapse" data-target="#formEbookCollapse" aria-expanded="false">
            <i class="fas fa-plus-circle mr-1"></i> + Tambah E-Book Baru
          </button>
        <?php else: ?>
          <a href="ebook.php" class="btn btn-secondary font-weight-bold shadow-sm">
            <i class="fas fa-times mr-1"></i> Batal Edit / Reset Form
          </a>
        <?php endif; ?>
      </div>

      <div class="collapse <?= $isEdit ? 'show' : ''; ?> mb-4" id="formEbookCollapse">
        <div class="card <?= $isEdit ? 'card-warning' : 'card-info'; ?> card-outline elevation-2 mb-0">
          <div class="card-header <?= $isEdit ? 'bg-warning text-dark' : 'bg-info text-white'; ?>">
            <h3 class="card-title font-weight-bold">
              <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?> mr-1"></i>
              <?= $isEdit ? 'Edit Data E-Book' : 'Form Input E-Book Baru'; ?>
            </h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#formEbookCollapse">
                <i class="fas fa-times <?= $isEdit ? 'text-dark' : 'text-white'; ?>"></i>
              </button>
            </div>
          </div>

          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_ebook" value="<?= htmlspecialchars($editId); ?>">
            <input type="hidden" name="cover_lama" value="<?= htmlspecialchars($editData['cover']); ?>">

            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Kode ISBN <span class="text-danger">*</span></label>
                    <input type="text" name="isbn" class="form-control" placeholder="Contoh: 978-602-0000-00-0" value="<?= htmlspecialchars($editData['isbn']); ?>" required>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="form-group">
                    <label>Judul E-Book <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul e-book..." value="<?= htmlspecialchars($editData['judul']); ?>" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Jumlah Halaman <span class="text-danger">*</span></label>
                    <input type="number" name="jml_halaman" class="form-control" placeholder="Contoh: 250" value="<?= htmlspecialchars($editData['jml_halaman']); ?>" required>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Penulis <span class="text-danger">*</span></label>
                    <input type="text" name="penulis" class="form-control" placeholder="Nama penulis e-book..." value="<?= htmlspecialchars($editData['penulis']); ?>" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Penerbit <span class="text-danger">*</span></label>
                    <input type="text" name="penerbit" class="form-control" placeholder="Nama penerbit..." value="<?= htmlspecialchars($editData['penerbit']); ?>" required>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Link Download E-Book (URL Drive/Cloud) <span class="text-danger">*</span></label>
                    <input type="url" name="link_download" class="form-control" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($editData['link_download']); ?>" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Gambar Cover Buku <?= $isEdit ? '(Biarkan kosong jika tidak diubah)' : '(Opsional)'; ?></label>
                    <input type="file" name="cover" class="form-control-file mb-1" accept="image/*">
                    <?php if (!empty($editData['cover']) && file_exists(__DIR__ . '/' . $editData['cover'])): ?>
                      <small class="text-success d-block"><i class="fas fa-image mr-1"></i> Cover saat ini: <b><?= htmlspecialchars(basename($editData['cover'])); ?></b></small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Deskripsi E-Book <span class="text-danger">*</span></label>
                <textarea name="deskripsi" class="form-control" rows="4" placeholder="Tuliskan ringkasan / sinopsis e-book..." required><?= htmlspecialchars($editData['deskripsi']); ?></textarea>
              </div>
            </div>

            <div class="card-footer text-right bg-light">
              <?php if ($isEdit): ?>
                <a href="ebook.php" class="btn btn-secondary mr-2"><i class="fas fa-undo mr-1"></i> Batal</a>
                <button type="submit" name="simpan_ebook" class="btn btn-warning px-4 font-weight-bold">
                  <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
              <?php else: ?>
                <button type="submit" name="simpan_ebook" class="btn btn-info px-4 font-weight-bold">
                  <i class="fas fa-paper-plane mr-1"></i> Simpan E-Book
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <div class="card elevation-2">
        <div class="card-header bg-dark">
          <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Daftar Koleksi E-Book Digital</h3>
        </div>
        <div class="card-body p-0 table-responsive">
        <table id="tableEbook" class="table table-hover table-striped align-middle mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 70px;" class="text-center">Cover</th>
                <th>ISBN & Judul E-Book</th>
                <th>Penulis & Penerbit</th>
                <th class="text-center">Hal.</th>
                <th class="text-center">Akses File</th>
                <th class="text-center" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $adaData = false;
              foreach ($ebookList as $doc):
                if ($doc->exists()):
                  $adaData = true;
                  $d = $doc->data();
                  $cover = !empty($d['cover']) && file_exists(__DIR__ . '/' . $d['cover']) ? $d['cover'] : 'https://via.placeholder.com/100x140?text=No+Cover';
              ?>
                <tr>
                  <td class="align-middle text-center">
                    <img src="<?= $cover; ?>" alt="Cover" class="img-thumbnail" style="width: 50px; height: 70px; object-fit: cover;">
                  </td>
                  <td class="align-middle">
                    <small class="badge badge-secondary mb-1">ISBN: <?= htmlspecialchars($d['isbn'] ?? '-'); ?></small>
                    <strong class="text-info font-weight-bold d-block"><?= htmlspecialchars($d['judul'] ?? 'Tanpa Judul'); ?></strong>
                  </td>
                  <td class="align-middle small">
                    <div><b>Penulis:</b> <?= htmlspecialchars($d['penulis'] ?? '-'); ?></div>
                    <div><b>Penerbit:</b> <?= htmlspecialchars($d['penerbit'] ?? '-'); ?></div>
                  </td>
                  <td class="align-middle text-center font-weight-bold">
                    <?= $d['jml_halaman'] ?? 0; ?>
                  </td>
                  <td class="align-middle text-center">
                    <a href="<?= htmlspecialchars($d['link_download'] ?? '#'); ?>" target="_blank" class="btn btn-sm btn-outline-info font-weight-bold">
                      <i class="fas fa-download mr-1"></i> Download
                    </a>
                  </td>
                  <td class="align-middle text-center">
                    <a href="ebook.php?action=edit&id=<?= $doc->id(); ?>" class="btn btn-sm btn-warning font-weight-bold"><i class="fas fa-edit"></i> Edit</a>
                    <a href="ebook.php?action=hapus&id=<?= $doc->id(); ?>" class="btn btn-sm btn-danger ml-1" onclick="return confirm('Apakah Anda yakin ingin menghapus e-book ini?')"><i class="fas fa-trash"></i></a>
                  </td>
                </tr>
              <?php
                endif;
              endforeach;

              if (!$adaData):
              ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">Belum ada koleksi e-book digital tersimpan.</td>
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
    $('#tableEbook').DataTable({
      "order": [],
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
      }
    });
  });
</script>