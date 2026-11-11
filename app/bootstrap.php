<?php
declare(strict_types=1);

// Konfigurasi .env lokal; environment hosting tetap diprioritaskan.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
    }
}

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $exception): void {
    error_log((string)$exception);
    http_response_code($exception->getCode() === 409 ? 409 : 503);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Layanan belum tersedia</title><body><h1>Data belum berhasil diproses.</h1><p>Muat ulang halaman dan coba kembali. Jika sedang mengedit dari beberapa tab, gunakan satu tab terlebih dahulu.</p><a href="/index.php">Kembali ke beranda</a></body></html>';
});
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https://raw.githubusercontent.com; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
session_name('admisia_session');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => '/']);
if (getenv('BLOB_READ_WRITE_TOKEN')) {
    require_once __DIR__ . '/blob-session.php';
    session_set_save_handler(new BlobSessionHandler(), true);
}
session_start();
$_SESSION['workspace'] ??= bin2hex(random_bytes(16));
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { session_write_close(); header('Location: ' . $path, true, 303); exit; }
function input(string $key, array $source): string { return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : ''; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }
function check_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST'); http_response_code(405); exit('Metode tidak diizinkan. Gunakan formulir.'); }
    if (!hash_equals($_SESSION['csrf'], input('csrf', $_POST))) { http_response_code(403); exit('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.'); }
}
function notice(string $text): void { $_SESSION['notice'] = $text; }
const GENDERS = ['Laki-laki', 'Perempuan'];
const RELIGIONS = ['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan terhadap Tuhan YME'];
const PROGRAMS = ['Rekayasa Perangkat Lunak', 'Teknik Komputer dan Jaringan', 'Desain Komunikasi Visual'];
const PROGRAM_CODES = ['Rekayasa Perangkat Lunak' => 'RPL', 'Teknik Komputer dan Jaringan' => 'TKJ', 'Desain Komunikasi Visual' => 'DKV'];

function validate_student(array $source): array {
    $data = []; $errors = [];
    foreach (['nama', 'alamat', 'sekolah_asal', 'jenis_kelamin', 'agama', 'jurusan'] as $key) $data[$key] = input($key, $source);
    foreach (['nama' => [2,64,'Nama lengkap'], 'alamat' => [5,255,'Alamat'], 'sekolah_asal' => [2,64,'Sekolah asal']] as $key => [$min,$max,$label]) {
        $length = mb_strlen($data[$key]);
        if ($length < $min || $length > $max) $errors[$key] = "$label harus berisi {$min}–{$max} karakter.";
    }
    foreach (['jenis_kelamin' => GENDERS, 'agama' => RELIGIONS, 'jurusan' => PROGRAMS] as $key => $choices) {
        if (!in_array($data[$key], $choices, true)) $errors[$key] = 'Pilih salah satu pilihan yang tersedia.';
    }
    return [$data, $errors];
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/layout.php';
