CREATE DATABASE IF NOT EXISTS pendaftaran_siswa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pendaftaran_siswa;

CREATE TABLE IF NOT EXISTS calon_siswa (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workspace_id CHAR(32) NOT NULL,
    nama VARCHAR(64) NOT NULL,
    alamat VARCHAR(255) NOT NULL,
    jenis_kelamin VARCHAR(16) NOT NULL,
    agama VARCHAR(64) NOT NULL,
    sekolah_asal VARCHAR(64) NOT NULL,
    jurusan VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX siswa_workspace (workspace_id, id)
) ENGINE=InnoDB;
