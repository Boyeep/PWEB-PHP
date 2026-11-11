<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$row=student(student_id($_GET['id'] ?? null));
if (!$row) { http_response_code(404); page_start('Data tidak ditemukan','students'); echo '<div class="empty-state"><h1>Pendaftar tidak ditemukan.</h1><a href="/list-siswa.php">Kembali ke daftar</a></div>'; page_end(); exit; }
page_start('Detail pendaftar','students');
?>
<a class="back-link" href="/list-siswa.php">← Kembali ke data pendaftar</a><div class="page-heading"><div><p class="eyebrow">REG-<?= str_pad((string)$row['id'],4,'0',STR_PAD_LEFT) ?></p><h1><?= e($row['nama']) ?></h1><p>Terdaftar pada <?= e(date('d M Y, H:i',strtotime($row['created_at']))) ?> WIB.</p></div><a href="/form-edit.php?id=<?= $row['id'] ?>" class="button primary"><?= icon('edit') ?> Edit pendaftar</a></div><section class="panel detail-panel"><h2>Informasi lengkap</h2><dl class="detail-grid"><?php foreach(['nama'=>'Nama lengkap','jenis_kelamin'=>'Jenis kelamin','agama'=>'Agama / kepercayaan','sekolah_asal'=>'Sekolah asal','jurusan'=>'Program keahlian','alamat'=>'Alamat'] as $key=>$label): ?><div><dt><?= $label ?></dt><dd><?= e($row[$key]) ?></dd></div><?php endforeach ?></dl></section>
<?php page_end(); ?>
