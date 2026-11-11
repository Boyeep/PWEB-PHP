<?php
declare(strict_types=1);

function db(): PDO {
    static $connection = null;
    if ($connection instanceof PDO) return $connection;
    if (getenv('DB_DRIVER') === 'mysql') {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE') ?: 'pendaftaran_siswa');
        $connection = new PDO($dsn, getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    } else {
        $path = getenv('SQLITE_PATH') ?: dirname(__DIR__) . '/storage/admisia.sqlite';
        $connection = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $connection->exec('PRAGMA busy_timeout = 5000');
        $connection->exec('CREATE TABLE IF NOT EXISTS calon_siswa (id INTEGER PRIMARY KEY AUTOINCREMENT, workspace_id TEXT NOT NULL, nama TEXT NOT NULL, alamat TEXT NOT NULL, jenis_kelamin TEXT NOT NULL, agama TEXT NOT NULL, sekolah_asal TEXT NOT NULL, jurusan TEXT NOT NULL, created_at TEXT NOT NULL)');
        $connection->exec('CREATE INDEX IF NOT EXISTS siswa_workspace ON calon_siswa(workspace_id, id)');
    }
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Setiap pengunjung mendapat ruang demo sendiri. Data contoh bukan data siswa nyata.
    if (empty($_SESSION['seeded'])) {
        $connection->beginTransaction();
        try {
            $query = $connection->prepare('SELECT COUNT(*) FROM calon_siswa WHERE workspace_id = ?');
            $query->execute([$_SESSION['workspace']]);
            if ((int)$query->fetchColumn() === 0) {
                $examples = [
                    ['Alya Prameswari', 'Jl. Melati No. 12, Surabaya', 'Perempuan', 'Islam', 'SMP Negeri 1 Surabaya', PROGRAMS[0]],
                    ['Bima Aditya', 'Jl. Kenanga No. 8, Surabaya', 'Laki-laki', 'Kristen Protestan', 'SMP Negeri 6 Surabaya', PROGRAMS[1]],
                    ['Citra Anindya', 'Jl. Mawar No. 21, Sidoarjo', 'Perempuan', 'Hindu', 'SMP Negeri 2 Sidoarjo', PROGRAMS[2]],
                    ['Daffa Ramadhan', 'Jl. Anggrek No. 5, Surabaya', 'Laki-laki', 'Islam', 'SMP Negeri 3 Surabaya', PROGRAMS[0]],
                    ['Nadia Kirana', 'Jl. Dahlia No. 16, Gresik', 'Perempuan', 'Katolik', 'SMP Negeri 1 Gresik', PROGRAMS[2]],
                    ['Raka Mahendra', 'Jl. Flamboyan No. 9, Surabaya', 'Laki-laki', 'Buddha', 'SMP Negeri 5 Surabaya', PROGRAMS[1]],
                ];
                $insert = $connection->prepare('INSERT INTO calon_siswa (workspace_id,nama,alamat,jenis_kelamin,agama,sekolah_asal,jurusan,created_at) VALUES (?,?,?,?,?,?,?,?)');
                foreach ($examples as $row) $insert->execute([$_SESSION['workspace'], ...$row, date('Y-m-d H:i:s')]);
            }
            $connection->commit();
            $_SESSION['seeded'] = true;
        } catch (Throwable $error) { $connection->rollBack(); throw $error; }
    }
    return $connection;
}

function students(string $search = '', string $program = ''): array {
    $sql = 'SELECT * FROM calon_siswa WHERE workspace_id = ?';
    $params = [$_SESSION['workspace']];
    if ($search !== '') {
        // Escape wildcard, gunakan prepared statement untuk semua nilai input.
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        $sql .= " AND (nama LIKE ? ESCAPE '!' OR sekolah_asal LIKE ? ESCAPE '!')";
        array_push($params, $pattern, $pattern);
    }
    if (in_array($program, PROGRAMS, true)) { $sql .= ' AND jurusan = ?'; $params[] = $program; }
    $query = db()->prepare($sql . ' ORDER BY id DESC');
    $query->execute($params);
    return $query->fetchAll();
}

function student(int $id): array|false {
    $query = db()->prepare('SELECT * FROM calon_siswa WHERE id = ? AND workspace_id = ?');
    $query->execute([$id, $_SESSION['workspace']]);
    return $query->fetch();
}

function student_id(mixed $value): int {
    if (!is_string($value) || !ctype_digit($value) || (int)$value < 1) return 0;
    return (int)$value;
}
