<?php
// Explicit allowlist: user-controlled paths can never include arbitrary PHP files.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routes = ['index.php','list-siswa.php','form-daftar.php','form-edit.php','proses-pendaftaran.php','proses-edit.php','hapus.php','detail.php','panduan.php','blog.php'];
$file = $path === '/' ? 'index.php' : ltrim((string)$path, '/');
if (!in_array($file,$routes,true)) { http_response_code(404); exit('Halaman tidak ditemukan.'); }
require dirname(__DIR__) . '/public/' . $file;
