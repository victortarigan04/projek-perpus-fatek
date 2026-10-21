<?php
require 'layout/header.php';
require 'layout/sidebar.php';

// Variabel default untuk Mode Edit
$isEdit = false;
$editId = '';
$editData = [
    'nama_mhs'     => '',
    'jurusan'      => '',
    'prodi'        => '',
    'pembimbing_1' => '',
    'pembimbing_2' => '',
    'judul'        => '',
    'abstrak'      => '',
    'file_pdf'     => ''
];

// 1. PROSES AMBIL DATA UNTUK EDIT
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $editId = $_GET['id'];
    $doc = $db->collection('skripsi')->document($editId)->snapshot();
    if ($doc->exists()) {
        $isEdit = true;
        $editData = array_merge($editData, $doc->data());
    } else {
        echo "<script>alert('Data skripsi tidak ditemukan!'); location.href='skripsi.php';</script>";
        exit;
    }
}

// 2. PROSES HAPUS SKRIPSI
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $doc = $db->collection('skripsi')->document($id)->snapshot();
    if ($doc->exists()) {
        $data = $doc->data();
        if (!empty($data['file_pdf']) && file_exists(__DIR__ . '/' . $data['file_pdf'])) {
            @unlink(__DIR__ . '/' . $data['file_pdf']);
        }
        $db->collection('skripsi')->document($id)->delete();
    }
    echo "<script>alert('Data skripsi berhasil dihapus!'); location.href='skripsi.php';</script>";
    exit;
}

// 3. PROSES SIMPAN (TAMBAH / UPDATE)
if (isset($_POST['simpan_skripsi'])) {
    $namaMhs     = trim($_POST['nama_mhs']);
    $jurusan     = trim($_POST['jurusan']);
    $prodi       = trim($_POST['prodi']);
    $pembimbing1 = trim($_POST['pembimbing_1']);
    $pembimbing2 = trim($_POST['pembimbing_2']);
    $judul       = trim($_POST['judul']);
    $abstrak     = trim($_POST['abstrak']);
    $pdfPath     = $_POST['pdf_lama'] ?? '';

    // Upload File PDF Baru
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION));
        
        if ($ext === 'pdf') {
            $folderUpload = __DIR__ . '/uploads/skripsi/';
            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0777, true);
            }
            
            $namaPdf = 'skripsi_' . time() . '_' . rand(100, 999) . '.pdf';
            $targetPath = $folderUpload . $namaPdf;
            
            if (move_uploaded_file($_FILES['file_pdf']['tmp_name'], $targetPath)) {
                if (!empty($_POST['pdf_lama']) && file_exists(__DIR__ . '/' . $_POST['pdf_lama'])) {
                    @unlink(__DIR__ . '/' . $_POST['pdf_lama']);
                }
                $pdfPath = 'uploads/skripsi/' . $namaPdf;
            }
        } else {
            echo "<script>alert('Gagal! File harus berformat PDF.'); window.history.back();</script>";
            exit;
        }
    }

    $payload = [
        'nama_mhs'     => $namaMhs,
        'jurusan'      => $jurusan,
        'prodi'        => $prodi,
        'pembimbing_1' => $pembimbing1,
        'pembimbing_2' => $pembimbing2,
        'judul'        => $judul,
        'abstrak'      => $abstrak,
        'file_pdf'     => $pdfPath,
        'updated_at'   => date('Y-m-d H:i:s')
    ];

    if (!empty($_POST['id_skripsi'])) {
        // Mode Update
        $db->collection('skripsi')->document($_POST['id_skripsi'])->set($payload, ['merge' => true]);
        echo "<script>alert('Data skripsi berhasil diperbarui!'); location.href='skripsi.php';</script>";
    } else {
        // Mode Tambah Baru
        $payload['tanggal']    = date('Y-m-d H:i:s');
        $payload['created_at'] = time();
        $db->collection('skripsi')->add($payload);
        echo "<script>alert('Data skripsi berhasil disimpan!'); location.href='skripsi.php';</script>";
    }
    exit;
}

