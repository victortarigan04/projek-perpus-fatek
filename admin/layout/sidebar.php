<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="dashboard.php" class="brand-link">
    <span class="brand-text font-weight-light"><b>Perpustakaan</b> Admin</span>
  </a>
  <div class="sidebar">
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="info">
        <a href="#" class="d-block"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin'); ?> (<b><?= ucfirst($_SESSION['admin_level'] ?? 'admin'); ?></b>)</a>
      </div>
    </div>
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a>
        </li>
        <li class="nav-item">
          <a href="slide.php" class="nav-link"><i class="nav-icon fas fa-images"></i><p>Slide Banner</p></a>
        </li>
        <li class="nav-item">
          <a href="buku_fisik.php" class="nav-link"><i class="nav-icon fas fa-book"></i><p>Buku Fisik</p></a>
        </li>
        <li class="nav-item">
          <a href="ebook.php" class="nav-link"><i class="nav-icon fas fa-book-reader"></i><p>E-Book Digital</p></a>
        </li>
        <li class="nav-item">
          <a href="skripsi.php" class="nav-link"><i class="nav-icon fas fa-graduation-cap"></i><p>Skripsi Mahasiswa</p></a>
        </li>
        <li class="nav-item">
          <a href="berita.php" class="nav-link"><i class="nav-icon fas fa-newspaper"></i><p>Berita & Pengumuman</p></a>
        </li>
		 <li class="nav-item">
          <a href="staff.php" class="nav-link"><i class="nav-icon fas fa-user"></i><p>Staff Perpustakaan</p></a>
        </li>

<!-- Menu Sidebar: Request Jurnal -->
<li class="nav-item <?= (in_array(basename($_SERVER['PHP_SELF']), ['request_jurnal_pending.php', 'request_jurnal_selesai.php'])) ? 'active' : ''; ?>">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseJurnal" aria-expanded="true">
  <i class="nav-icon fas fa-file"></i>
    <span class="menu-title">Request Jurnal</span>
    <i class="mdi mdi-chevron-down menu-arrow"></i>
  </a>
  <div class="collapse <?= (in_array(basename($_SERVER['PHP_SELF']), ['request_jurnal_pending.php', 'request_jurnal_selesai.php'])) ? 'show' : ''; ?>" id="collapseJurnal">
    <ul class="nav flex-column sub-menu pl-4">
      <li class="nav-item">
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'request_jurnal_pending.php') ? 'active font-weight-bold text-primary' : ''; ?>" href="request_jurnal_pending.php">
          <i class="mdi mdi-clock-outline mr-2"></i>Belum Direspon
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'request_jurnal_selesai.php') ? 'active font-weight-bold text-success' : ''; ?>" href="request_jurnal_selesai.php">
          <i class="mdi mdi-check-circle-outline mr-2"></i>Sudah Dikirim
        </a>
      </li>
    </ul>
  </div>
</li>

        <?php if (($_SESSION['admin_level'] ?? '') === 'superadmin'): ?>
        <li class="nav-header">PENGATURAN HAK AKSES</li>
        <li class="nav-item">
          <a href="admin.php" class="nav-link"><i class="nav-icon fas fa-user-shield"></i><p>Kelola Data Admin</p></a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</aside>