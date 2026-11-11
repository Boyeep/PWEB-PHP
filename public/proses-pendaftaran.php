<?php
require dirname(__DIR__) . '/app/bootstrap.php';
check_post();
[$data,$errors] = validate_student($_POST);
if ($errors) { $_SESSION['old']=$data; $_SESSION['errors']=$errors; redirect('/form-daftar.php'); }
if (count(students()) >= 100) { notice('Ruang demo sudah berisi 100 pendaftar. Hapus data sebelum menambah lagi.'); redirect('/list-siswa.php'); }
$query = db()->prepare('INSERT INTO calon_siswa (workspace_id,nama,alamat,jenis_kelamin,agama,sekolah_asal,jurusan,created_at) VALUES (?,?,?,?,?,?,?,?)');
$query->execute([$_SESSION['workspace'],$data['nama'],$data['alamat'],$data['jenis_kelamin'],$data['agama'],$data['sekolah_asal'],$data['jurusan'],date('Y-m-d H:i:s')]);
notice('Pendaftaran ' . $data['nama'] . ' berhasil disimpan.');
redirect('/list-siswa.php');
