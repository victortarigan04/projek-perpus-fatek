<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'admin/koneksi.php';

$message = '';
$error = '';
$countInserted = 0;

// Data Dummy Berita (15 Data)
$berita_data = [
    // --- BERITA UTAMA (5 Data) ---
    [
        'judul'    => 'Fakultas Teknik UNSRAT Resmi Luncurkan Sistem Digital E-Library Terpadu',
        'kategori' => 'Berita Utama',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Humas Fatek UNSRAT',
        'gambar'   => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Dalam rangka meningkatkan kualitas pelayanan akademik dan aksesibilitas sumber daya pustaka digital, Fakultas Teknik Universitas Sam Ratulangi resmi meluncurkan platform E-Library terpadu. Platform ini memudahkan dosen dan mahasiswa mengakses ribuan e-book, katalog buku fisik, skripsi, serta pengajuan request jurnal internasional.'
    ],
    [
        'judul'    => 'Mahasiswa Teknik Informatika Raih Juara 1 Kompetisi Inovasi Teknologi Nasional',
        'kategori' => 'Berita Utama',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Tim Redaksi Akademik',
        'gambar'   => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Tim mahasiswa Jurusan Teknik Informatika Fakultas Teknik UNSRAT berhasil meraih peringkat pertama dalam ajang Kompetisi Teknologi Nasional berkat karya sistem pemantauan kualitas air tambak berbasis Internet of Things (IoT) dan Kecerdasan Buatan (AI).'
    ],
    [
        'judul'    => 'Kerja Sama Internasional Fatek UNSRAT dengan Universiti Teknologi Malaysia (UTM)',
        'kategori' => 'Berita Utama',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Kerja Sama & Alumni',
        'gambar'   => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Fakultas Teknik UNSRAT menandatangani Memorandum of Understanding (MoU) dengan Universiti Teknologi Malaysia terkait program pertukaran mahasiswa, riset bersama bidang rekayasa pesisir, dan joint publication jurnal ilmiah internasional.'
    ],
    [
        'judul'    => 'Penguatan Akreditasi Unggul Jurusan Teknik Sipil dan Arsitektur Fatek UNSRAT',
        'kategori' => 'Berita Utama',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Humas Fatek UNSRAT',
        'gambar'   => 'https://images.unsplash.com/photo-1541888946425-d0fbb186a5b7?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Lembaga Akreditasi Mandiri Teknik (LAM Teknik) menetapkan akreditasi Unggul untuk Jurusan Teknik Sipil dan Arsitektur Fatek UNSRAT sebagai pilar utama pencapaian standar pendidikan rekayasa bertaraf internasional.'
    ],
    [
        'judul'    => 'Peresmian Laboratorium Energi Terbarukan & Smart Grid Fakultas Teknik',
        'kategori' => 'Berita Utama',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Laboratorium Elektro',
        'gambar'   => 'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Fakultas Teknik UNSRAT meresmikan fasilitas Laboratorium Energi Terbarukan yang dilengkapi dengan perangkat pemantau panel surya cerdas dan sistem BESS (Battery Energy Storage System) untuk riset transisi energi hijau.'
    ],

    // --- AGENDA KEGIATAN (5 Data) ---
    [
        'judul'    => 'Seminar Nasional Rekayasa Teknologi & Infrastruktur Pesisir (SNRTIP) 2026',
        'kategori' => 'Agenda Kegiatan',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Panitia SNRTIP',
        'gambar'   => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Fakultas Teknik UNSRAT mengundang para peneliti, akademisi, dan praktisi untuk menghadiri Seminar Nasional SNRTIP 2026 dengan fokus topik mitigasi bencana pantai dan infrastruktur ramah lingkungan.'
    ],
    [
        'judul'    => 'Workshop Penulisan Jurnal Ilmiah Terindeks Scopus untuk Dosen & Mahasiswa',
        'kategori' => 'Agenda Kegiatan',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Pusat Riset & Pengabdian',
        'gambar'   => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Kegiatan pelatihan metodologi penelitian, teknik penyusunan manuskrip, dan strategi penembusan jurnal internasional bereputasi bagi dosen muda dan mahasiswa tingkat akhir.'
    ],
    [
        'judul'    => 'Pameran Karya Akhir Mahasiswa Arsitektur & Perencanaan Wilayah Kota',
        'kategori' => 'Agenda Kegiatan',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Himpunan Mahasiswa Arsitektur',
        'gambar'   => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Pameran terbuka menampilkan maket, konsep desain kawasan hijau, dan rancangan peremajaan kota hasil karya tugas akhir mahasiswa Arsitektur dan PWK Fatek UNSRAT.'
    ],
    [
        'judul'    => 'Kuliah Umum Guest Lecturer: Perkembangan AI & Cloud Computing di Industri',
        'kategori' => 'Agenda Kegiatan',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Jurusan Teknik Informatika',
        'gambar'   => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Kuliah umum bersama pakar industri dari perusahaan teknologi terkemuka yang membahas tren adopsi arsitektur microservices, DevOps, dan penerapan Machine Learning skala besar.'
    ],
    [
        'judul'    => 'Pelatihan K3 Konstruksi & Sertifikasi Keahlian Mahasiswa Teknik Mesin & Sipil',
        'kategori' => 'Agenda Kegiatan',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Pusat Karir Fatek',
        'gambar'   => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Program pembekalan Keselamatan dan Kesehatan Kerja (K3) industri serta ujian sertifikasi BNSP bagi calon lulusan sarjana teknik agar siap bersaing di dunia kerja.'
    ],

    // --- PENGUMUMAN RESMI (5 Data) ---
    [
        'judul'    => 'Pengumuman Jadwal Ujian Akhir Semester (UAS) Semester Genap TA 2025/2026',
        'kategori' => 'Pengumuman Resmi',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Subbag Akademik',
        'gambar'   => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Diberitahukan kepada seluruh mahasiswa Fakultas Teknik UNSRAT bahwa Ujian Akhir Semester (UAS) Semester Genap akan dilaksanakan secara luring sesuai dengan jadwal mata kuliah masing-masing.'
    ],
    [
        'judul'    => 'Tata Cara Pengajuan Permohonan Jurnal Internasional Melalui E-Library',
        'kategori' => 'Pengumuman Resmi',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Pengelola Perpustakaan',
        'gambar'   => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Informasi alur panduan bagi dosen dan peneliti yang membutuhkan akses dokumen full-text artikel jurnal internasional terindeks melalui fitur Request Jurnal di portal E-Library.'
    ],
    [
        'judul'    => 'Pendaftaran Program Beasiswa Riset Tugas Akhir Mahasiswa Fatek UNSRAT',
        'kategori' => 'Pengumuman Resmi',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Wakil Dekan III',
        'gambar'   => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Pembukaan bantuan dana riset bagi mahasiswa yang sedang menyusun Tugas Akhir / Skripsi dengan topik rekayasa inovatif, teknologi tepat guna, dan keberlanjutan lingkungan.'
    ],
    [
        'judul'    => 'Pemberitahuan Jam Operasional Perpustakaan Fakultas Teknik Selama Bulan Ramadan',
        'kategori' => 'Pengumuman Resmi',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Pengelola Perpustakaan',
        'gambar'   => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Perubahan jam layanan peminjaman buku fisik, ruang diskusi, dan fasilitas komputer E-Library Fakultas Teknik UNSRAT selama bulan Ramadan dibuka pukul 08.00 - 15.30 WITA.'
    ],
    [
        'judul'    => 'Prosedur Bebas Pustaka & Penyerahan Draf Skripsi Digital Bagi Calon Wisudawan',
        'kategori' => 'Pengumuman Resmi',
        'status'   => 'Dipublikasikan',
        'penulis'  => 'Subbag Kemahasiswaan',
        'gambar'   => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=800&auto=format&fit=crop&q=80',
        'isi'      => 'Persyaratan administrasi kelulusan meliputi pengunggahan softcopy dokumen skripsi lengkap ke repository E-Library dan validasi surat bebas pinjam perpustakaan.'
    ]
];

