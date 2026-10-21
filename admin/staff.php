<?php
require 'layout/header.php';
require 'layout/sidebar.php';

// Variabel default untuk Mode Edit
$isEdit = false;
$editId = '';
$editData = [
    'nama'       => '',
    'jabatan'    => '',
    'nip'        => '',
    'pendidikan' => '',
    'email'      => '',
    'telepon'    => '',
    'bidang'     => '',
    'urutan'     => 10,
    'foto'       => ''
];

// 1. PROSES AMBIL DATA UNTUK EDIT
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $editId = $_GET['id'];
    $doc = $db->collection('staff')->document($editId)->snapshot();
    if ($doc->exists()) {
        $isEdit = true;
        $editData = array_merge($editData, $doc->data());
    } else {
        echo "<script>alert('Data staf tidak ditemukan!'); location.href='staff.php';</script>";
        exit;
    }
}

// 2. PROSES HAPUS STAF
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $doc = $db->collection('staff')->document($id)->snapshot();
    if ($doc->exists()) {
        $data = $doc->data();
        if (!empty($data['foto']) && file_exists(__DIR__ . '/' . $data['foto'])) {
            @unlink(__DIR__ . '/' . $data['foto']);
        }
        $db->collection('staff')->document($id)->delete();
    }
    echo "<script>alert('Data staf berhasil dihapus!'); location.href='staff.php';</script>";
    exit;
}

// 3. PROSES SIMPAN (TAMBAH / UPDATE)
if (isset($_POST['simpan_staff'])) {
    $nama       = trim($_POST['nama']);
    $jabatan    = trim($_POST['jabatan']);
    $nip        = trim($_POST['nip']);
    $pendidikan = trim($_POST['pendidikan']);
    $email      = trim($_POST['email']);
    $telepon    = trim($_POST['telepon']);
    $bidang     = trim($_POST['bidang']);
    $urutan     = (int)$_POST['urutan'];
    $fotoPath   = $_POST['foto_lama'] ?? '';
    $pesanFoto  = '';

    // Upload Foto Staf
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext      = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        $maksUkuran = 2 * 1024 * 1024; // 2 MB

        if (!in_array($ext, $allowed)) {
            $pesanFoto = ' (Foto tidak diunggah: format harus JPG, PNG, atau WEBP)';
        } elseif ($_FILES['foto']['size'] > $maksUkuran) {
            $pesanFoto = ' (Foto tidak diunggah: ukuran maksimal 2 MB)';
        } elseif (@getimagesize($_FILES['foto']['tmp_name']) === false) {
            $pesanFoto = ' (Foto tidak diunggah: file bukan gambar yang valid)';
        } else {
            $folderUpload = __DIR__ . '/uploads/staff/';
            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0777, true);
            }

            $namaFoto   = 'staff_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $folderUpload . $namaFoto;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
                if (!empty($_POST['foto_lama']) && file_exists(__DIR__ . '/' . $_POST['foto_lama'])) {
                    @unlink(__DIR__ . '/' . $_POST['foto_lama']);
                }
                $fotoPath = 'uploads/staff/' . $namaFoto;
            }
        }
    }

    $payload = [
        'nama'       => $nama,
        'jabatan'    => $jabatan,
        'nip'        => $nip,
        'pendidikan' => $pendidikan,
        'email'      => $email,
        'telepon'    => $telepon,
        'bidang'     => $bidang,
        'urutan'     => $urutan,
        'foto'       => $fotoPath,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($_POST['id_staff'])) {
        $db->collection('staff')->document($_POST['id_staff'])->set($payload, ['merge' => true]);
        $pesan = 'Data staf berhasil diperbarui!';
    } else {
        $payload['created_at'] = time();
        $db->collection('staff')->add($payload);
        $pesan = 'Staf berhasil ditambahkan!';
    }
    echo "<script>alert(" . json_encode($pesan . $pesanFoto) . "); location.href='staff.php';</script>";
    exit;
}

