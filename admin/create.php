<?php
require 'koneksi.php';

$db->collection('admins')->add([
    'nama'     => 'Super Admin',
    'email'    => 'admin@perpustakaan.com',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'level'    => 'superadmin'
]);

echo "Superadmin berhasil dibuat! Email: admin@perpustakaan.com | Pass: admin123";