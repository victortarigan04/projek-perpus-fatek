# Elibrary Fatek UNSRAT

Portal perpustakaan digital Fakultas Teknik UNSRAT (PHP 8.2 + Apache + Firebase Firestore).
Dibungkus dengan Docker agar mudah dijalankan di server mana pun.

## Teknologi
- PHP 8.2 (Apache) dengan ekstensi **gRPC** (wajib untuk Firestore)
- Library `google/cloud-firestore` (sudah ada di `admin/vendor`; jika tidak ada, Composer
  menginstalnya otomatis saat build)
- Database: Google Cloud Firestore (di luar container)

