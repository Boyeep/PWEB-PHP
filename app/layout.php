<?php
declare(strict_types=1);
function icon(string $name, string $class = ''): string {
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'edit' => '<path d="m16 3 5 5-13 13H3v-5L16 3Zm-3 3 5 5"/>',
        'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
        'book' => '<path d="M12 7c-3-3-7-3-10-2v15c3-1 7-1 10 2 3-3 7-3 10-2V5c-3-1-7-1-10 2Zm0 0v15"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8m-8 4h5"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'code' => '<path d="m8 7-5 5 5 5m8-10 5 5-5 5m-3-14-2 18"/>',
    ];
    return '<svg class="icon ' . e($class) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['file']) . '</svg>';
}

function page_start(string $title, string $active = 'overview'): void {
    $notice = $_SESSION['notice'] ?? null;
    unset($_SESSION['notice']);
    // Commit durable session/database before emitting a success page.
    session_write_close();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Admisia, aplikasi pendaftaran siswa baru berbasis PHP. Kelola pendaftaran, cari data, dan perbarui informasi siswa.">
  <title><?= e($title) ?> — Admisia</title>
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
  <link rel="preload" href="/assets/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/style.css"><script src="/assets/app.js" defer></script>
</head>
<body>
<a class="skip" href="#main">Lewati navigasi</a>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="/index.php"><span class="brand-mark"><?= icon('book') ?></span>admisia<span class="brand-dot">.</span></a>
  <div class="school"><span class="school-avatar">SC</span><div><strong>SMK Cakrawala</strong><small>Portal pendaftaran siswa</small></div></div>
  <p class="nav-label">RUANG ADMINISTRASI</p>
  <nav aria-label="Navigasi utama">
    <?php foreach ([['overview','/index.php','grid','Ringkasan'],['students','/list-siswa.php','users','Data pendaftar'],['register','/form-daftar.php','plus','Tambah pendaftar'],['guide','/panduan.php','book','Panduan']] as [$key,$href,$symbol,$label]): ?>
    <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= $href ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><?= icon($symbol) ?><?= $label ?></a>
    <?php endforeach ?>
  </nav>
  <div class="sidebar-note"><span class="small-symbol"><?= icon('code') ?></span><strong>Belajar dari prosesnya.</strong><p>Dari satu formulir, menuju pengelolaan data yang lebih rapi.</p><a href="/blog.php">Catatan pengembangan <?= icon('arrow') ?></a></div>
  <div class="sidebar-bottom"><span class="online-dot"></span> Ruang demo pribadi</div>
</aside>
<div class="app-shell">
<header class="topbar"><div class="breadcrumb"><button class="menu-toggle icon-button" aria-label="Buka navigasi" aria-expanded="false" aria-controls="sidebar"><?= icon('menu') ?></button><span>Administrasi</span><span class="divider">/</span><strong><?= e($title) ?></strong></div><div class="topbar-right"><span class="demo-badge">DEMO</span><span class="avatar">AD</span><span class="admin-name">Administrator</span></div></header>
<main id="main">
<?php if ($notice): ?><div class="notice" role="status"><?= icon('check') ?><?= e($notice) ?></div><?php endif;
}

function page_end(): void { ?>
</main><footer class="footer"><span>© <?= date('Y') ?> Admisia · SMK Cakrawala</span><span>Demo pembelajaran. Gunakan data fiktif.</span><a href="https://github.com/Boyeep/PWEB-PHP" target="_blank" rel="noopener noreferrer">Source code <?= icon('arrow') ?></a></footer></div>
</body></html>
<?php }

function student_table(array $rows, bool $actions = true, int $offset = 0): void { ?>
<div class="table-scroll" tabindex="0" role="region" aria-label="Tabel pendaftar, geser untuk melihat seluruh kolom"><table>
  <thead><tr><th scope="col">PENDAFTAR</th><th scope="col">SEKOLAH ASAL</th><th scope="col">JURUSAN PILIHAN</th><th scope="col">TANGGAL DAFTAR</th><?php if ($actions): ?><th scope="col" class="text-right">TINDAKAN</th><?php endif ?></tr></thead>
  <tbody><?php foreach ($rows as $row): ?><tr>
    <td><div class="student-cell"><span class="student-avatar tone-<?= $row['id'] % 4 ?>"><?= e(mb_strtoupper(mb_substr($row['nama'],0,1))) ?></span><div><a class="student-name" href="/detail.php?id=<?= $row['id'] ?>"><?= e($row['nama']) ?></a><small>REG-<?= str_pad((string)$row['id'],4,'0',STR_PAD_LEFT) ?> · <?= e($row['jenis_kelamin']) ?></small></div></div></td>
    <td><?= e($row['sekolah_asal']) ?></td><td><span class="program-badge"><?= e(PROGRAM_CODES[$row['jurusan']] ?? $row['jurusan']) ?></span></td>
    <td class="muted"><?= e(date('d M Y', strtotime($row['created_at']))) ?></td>
    <?php if ($actions): ?><td><div class="row-actions"><a class="icon-button" href="/form-edit.php?id=<?= $row['id'] ?>" aria-label="Edit <?= e($row['nama']) ?>" title="Edit"><?= icon('edit') ?></a><a class="icon-button danger" href="/hapus.php?id=<?= $row['id'] ?>" aria-label="Hapus <?= e($row['nama']) ?>" title="Hapus"><?= icon('trash') ?></a></div></td><?php endif ?>
  </tr><?php endforeach ?>
  <?php if (!$rows): ?><tr><td colspan="<?= $actions ? 5 : 4 ?>"><div class="empty-state"><?= icon('search') ?><h3>Belum ada hasil yang sesuai</h3><p>Coba kata kunci lain atau ubah filter jurusan.</p><a href="/list-siswa.php">Tampilkan semua pendaftar</a></div></td></tr><?php endif ?>
  </tbody>
</table></div>
<?php }