// 4. AMBIL DAFTAR SKRIPSI DARI FIRESTORE
// Query PHP
$skripsiList = $db->collection('skripsi')->orderBy('created_at', 'DESC')->documents();
?>

<div class="content-wrapper">
  <!-- Content Header -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-graduation-cap mr-2 text-success"></i>Kelola Skripsi Mahasiswa</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Skripsi</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <section class="content">
    <div class="container-fluid">

      <!-- Tombol Pemicu Buka/Tutup Form -->
      <div class="mb-3">
        <?php if (!$isEdit): ?>
          <button class="btn btn-success font-weight-bold shadow-sm" type="button" data-toggle="collapse" data-target="#formSkripsiCollapse" aria-expanded="false" aria-controls="formSkripsiCollapse">
            <i class="fas fa-plus-circle mr-1"></i> + Tambah Skripsi Baru
          </button>
        <?php else: ?>
          <a href="skripsi.php" class="btn btn-secondary font-weight-bold shadow-sm">
            <i class="fas fa-times mr-1"></i> Batal Edit / Reset Form
          </a>
        <?php endif; ?>
      </div>

      <!-- Container Form (Default Hidden, Otomatis Terbuka Jika Mode Edit) -->
      <div class="collapse <?= $isEdit ? 'show' : ''; ?> mb-4" id="formSkripsiCollapse">
        <div class="card <?= $isEdit ? 'card-warning' : 'card-success'; ?> card-outline elevation-2 mb-0">
          <div class="card-header <?= $isEdit ? 'bg-warning text-dark' : 'bg-success text-white'; ?>">
            <h3 class="card-title font-weight-bold">
              <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?> mr-1"></i>
              <?= $isEdit ? 'Edit Data Skripsi' : 'Form Input Skripsi Mahasiswa'; ?>
            </h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#formSkripsiCollapse">
                <i class="fas fa-times <?= $isEdit ? 'text-dark' : 'text-white'; ?>"></i>
              </button>
            </div>
          </div>

          <form method="post" enctype="multipart/form-data">
            <!-- Hidden Inputs -->
            <input type="hidden" name="id_skripsi" value="<?= htmlspecialchars($editId); ?>">
            <input type="hidden" name="pdf_lama" value="<?= htmlspecialchars($editData['file_pdf']); ?>">

            <div class="card-body">
              <div class="row">
                <!-- Nama Mahasiswa -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Nama Mahasiswa <span class="text-danger">*</span></label>
                    <input type="text" name="nama_mhs" class="form-control" placeholder="Nama lengkap mahasiswa..." value="<?= htmlspecialchars($editData['nama_mhs']); ?>" required>
                  </div>
                </div>

                <!-- Combo Box Jurusan -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Jurusan <span class="text-danger">*</span></label>
                    <select name="jurusan" id="jurusan" class="form-control" required>
                      <option value="">-- Pilih Jurusan --</option>
                      <option value="Teknik Mesin" <?= $editData['jurusan'] === 'Teknik Mesin' ? 'selected' : ''; ?>>Teknik Mesin</option>
                      <option value="Teknik Elektro" <?= $editData['jurusan'] === 'Teknik Elektro' ? 'selected' : ''; ?>>Teknik Elektro</option>
                      <option value="Teknik Sipil" <?= $editData['jurusan'] === 'Teknik Sipil' ? 'selected' : ''; ?>>Teknik Sipil</option>
                      <option value="Arsitektur" <?= $editData['jurusan'] === 'Arsitektur' ? 'selected' : ''; ?>>Arsitektur</option>
                    </select>
                  </div>
                </div>

                <!-- Combo Box Program Studi (Dependent Dropdown) -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Program Studi <span class="text-danger">*</span></label>
                    <select name="prodi" id="prodi" class="form-control" required disabled>
                      <option value="">-- Pilih Jurusan Terlebih Dahulu --</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <!-- Judul Skripsi -->
                <div class="col-md-12">
                  <div class="form-group">
                    <label>Judul Skripsi <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Masukkan judul skripsi lengkap..." value="<?= htmlspecialchars($editData['judul']); ?>" required>
                  </div>
                </div>
              </div>

              <div class="row">
                <!-- Dosen Pembimbing 1 -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Dosen Pembimbing 1 <span class="text-danger">*</span></label>
                    <input type="text" name="pembimbing_1" class="form-control" placeholder="Nama Dosen Pembimbing 1 beserta Gelar..." value="<?= htmlspecialchars($editData['pembimbing_1']); ?>" required>
                  </div>
                </div>

                <!-- Dosen Pembimbing 2 -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Dosen Pembimbing 2 <span class="text-danger">*</span></label>
                    <input type="text" name="pembimbing_2" class="form-control" placeholder="Nama Dosen Pembimbing 2 beserta Gelar..." value="<?= htmlspecialchars($editData['pembimbing_2']); ?>" required>
                  </div>
                </div>
              </div>

              <div class="row">
                <!-- Upload File PDF -->
                <div class="col-md-12">
                  <div class="form-group">
                    <label>Upload File Skripsi (Format .PDF) <?= $isEdit ? '(Biarkan kosong jika tidak diubah)' : '<span class="text-danger">*</span>'; ?></label>
                    <input type="file" name="file_pdf" class="form-control-file mb-1" accept="application/pdf" <?= $isEdit ? '' : 'required'; ?>>
                    <?php if (!empty($editData['file_pdf']) && file_exists(__DIR__ . '/' . $editData['file_pdf'])): ?>
                      <small class="text-success d-block"><i class="fas fa-file-pdf mr-1"></i> File PDF saat ini: <b><a href="<?= htmlspecialchars($editData['file_pdf']); ?>" target="_blank" class="text-success text-underline"><?= htmlspecialchars(basename($editData['file_pdf'])); ?></a></b></small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Abstrak -->
              <div class="form-group">
                <label>Abstrak Skripsi <span class="text-danger">*</span></label>
                <textarea name="abstrak" class="form-control" rows="5" placeholder="Tuliskan atau tempelkan ringkasan / abstrak skripsi di sini..." required><?= htmlspecialchars($editData['abstrak']); ?></textarea>
              </div>
            </div>

            <div class="card-footer text-right bg-light">
              <?php if ($isEdit): ?>
                <a href="skripsi.php" class="btn btn-secondary mr-2"><i class="fas fa-undo mr-1"></i> Batal</a>
                <button type="submit" name="simpan_skripsi" class="btn btn-warning px-4 font-weight-bold">
                  <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
              <?php else: ?>
                <button type="submit" name="simpan_skripsi" class="btn btn-success px-4 font-weight-bold">
                  <i class="fas fa-paper-plane mr-1"></i> Simpan Skripsi
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Tabel Daftar Skripsi -->
      <div class="card elevation-2">
        <div class="card-header bg-dark">
          <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Daftar Repositori Skripsi Mahasiswa</h3>
        </div>
        <div class="card-body p-0 table-responsive">
        <table id="tableSkripsi" class="table table-hover table-striped align-middle mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th>Mahasiswa & Jurusan</th>
                <th>Judul Skripsi</th>
                <th>Dosen Pembimbing</th>
                <th>Abstrak (Ringkasan)</th>
                <th class="text-center" style="width: 100px;">File PDF</th>
                <th class="text-center" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $adaData = false;
              $no = 1;
              foreach ($skripsiList as $doc):
                if ($doc->exists()):
                  $adaData = true;
                  $d = $doc->data();
                  $hasPdf = !empty($d['file_pdf']) && file_exists(__DIR__ . '/' . $d['file_pdf']);
              ?>
                <tr>
                  <td class="align-middle text-center font-weight-bold"><?= $no++; ?></td>

                  <!-- Mahasiswa & Jurusan/Prodi -->
                  <td class="align-middle" style="min-width: 200px;">
                    <strong class="text-dark d-block"><i class="fas fa-user-graduate mr-1 text-success"></i> <?= htmlspecialchars($d['nama_mhs'] ?? '-'); ?></strong>
                    <span class="badge badge-info mt-1"><?= htmlspecialchars($d['jurusan'] ?? '-'); ?></span>
                    <small class="text-muted d-block font-weight-bold mt-1"><?= htmlspecialchars($d['prodi'] ?? '-'); ?></small>
                  </td>

                  <!-- Judul -->
                  <td class="align-middle" style="min-width: 230px;">
                    <strong class="text-success font-weight-bold d-block"><?= htmlspecialchars($d['judul'] ?? 'Tanpa Judul'); ?></strong>
                  </td>

                  <!-- Pembimbing 1 & 2 -->
                  <td class="align-middle small" style="min-width: 200px;">
                    <div><b>PBB 1:</b> <?= htmlspecialchars($d['pembimbing_1'] ?? '-'); ?></div>
                    <div><b>PBB 2:</b> <?= htmlspecialchars($d['pembimbing_2'] ?? '-'); ?></div>
                  </td>

                  <!-- Abstrak -->
                  <td class="align-middle text-muted small" style="max-width: 250px;">
                    <?= htmlspecialchars(substr($d['abstrak'] ?? '-', 0, 100)); ?>...
                  </td>

                  <!-- Download / View PDF -->
                  <td class="align-middle text-center">
                    <?php if ($hasPdf): ?>
                      <a href="<?= htmlspecialchars($d['file_pdf']); ?>" target="_blank" class="btn btn-sm btn-outline-danger font-weight-bold" title="Buka File PDF">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                      </a>
                    <?php else: ?>
                      <span class="badge badge-secondary p-1">Tidak Ada</span>
                    <?php endif; ?>
                  </td>

                  <!-- Aksi Edit & Hapus -->
                  <td class="align-middle text-center">
                    <a href="skripsi.php?action=edit&id=<?= $doc->id(); ?>" 
                       class="btn btn-sm btn-warning font-weight-bold" 
                       title="Edit Skripsi">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="skripsi.php?action=hapus&id=<?= $doc->id(); ?>" 
                       class="btn btn-sm btn-danger ml-1" 
                       onclick="return confirm('Apakah Anda yakin ingin menghapus data skripsi ini?')" 
                       title="Hapus Skripsi">
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
                  <td colspan="7" class="text-center py-4 text-muted">Belum ada data skripsi mahasiswa yang diunggah.</td>
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

