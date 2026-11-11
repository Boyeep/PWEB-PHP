<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$current = student(student_id($_GET['id'] ?? null));
if (!$current) { http_response_code(404); page_start('Data tidak ditemukan','students'); echo '<div class="empty-state"><h1>Pendaftar tidak ditemukan.</h1><p>Data mungkin sudah dihapus atau berada di ruang demo lain.</p><a class="button primary" href="/list-siswa.php">Kembali ke daftar</a></div>'; page_end(); exit; }
require dirname(__DIR__) . '/app/student-form.php';