// Ambil seluruh staf, urutkan berdasarkan nomor urut
$staffList = [];
foreach ($db->collection('staff')->documents() as $doc) {
    if ($doc->exists()) {
        $d = $doc->data();
        $d['id'] = $doc->id();
        $staffList[] = $d;
    }
}
usort($staffList, function ($a, $b) {
    $u = (int)($a['urutan'] ?? 999) - (int)($b['urutan'] ?? 999);
    return $u !== 0 ? $u : strcasecmp($a['nama'] ?? '', $b['nama'] ?? '');
});
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-users mr-2 text-primary"></i>Kelola Staf Perpustakaan</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Staf Perpustakaan</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="mb-3">
        <?php if (!$isEdit): ?>
          <button class="btn btn-primary font-weight-bold shadow-sm" type="button" data-toggle="collapse" data-target="#formStaffCollapse" aria-expanded="false">
            <i class="fas fa-plus-circle mr-1"></i> + Tambah Staf Baru
          </button>
        <?php else: ?>
          <a href="staff.php" class="btn btn-secondary font-weight-bold shadow-sm">
            <i class="fas fa-times mr-1"></i> Batal Edit / Reset Form
          </a>
        <?php endif; ?>
      </div>

      <div class="collapse <?= $isEdit ? 'show' : ''; ?> mb-4" id="formStaffCollapse">
        <div class="card <?= $isEdit ? 'card-warning' : 'card-primary'; ?> card-outline elevation-2 mb-0">
          <div class="card-header <?= $isEdit ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
            <h3 class="card-title font-weight-bold">
              <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?> mr-1"></i>
              <?= $isEdit ? 'Edit Data Staf' : 'Form Input Staf Baru'; ?>
            </h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#formStaffCollapse">
                <i class="fas fa-times <?= $isEdit ? 'text-dark' : 'text-white'; ?>"></i>
              </button>
            </div>
          </div>

          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_staff" value="<?= htmlspecialchars($editId); ?>">
            <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($editData['foto']); ?>">

            <div class="card-body">
              <div class="row">
                <div class="col-md-5">
                  <div class="form-group">
                    <label>Nama Lengkap (beserta gelar) <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Dr. Maria Kalesaran, S.Kom., M.Si." value="<?= htmlspecialchars($editData['nama']); ?>" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Jabatan <span class="text-danger">*</span></label>
                    <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Kepala Perpustakaan / Pustakawan" value="<?= htmlspecialchars($editData['jabatan']); ?>" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Nomor Urut Tampil <span class="text-danger">*</span></label>
                    <input type="number" name="urutan" class="form-control" min="1" value="<?= htmlspecialchars($editData['urutan']); ?>" required>
                    <small class="text-muted">Angka kecil tampil lebih dulu. Staf urut 1 berjabatan &ldquo;Kepala...&rdquo; menjadi kartu utama.</small>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>NIP / NIK <small class="text-muted">(opsional)</small></label>
                    <input type="text" name="nip" class="form-control" placeholder="Kosongkan jika tidak ingin ditampilkan" value="<?= htmlspecialchars($editData['nip']); ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Pendidikan Terakhir <small class="text-muted">(opsional)</small></label>
                    <input type="text" name="pendidikan" class="form-control" placeholder="Contoh: S2 Ilmu Perpustakaan" value="<?= htmlspecialchars($editData['pendidikan']); ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Foto Staf <?= $isEdit ? '(Biarkan kosong jika tidak diubah)' : '(Opsional)'; ?></label>
                    <input type="file" name="foto" class="form-control-file mb-1" accept="image/*">
                    <small class="text-muted d-block">JPG/PNG/WEBP, maks. 2 MB. Disarankan foto close-up rasio 1:1.</small>
                    <?php if (!empty($editData['foto']) && file_exists(__DIR__ . '/' . $editData['foto'])): ?>
                      <small class="text-success d-block"><i class="fas fa-image mr-1"></i> Foto saat ini: <b><?= htmlspecialchars(basename($editData['foto'])); ?></b></small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Email <small class="text-muted">(opsional)</small></label>
                    <input type="email" name="email" class="form-control" placeholder="nama@unsrat.ac.id" value="<?= htmlspecialchars($editData['email']); ?>">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Telepon / WhatsApp <small class="text-muted">(opsional)</small></label>
                    <input type="text" name="telepon" class="form-control" placeholder="Contoh: 0812 3456 7890" value="<?= htmlspecialchars($editData['telepon']); ?>">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Bidang Tugas / Layanan <small class="text-muted">(opsional)</small></label>
                <textarea name="bidang" class="form-control" rows="3" placeholder="Contoh: Layanan sirkulasi, pengolahan bahan pustaka, dan repositori skripsi."><?= htmlspecialchars($editData['bidang']); ?></textarea>
              </div>
            </div>

            <div class="card-footer text-right bg-light">
              <?php if ($isEdit): ?>
                <a href="staff.php" class="btn btn-secondary mr-2"><i class="fas fa-undo mr-1"></i> Batal</a>
                <button type="submit" name="simpan_staff" class="btn btn-warning px-4 font-weight-bold">
                  <i class="fas fa-save mr-1"></i> Simpan Perubahan
                </button>
              <?php else: ?>
                <button type="submit" name="simpan_staff" class="btn btn-primary px-4 font-weight-bold">
                  <i class="fas fa-paper-plane mr-1"></i> Simpan Staf
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <div class="card elevation-2">
        <div class="card-header bg-dark">
          <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Daftar Staf Perpustakaan</h3>
        </div>
        <div class="card-body p-0 table-responsive">
          <table id="tableStaff" class="table table-hover table-striped align-middle mb-0">
            <thead class="thead-light">
              <tr>
                <th class="text-center" style="width: 70px;">Urut</th>
                <th class="text-center" style="width: 80px;">Foto</th>
                <th>Nama & Jabatan</th>
                <th>Kontak</th>
                <th class="text-center" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($staffList)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">Belum ada data staf tersimpan.</td>
                </tr>
              <?php else: foreach ($staffList as $d):
                $foto = !empty($d['foto']) && file_exists(__DIR__ . '/' . $d['foto']) ? $d['foto'] : 'https://via.placeholder.com/100x100?text=Foto';
              ?>
                <tr>
                  <td class="align-middle text-center font-weight-bold"><?= (int)($d['urutan'] ?? 0); ?></td>
                  <td class="align-middle text-center">
                    <img src="<?= htmlspecialchars($foto); ?>" alt="Foto" class="img-circle elevation-1" style="width: 52px; height: 52px; object-fit: cover; object-position: center top;">
                  </td>
                  <td class="align-middle">
                    <strong class="text-primary d-block"><?= htmlspecialchars($d['nama'] ?? '-'); ?></strong>
                    <span class="badge badge-info"><?= htmlspecialchars($d['jabatan'] ?? '-'); ?></span>
                    <?php if (!empty($d['nip'])): ?><small class="text-muted d-block mt-1">NIP. <?= htmlspecialchars($d['nip']); ?></small><?php endif; ?>
                  </td>
                  <td class="align-middle small">
                    <div><i class="fas fa-envelope text-muted mr-1"></i><?= htmlspecialchars($d['email'] ?? '-'); ?></div>
                    <div><i class="fas fa-phone text-muted mr-1"></i><?= htmlspecialchars($d['telepon'] ?? '-'); ?></div>
                  </td>
                  <td class="align-middle text-center">
                    <a href="staff.php?action=edit&id=<?= $d['id']; ?>" class="btn btn-sm btn-warning font-weight-bold"><i class="fas fa-edit"></i> Edit</a>
                    <a href="staff.php?action=hapus&id=<?= $d['id']; ?>" class="btn btn-sm btn-danger ml-1" onclick="return confirm('Apakah Anda yakin ingin menghapus data staf ini?')"><i class="fas fa-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
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
    // Tabel sudah diurutkan berdasarkan nomor urut dari server
    if ($('#tableStaff tbody td[colspan]').length === 0) {
      $('#tableStaff').DataTable({
        "order": [],
        "language": {
          "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        }
      });
    }
  });
</script>
