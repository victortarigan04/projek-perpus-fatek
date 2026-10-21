<?php error_reporting(0); session_start();
 
require 'vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

// Path file JSON kredensial
$jsonPath = __DIR__ . '/service-account.json';

if (!file_exists($jsonPath)) {
    die("Fatal Error: File 'service-accounaat.json' tidak ditemukan!");
}

// Set environment variable
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $jsonPath);

try {
    // Inisialisasi koneksi Firestore dengan mode REST
  $db = new FirestoreClient([
    'projectId'      => 'driven-lore-472406-i6',
    'transport'      => 'rest',
    'requestTimeout' => 10 // Batas waktu maksimal 10 detik
]);
} catch (Exception $e) {
    die("Koneksi ke Firestore Gagal: " . $e->getMessage());
}

function check_login() {
    if (!isset($_SESSION['admin_logged_in'])) {
        header("Location: login.php");
        exit;
    }
}

function check_superadmin() {
    if ($_SESSION['admin_level'] !== 'superadmin') {
        echo "<script>alert('Akses ditolak! Halaman ini khusus Superadmin.'); window.location='dashboard.php';</script>";
        exit;
    }
}