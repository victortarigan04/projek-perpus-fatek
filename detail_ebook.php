<?php
// Pengalihan tautan lama ke halaman detail terpadu
header('Location: detail.php?tipe=ebook&id=' . urlencode($_GET['id'] ?? ''), true, 301);
exit;
