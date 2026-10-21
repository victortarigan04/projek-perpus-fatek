# Elibrary Fatek UNSRAT

Portal perpustakaan digital Fakultas Teknik UNSRAT (PHP 8.2 + Apache + Firebase Firestore).
Dibungkus dengan Docker agar mudah dijalankan di server mana pun.

## Teknologi
- PHP 8.2 (Apache) dengan ekstensi **gRPC** (wajib untuk Firestore)
- Library `google/cloud-firestore` (sudah ada di `admin/vendor`; jika tidak ada, Composer
  menginstalnya otomatis saat build)
- Database: Google Cloud Firestore (di luar container)

## Menjalankan
```bash
docker compose up -d --build
```
Situs dapat dibuka di `http://localhost:8080` (port dapat diubah di `docker-compose.yml`).

## PENTING: repository ini berisi kunci rahasia
File `admin/credentials.json` adalah kunci service account Firebase dan **ikut berada di repository**.
- Repository ini harus tetap **Private** dan aksesnya dibatasi.
- Jangan menjadikannya Public atau menyalinnya ke tempat lain.
- Setelah situs berjalan di server kampus, disarankan membuat kunci baru di Firebase Console
  (Project settings > Service accounts > Generate new private key), mengganti
  `admin/credentials.json`, lalu menghapus kunci lama.
- Untuk mengganti kunci dari luar tanpa build ulang, pasang file kunci sebagai volume ke
  `/var/www/html/admin/credentials.json` (lihat baris opsional di `docker-compose.yml`).

## Data permanen
Folder `/var/www/html/admin/uploads` berisi file unggahan (cover buku, foto staf, PDF skripsi).
Isi awalnya ikut di repository. Setelah situs berjalan, unggahan baru dari halaman admin
disimpan di volume permanen `elib_uploads` (bukan di repository). Lakukan backup volume ini
secara berkala.

## Kebutuhan jaringan
Container perlu akses keluar (HTTPS, port 443) ke domain Google:
`firestore.googleapis.com`, `oauth2.googleapis.com`, dan `www.googleapis.com`.

## Struktur singkat
- `/` : halaman publik (index, pencarian, detail, staff, berita, dll.)
- `admin/` : halaman admin, koneksi Firebase (`admin/koneksi.php`), dependensi Composer
