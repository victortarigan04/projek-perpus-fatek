<?php
require 'koneksi.php';

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $users = $db->collection('admins')->where('email', '=', $email)->documents();
    $found = false;

    foreach ($users as $user) {
        if ($user->exists()) {
            $data = $user->data();
            if (password_verify($password, $data['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $user->id();
                $_SESSION['admin_nama']      = $data['nama'];
                $_SESSION['admin_level']     = $data['level']; // superadmin / admin
                
                header("Location: index.php");
                exit;
            }
        }
    }
    $error = "Email atau password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login | Admin Perpustakaan</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <h3><b>Admin</b>Perpustakaan</h3>
    </div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger p-2"><?= $error; ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
        </div>
        <button type="submit" name="login" class="btn btn-primary btn-block">Masuk</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>