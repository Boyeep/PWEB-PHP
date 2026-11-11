<?php
$editing = isset($current);
$old = $_SESSION['old'] ?? []; $errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);
$values = array_merge(['nama'=>'','alamat'=>'','jenis_kelamin'=>'','agama'=>'','sekolah_asal'=>'','jurusan'=>''], $current ?? [], $old);
$field = function(string $key) use($errors): string { return isset($errors[$key]) ? 'aria-invalid="true" aria-describedby="error-' . $key . '"' : ''; };
$error = function(string $key) use($errors): void { if(isset($errors[$key])) echo '<p class="field-error" id="error-' . $key . '">' . e($errors[$key]) . '</p>'; };
page_start($editing?'Edit pendaftar':'Tambah pendaftar',$editing?'students':'register');
?>
<a class="back-link" href="/list-siswa.php">← Kembali ke data pendaftar</a>
<div class="page-heading"><div><p class="eyebrow">FORMULIR PENDAFTARAN</p><h1><?= $editing?'Perbarui data pendaftar.':'Awal perjalanan baru.' ?></h1><p><?= $editing?'Pastikan informasi calon siswa tetap akurat.':'Lengkapi informasi calon siswa untuk memulai pendaftaran.' ?></p></div></div>
<div class="form-layout"><aside class="form-aside"><div class="form-step active"><span>01</span><div><strong>Informasi calon siswa</strong><p>Identitas dan sekolah asal</p></div></div><div class="form-step"><span>02</span><div><strong>Simpan pendaftaran</strong><p>Data siap untuk dikelola</p></div></div><div class="form-help"><?= icon('file') ?><h3>Sebelum mengisi</h3><p>Semua kolom wajib diisi. Periksa kembali nama dan sekolah asal sebelum menyimpan.</p><p>Ini ruang demo pribadi. Gunakan data fiktif untuk mencoba aplikasi.</p></div></aside>
<section class="panel form-panel"><div class="panel-heading"><div><h2><?= $editing?'Informasi pendaftar':'Informasi calon siswa' ?></h2><p>Isian bertanda <span class="required">*</span> wajib dilengkapi.</p></div><span class="form-number">01 / 02</span></div>
<?php if($errors): ?><div class="error-summary" role="alert" tabindex="-1"><strong>Data belum tersimpan.</strong> Periksa kolom yang ditandai di bawah.</div><?php endif ?>
<form method="post" action="<?= $editing?'/proses-edit.php':'/proses-pendaftaran.php' ?>" class="student-form">
<?= csrf_field() ?><?php if($editing): ?><input type="hidden" name="id" value="<?= $current['id'] ?>"><?php endif ?>
<div class="form-section-title"><span class="small-symbol"><?= icon('users') ?></span><div><h3>Data diri</h3><p>Identitas dasar calon siswa.</p></div></div>
<div class="form-grid"><div class="field full"><label for="nama">Nama lengkap <span class="required">*</span></label><input id="nama" name="nama" required minlength="2" maxlength="64" autocomplete="off" placeholder="Masukkan nama lengkap" value="<?= e($values['nama']) ?>" <?= $field('nama') ?>><?php $error('nama') ?></div>
<div class="field"><label for="jenis_kelamin">Jenis kelamin <span class="required">*</span></label><select id="jenis_kelamin" name="jenis_kelamin" required <?= $field('jenis_kelamin') ?>><option value="">Pilih jenis kelamin</option><?php foreach(GENDERS as $value): ?><option <?= $value===$values['jenis_kelamin']?'selected':'' ?>><?= e($value) ?></option><?php endforeach ?></select><?php $error('jenis_kelamin') ?></div>
<div class="field"><label for="agama">Agama / kepercayaan <span class="required">*</span></label><select id="agama" name="agama" required <?= $field('agama') ?>><option value="">Pilih agama / kepercayaan</option><?php foreach(RELIGIONS as $value): ?><option <?= $value===$values['agama']?'selected':'' ?>><?= e($value) ?></option><?php endforeach ?></select><?php $error('agama') ?></div>
<div class="field full"><label for="alamat">Alamat <span class="required">*</span></label><textarea id="alamat" name="alamat" rows="3" required minlength="5" maxlength="255" placeholder="Nama jalan, nomor, dan kota" <?= $field('alamat') ?>><?= e($values['alamat']) ?></textarea><?php $error('alamat') ?></div></div>
<div class="form-section-title separated"><span class="small-symbol"><?= icon('book') ?></span><div><h3>Informasi pendidikan</h3><p>Sekolah asal dan program keahlian yang diminati.</p></div></div>
<div class="form-grid"><div class="field full"><label for="sekolah_asal">Sekolah asal <span class="required">*</span></label><input id="sekolah_asal" name="sekolah_asal" required minlength="2" maxlength="64" placeholder="Contoh: SMP Negeri 1 Surabaya" value="<?= e($values['sekolah_asal']) ?>" <?= $field('sekolah_asal') ?>><?php $error('sekolah_asal') ?></div><div class="field full"><label for="program">Program keahlian <span class="required">*</span></label><select id="program" name="jurusan" required <?= $field('jurusan') ?>><option value="">Pilih program keahlian</option><?php foreach(PROGRAMS as $value): ?><option <?= $value===$values['jurusan']?'selected':'' ?>><?= e($value) ?></option><?php endforeach ?></select><?php $error('jurusan') ?></div></div>
<div class="form-actions"><a href="/list-siswa.php" class="button secondary">Batal</a><button class="button primary" type="submit"><?= icon('check') ?> <?= $editing?'Simpan perubahan':'Simpan pendaftaran' ?></button></div>
</form></section></div>
<?php page_end(); ?>
