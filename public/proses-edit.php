<?php
require dirname(__DIR__) . '/app/bootstrap.php';
check_post();
$id = student_id($_POST['id'] ?? null);
if (!student($id)) { http_response_code(404); exit('Pendaftar tidak ditemukan.'); }
[$data,$errors] = validate_student($_POST);
if ($errors) { $_SESSION['old']=$data; $_SESSION['errors']=$errors; redirect('/form-edit.php?id=' . $id); }
$query = db()->prepare('UPDATE calon_siswa SET nama=?,alamat=?,jenis_kelamin=?,agama=?,sekolah_asal=?,jurusan=? WHERE id=? AND workspace_id=?');
$query->execute([$data['nama'],$data['alamat'],$data['jenis_kelamin'],$data['agama'],$data['sekolah_asal'],$data['jurusan'],$id,$_SESSION['workspace']]);
notice('Data ' . $data['nama'] . ' berhasil diperbarui.');
redirect('/list-siswa.php');