<!-- JavaScript Dependent Dropdown Jurusan -> Program Studi -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const prodiMap = {
      'Teknik Mesin': ['S1 Teknik Mesin', 'S1 Teknik Industri'],
      'Teknik Elektro': ['S1 Teknik Informatika', 'S1 Teknik Elektro'],
      'Teknik Sipil': ['S1 Teknik Sipil', 'S1 Teknik Lingkungan'],
      'Arsitektur': ['S1 Arsitektur', 'S1 Perencanaan Wilayah dan Kota']
    };

    const jurusanSelect = document.getElementById('jurusan');
    const prodiSelect = document.getElementById('prodi');
    const editProdiVal = "<?= htmlspecialchars($editData['prodi']); ?>";

    function updateProdiOptions(jurusanValue, selectedProdi = '') {
      prodiSelect.innerHTML = '';

      if (jurusanValue && prodiMap[jurusanValue]) {
        prodiSelect.disabled = false;
        
        let defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '-- Pilih Program Studi --';
        prodiSelect.appendChild(defaultOpt);

        prodiMap[jurusanValue].forEach(function (prodi) {
          let opt = document.createElement('option');
          opt.value = prodi;
          opt.textContent = prodi;
          if (prodi === selectedProdi) {
            opt.selected = true;
          }
          prodiSelect.appendChild(opt);
        });
      } else {
        prodiSelect.disabled = true;
        let defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '-- Pilih Jurusan Terlebih Dahulu --';
        prodiSelect.appendChild(defaultOpt);
      }
    }

    // Event listener saat Jurusan diubah
    jurusanSelect.addEventListener('change', function () {
      updateProdiOptions(this.value);
    });

    // Inisialisasi awal (terutama untuk mode edit)
    if (jurusanSelect.value) {
      updateProdiOptions(jurusanSelect.value, editProdiVal);
    }
  });
</script>

<?php require 'layout/footer.php'; ?>

<script>
  $(document).ready(function() {
    $('#tableSkripsi').DataTable({
      "order": [],
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
      }
    });
  });
</script>