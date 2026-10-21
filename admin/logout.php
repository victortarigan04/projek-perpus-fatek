<?php
session_start();

// Kosongkan semua data variabel sesi
$_SESSION = array();

// Hapus cookie sesi jika tersimpan di browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi sepenuhnya
session_destroy();

// Alihkan kembali ke halaman login
header("Location: login.php");
exit;