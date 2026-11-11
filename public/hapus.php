<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_post(); $id=student_id($_POST['id'] ?? null); $row=student($id);
    if (!$row) { http_response_code(404); exit('Pendaftar tidak ditemukan.'); }
    $query=db()->prepare('DELETE FROM calon_siswa WHERE id=? AND workspace_id=?');
    $query->execute([$id,$_SESSION['workspace']]);
    notice('Pendaftaran ' . $row['nama'] . ' berhasil dihapus.'); redirect('/list-siswa.php');
}
$row=student(student_id($_GET['id'] ?? null));
if (!$row) { http_response_code(404); page_start('Data tidak ditemukan','students'); echo '<div class="empty-state"><h1>Pendaftar tidak ditemukan.</h1><a href="/list-siswa.php">Kembali ke daftar</a></div>'; page_end(); exit; }
page_start('Hapus pendaftar','students');
?>
<a class="back-link" href="/list-siswa.php">← Kembali ke data pendaftar</a><section class="panel confirm-panel"><span class="delete-symbol"><?= icon('trash') ?></span><h1>Hapus pendaftaran ini?</h1><p>Data <strong><?= e($row['nama']) ?></strong> dari <?= e($row['sekolah_asal']) ?> akan dihapus dari ruang demo Anda. Tindakan ini tidak dapat dibatalkan.</p><form method="post" action="/hapus.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><div class="confirm-actions"><a class="button secondary" href="/list-siswa.php">Batal</a><button class="button destructive" type="submit">Ya, hapus pendaftar</button></div></form></section>
<?php page_end(); ?>
