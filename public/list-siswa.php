<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$search = mb_substr(input('q', $_GET),0,100); $program = input('jurusan', $_GET);
$all = students($search,$program); $total = count($all); $pages = max(1,(int)ceil($total/8));
$page = max(1,min($pages,(int)input('page',$_GET))); $offset = ($page-1)*8;
page_start('Data pendaftar','students');
?>
<div class="page-heading"><div><p class="eyebrow">DIREKTORI SISWA</p><h1>Data pendaftar</h1><p>Temukan, lihat, dan perbarui informasi calon siswa.</p></div><a class="button primary" href="/form-daftar.php"><?= icon('plus') ?> Tambah pendaftar</a></div>
<section class="panel"><div class="panel-heading"><div><h2>Semua pendaftar <span class="count-badge"><?= $total ?></span></h2><p>Data di ruang demo pribadi Anda.</p></div></div>
<form class="filter-bar" method="get" action="/list-siswa.php"><div class="search-input"><?= icon('search') ?><label class="sr-only" for="q">Cari nama atau sekolah asal</label><input type="search" id="q" name="q" maxlength="100" placeholder="Cari nama atau sekolah asal..." value="<?= e($search) ?>"></div><label class="sr-only" for="jurusan">Filter jurusan</label><select id="jurusan" name="jurusan"><option value="">Semua jurusan</option><?php foreach(PROGRAMS as $value): ?><option value="<?= e($value) ?>" <?= $program===$value?'selected':'' ?>><?= e($value) ?></option><?php endforeach ?></select><button class="button secondary" type="submit">Terapkan</button><?php if($search || $program): ?><a class="reset-link" href="/list-siswa.php">Reset</a><?php endif ?></form>
<?php student_table(array_slice($all,$offset,8)); ?>
<div class="pagination"><span><?= $total ? $offset+1 : 0 ?>–<?= min($offset+8,$total) ?> dari <?= $total ?> pendaftar</span><nav aria-label="Halaman data pendaftar"><?php for($i=1;$i<=$pages;$i++): ?><a href="?<?= e(http_build_query(['q'=>$search,'jurusan'=>$program,'page'=>$i])) ?>" class="page-link <?= $page===$i?'current':'' ?>" <?= $page===$i?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor ?></nav></div></section>
<p class="table-tip"><?= icon('file') ?> Klik nama pendaftar untuk melihat informasi lengkap.</p>
<?php page_end(); ?>
