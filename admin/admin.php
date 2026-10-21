<?php
require 'layout/header.php';
require 'layout/sidebar.php';
check_superadmin();

if (isset($_POST['tambah_admin'])) {
    $db->collection('admins')->add([
        'nama'     => $_POST['nama'],
        'email'    => $_POST['email'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'level'    => $_POST['level'] // superadmin / admin
    ]);
    echo "<script>location.href='admin.php';</script>";
}

$admins = $db->collection('admins')->documents();
?>

<div class="content-wrapper p-3">
  <div class="content-header"><h1>Kelola Admin</h1></div>
  <div class="content">
    <div class="row">
      <div class="col-md-4">
        <div class="card card-primary">
          <div class="card-header"><h3 class="card-title">Tambah Admin</h3></div>
          <form method="post">
            <div class="card-body">
              <div class="form-group"><label>Nama</label><input type="text" name="nama" class="form-control" required></div>
              <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
              <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
              <div class="form-group">
                <label>Level Admin</label>
                <select name="level" class="form-control" required>
                  <option value="admin">Admin Regular</option>
                  <option value="superadmin">Superadmin</option>
                </select>
              </div>
            </div>
            <div class="card-footer"><button type="submit" name="tambah_admin" class="btn btn-primary">Simpan</button></div>
          </form>
        </div>
      </div>
      
      <div class="col-md-8">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Daftar Admin</h3></div>
          <div class="card-body p-0">
            <table class="table table-striped">
              <thead><tr><th>Nama</th><th>Email</th><th>Level</th></tr></thead>
              <tbody>
                <?php foreach ($admins as $a): if($a->exists()): $d = $a->data(); ?>
                  <tr>
                    <td><?= htmlspecialchars($d['nama']); ?></td>
                    <td><?= htmlspecialchars($d['email']); ?></td>
                    <td><span class="badge badge-<?= $d['level'] == 'superadmin' ? 'danger' : 'info'; ?>"><?= $d['level']; ?></span></td>
                  </tr>
                <?php endif; endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require 'layout/footer.php'; ?>