// Proses Eksekusi Penambahan Data ke Firestore (Koleksi: berita)
if (isset($_POST['execute_seed_berita'])) {
    try {
        $now = date('Y-m-d H:i:s');

        foreach ($berita_data as $item) {
             $item['created_at'] = $now;
             $db->collection('berita')->add($item);
             $countInserted++;
        }

        $message = "SUKSES! Berhasil menambahkan $countInserted data berita dummy tanpa field updated_at!";
    } catch (Exception $e) {
        $error = "Gagal memasukkan data berita: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seeder Berita - Fatek UNSRAT</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style> body { font-family: 'Poppins', sans-serif; background-color: #F4F6F9; } </style>
</head>
<body class="p-4">

<div class="container my-5">
  <div class="card border-0 shadow-sm rounded-lg p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="font-weight-bold text-dark mb-0">
        <i class="mdi mdi-newspaper-variant-multiple text-primary mr-2"></i>Eksekusi Data Dummy 15 Berita (Koleksi: berita)
      </h3>
      <a href="index.php" class="btn btn-outline-secondary font-weight-bold">&larr; Kembali ke Dashboard</a>
    </div>
    <p class="text-muted">
      Script ini akan memasukkan <strong>15 data dummy berita</strong> ke koleksi Firestore <span class="badge badge-primary px-3 py-2">berita</span> tanpa field <code>updated_at</code>.
    </p>

    <?php if ($message): ?>
      <div class="alert alert-success alert-dismissible fade show my-3" role="alert">
        <i class="mdi mdi-check-circle mr-2"></i> <?= htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show my-3" role="alert">
        <i class="mdi mdi-alert-circle mr-2"></i> <?= htmlspecialchars($error); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
    <?php endif; ?>

    <form action="" method="post" class="mt-3">
      <button type="submit" name="execute_seed_berita" class="btn btn-primary btn-lg font-weight-bold shadow-sm">
        <i class="mdi mdi-upload mr-2"></i> Jalankan Eksekusi 15 Berita Sekarang
      </button>
    </form>
  </div>
</div>

</body>
</html>