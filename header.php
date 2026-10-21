<!-- Top Info Bar -->
<div class="top-bar py-2 d-none d-md-block">
  <div class="container d-flex justify-content-between align-items-center">
    <div>
      <span class="mr-3"><i class="mdi mdi-clock-outline mr-1"></i>Senin - Kamis: 08:00 - 13:00 WITA</span>
      <span><i class="mdi mdi-map-marker-outline mr-1"></i>Gedung Perpustakaan Fakultas Teknik UNSRAT</span>
    </div>
    <div>
      <a href="mailto:perpustakaan.fatek@unsrat.ac.id" class="text-light mr-3"><i class="mdi mdi-email-outline mr-1"></i>perpustakaan.fatek@unsrat.ac.id</a>
      <span><i class="mdi mdi-phone-outline mr-1"></i>(0431) 863-886</span>
    </div>
  </div>
</div>

<!-- Navigation Bar Utama -->
<nav class="navbar navbar-expand-lg navbar-light navbar-light-custom sticky-top py-2">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="Logo_unsrat.png" alt="Logo UNSRAT" class="rounded mr-2" style="height:48px;">
      <div>
        <span class="font-weight-bold d-block text-dark" style="font-size: 1.15rem; line-height: 1.2;">E-LIBRARY FATEK</span>
        <small class="text-muted font-weight-bold" style="font-size: 0.72rem;">FAKULTAS TEKNIK UNSRAT</small>
      </div>
    </a>

    <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ml-auto align-items-lg-center">
        <li class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
          <a class="nav-link" href="index.php"><i class="mdi mdi-home-outline mr-1"></i>Beranda</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="katalogDropdown" role="button" data-toggle="dropdown">
            <i class="mdi mdi-book-open-page-variant-outline mr-1"></i>Katalog
          </a>
          <div class="dropdown-menu">
            <a class="dropdown-item" href="buku_fisik.php"><i class="mdi mdi-book-outline mr-2 text-primary"></i>Buku Fisik</a>
            <a class="dropdown-item" href="ebook.php"><i class="mdi mdi-book-open-variant mr-2 text-info"></i>E-Book Digital</a>
            <a class="dropdown-item" href="index.php#skripsi"><i class="mdi mdi-school-outline mr-2 text-success"></i>Skripsi Mahasiswa</a>
          </div>
        </li>

        <li class="nav-item dropdown <?= (basename($_SERVER['PHP_SELF']) == 'request_jurnal.php') ? 'active' : ''; ?>">
          <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-toggle="dropdown">
            <i class="mdi mdi-information-outline mr-1"></i>Tentang Kami
          </a>
          <div class="dropdown-menu">
            <a class="dropdown-item" href="index.php#fasilitas"><i class="mdi mdi-domain mr-2 text-primary"></i>Fasilitas Perpustakaan</a>
            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'request_jurnal.php') ? 'active' : ''; ?>" href="staff.php"><i class="mdi mdi-file-document-edit-outline mr-2 text-info"></i>Staff Dosen</a>
          </div>
        </li>

        <li class="nav-item"><a class="nav-link" href="index.php#berita"><i class="mdi mdi-newspaper-variant-outline mr-1"></i>Berita</a></li>

        <li class="nav-item ml-lg-2 mt-2 mt-lg-0">
          <a class="btn btn-primary-custom px-3 py-2 rounded-pill font-weight-bold" href="admin/login.php">
            <i class="mdi mdi-lock-outline mr-1"></i> Area Admin
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>