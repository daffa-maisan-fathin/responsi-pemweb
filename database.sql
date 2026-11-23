-- ========================================================
-- DATABASE: db_questudy
-- Platform: Questudy — LMS Gamifikasi, Ruang Belajar Pribadi, dan Peringatan Dini
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_questudy` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_questudy`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `laporan`;
DROP TABLE IF EXISTS `pengaturan_sistem`;
DROP TABLE IF EXISTS `tindak_lanjut`;
DROP TABLE IF EXISTS `peringatan_dini_aturan`;
DROP TABLE IF EXISTS `planner`;
DROP TABLE IF EXISTS `flashcards`;
DROP TABLE IF EXISTS `sesi_pomodoro`;
DROP TABLE IF EXISTS `user_badges`;
DROP TABLE IF EXISTS `badges`;
DROP TABLE IF EXISTS `gamifikasi_aturan`;
DROP TABLE IF EXISTS `booking_tutor`;
DROP TABLE IF EXISTS `hasil_kuis`;
DROP TABLE IF EXISTS `soal_kuis`;
DROP TABLE IF EXISTS `kuis`;
DROP TABLE IF EXISTS `pengumpulan_tugas`;
DROP TABLE IF EXISTS `tugas`;
DROP TABLE IF EXISTS `forum_materi`;
DROP TABLE IF EXISTS `catatan_pribadi`;
DROP TABLE IF EXISTS `progres_materi`;
DROP TABLE IF EXISTS `materi`;
DROP TABLE IF EXISTS `pengumuman`;
DROP TABLE IF EXISTS `anggota_kelas`;
DROP TABLE IF EXISTS `kelas`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABEL USERS
CREATE TABLE `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','pengajar','pelajar') NOT NULL,
  `status_akun` ENUM('aktif','nonaktif','pending') DEFAULT 'aktif',
  `keahlian` VARCHAR(255) NULL,
  `deskripsi_tutor` TEXT NULL,
  `portofolio_url` VARCHAR(255) NULL,
  `foto_profil` VARCHAR(255) DEFAULT 'default.png',
  `xp` INT DEFAULT 0,
  `level` INT DEFAULT 1,
  `streak_harian` INT DEFAULT 1,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL KELAS
CREATE TABLE `kelas` (
  `id_kelas` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pengajar` INT NOT NULL,
  `kode_kelas` VARCHAR(10) NOT NULL UNIQUE,
  `nama_kelas` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NULL,
  `kategori` VARCHAR(50) NOT NULL DEFAULT 'Lainnya',
  `tingkat` ENUM('pemula','menengah','lanjut') NOT NULL DEFAULT 'pemula',
  `kuota` INT NOT NULL DEFAULT 20,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kelas_pengajar` FOREIGN KEY (`id_pengajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL ANGGOTA_KELAS
CREATE TABLE `anggota_kelas` (
  `id_anggota` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `id_pelajar` INT NOT NULL,
  `bergabung_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_kelas_pelajar` (`id_kelas`, `id_pelajar`),
  CONSTRAINT `fk_anggota_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  CONSTRAINT `fk_anggota_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL PENGUMUMAN
CREATE TABLE `pengumuman` (
  `id_pengumuman` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `isi` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pengumuman_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL MATERI
CREATE TABLE `materi` (
  `id_materi` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `tipe` ENUM('teks','file','tautan') NOT NULL,
  `konten_teks` TEXT NULL,
  `file_materi` VARCHAR(255) NULL,
  `tautan_url` VARCHAR(255) NULL,
  `urutan` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_materi_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL PROGRES_MATERI
CREATE TABLE `progres_materi` (
  `id_progres` INT AUTO_INCREMENT PRIMARY KEY,
  `id_materi` INT NOT NULL,
  `id_pelajar` INT NOT NULL,
  `selesai_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_progres_materi` (`id_materi`, `id_pelajar`),
  CONSTRAINT `fk_progres_materi` FOREIGN KEY (`id_materi`) REFERENCES `materi` (`id_materi`) ON DELETE CASCADE,
  CONSTRAINT `fk_progres_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABEL CATATAN_PRIBADI
CREATE TABLE `catatan_pribadi` (
  `id_catatan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_materi` INT NOT NULL,
  `id_pelajar` INT NOT NULL,
  `isi_catatan` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_catatan_materi_pelajar` (`id_materi`, `id_pelajar`),
  CONSTRAINT `fk_catatan_materi` FOREIGN KEY (`id_materi`) REFERENCES `materi` (`id_materi`) ON DELETE CASCADE,
  CONSTRAINT `fk_catatan_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABEL FORUM_MATERI
CREATE TABLE `forum_materi` (
  `id_forum` INT AUTO_INCREMENT PRIMARY KEY,
  `id_materi` INT NOT NULL,
  `id_user` INT NOT NULL,
  `isi_komentar` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_forum_materi` FOREIGN KEY (`id_materi`) REFERENCES `materi` (`id_materi`) ON DELETE CASCADE,
  CONSTRAINT `fk_forum_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABEL TUGAS
CREATE TABLE `tugas` (
  `id_tugas` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `petunjuk` TEXT NOT NULL,
  `tenggat_waktu` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tugas_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABEL PENGUMPULAN_TUGAS
CREATE TABLE `pengumpulan_tugas` (
  `id_kumpul` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tugas` INT NOT NULL,
  `id_pelajar` INT NOT NULL,
  `jawaban_teks` TEXT NULL,
  `file_jawaban` VARCHAR(255) NULL,
  `waktu_kumpul` DATETIME NOT NULL,
  `status` ENUM('tepat_waktu','terlambat') NOT NULL,
  `nilai` INT NULL,
  `komentar_pengajar` TEXT NULL,
  UNIQUE KEY `uk_kumpul_tugas_pelajar` (`id_tugas`, `id_pelajar`),
  CONSTRAINT `fk_kumpul_tugas` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`) ON DELETE CASCADE,
  CONSTRAINT `fk_kumpul_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. TABEL KUIS
CREATE TABLE `kuis` (
  `id_kuis` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `durasi_menit` INT DEFAULT 15,
  `maks_percobaan` INT DEFAULT 2,
  `tenggat_waktu` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kuis_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. TABEL SOAL_KUIS
CREATE TABLE `soal_kuis` (
  `id_soal` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kuis` INT NOT NULL,
  `pertanyaan` TEXT NOT NULL,
  `opsi_a` TEXT NOT NULL,
  `opsi_b` TEXT NOT NULL,
  `opsi_c` TEXT NOT NULL,
  `opsi_d` TEXT NOT NULL,
  `kunci_jawaban` ENUM('a','b','c','d') NOT NULL,
  CONSTRAINT `fk_soal_kuis` FOREIGN KEY (`id_kuis`) REFERENCES `kuis` (`id_kuis`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. TABEL HASIL_KUIS
CREATE TABLE `hasil_kuis` (
  `id_hasil` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kuis` INT NOT NULL,
  `id_pelajar` INT NOT NULL,
  `percobaan_ke` INT NOT NULL,
  `skor` INT NOT NULL,
  `waktu_selesai` DATETIME NOT NULL,
  CONSTRAINT `fk_hasil_kuis` FOREIGN KEY (`id_kuis`) REFERENCES `kuis` (`id_kuis`) ON DELETE CASCADE,
  CONSTRAINT `fk_hasil_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. TABEL BOOKING_TUTOR
CREATE TABLE `booking_tutor` (
  `id_booking` INT AUTO_INCREMENT PRIMARY KEY,
  `id_mahasiswa` INT NOT NULL,
  `id_tutor` INT NOT NULL,
  `id_kelas` INT NULL,
  `tanggal_sesi` DATE NOT NULL,
  `jam_sesi` TIME NOT NULL,
  `link_meeting` VARCHAR(255) NULL,
  `catatan` TEXT NULL,
  `status` ENUM('pending','disetujui','selesai','dibatalkan') DEFAULT 'pending',
  `rating` INT NULL,
  `ulasan` TEXT NULL,
  `alasan_batal` VARCHAR(255) NULL,
  CONSTRAINT `fk_booking_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. TABEL GAMIFIKASI_ATURAN
CREATE TABLE `gamifikasi_aturan` (
  `id_aturan` INT AUTO_INCREMENT PRIMARY KEY,
  `aksi` VARCHAR(50) NOT NULL UNIQUE,
  `keterangan` VARCHAR(150) NOT NULL,
  `xp_didapat` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. TABEL BADGES
CREATE TABLE `badges` (
  `id_badge` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_badge` VARCHAR(100) NOT NULL,
  `deskripsi` VARCHAR(255) NOT NULL,
  `syarat_xp` INT NOT NULL,
  `ikon` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. TABEL USER_BADGES
CREATE TABLE `user_badges` (
  `id_ub` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelajar` INT NOT NULL,
  `id_badge` INT NOT NULL,
  `diperoleh_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_badge` (`id_pelajar`, `id_badge`),
  CONSTRAINT `fk_ub_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_ub_badge` FOREIGN KEY (`id_badge`) REFERENCES `badges` (`id_badge`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. TABEL SESI_POMODORO
CREATE TABLE `sesi_pomodoro` (
  `id_sesi` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelajar` INT NOT NULL,
  `durasi_menit` INT NOT NULL DEFAULT 25,
  `tanggal` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pomodoro_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. TABEL FLASHCARDS
CREATE TABLE `flashcards` (
  `id_card` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelajar` INT NOT NULL,
  `pertanyaan` TEXT NOT NULL,
  `jawaban` TEXT NOT NULL,
  `status_hafal` ENUM('hafal','belum') DEFAULT 'belum',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_flashcards_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. TABEL PLANNER
CREATE TABLE `planner` (
  `id_plan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelajar` INT NOT NULL,
  `judul_target` VARCHAR(200) NOT NULL,
  `tanggal_target` DATE NOT NULL,
  `status` ENUM('belum','selesai') DEFAULT 'belum',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_planner_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. TABEL PERINGATAN_DINI_ATURAN
CREATE TABLE `peringatan_dini_aturan` (
  `id_rule` INT AUTO_INCREMENT PRIMARY KEY,
  `max_telat_tugas` INT DEFAULT 2,
  `min_rata_nilai` INT DEFAULT 65,
  `max_hari_tidak_login` INT DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. TABEL TINDAK_LANJUT
CREATE TABLE `tindak_lanjut` (
  `id_tl` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kelas` INT NOT NULL,
  `id_pengajar` INT NOT NULL,
  `id_pelajar` INT NOT NULL,
  `catatan_tindak_lanjut` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tl_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  CONSTRAINT `fk_tl_pengajar` FOREIGN KEY (`id_pengajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_tl_pelajar` FOREIGN KEY (`id_pelajar`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pengaturan kuota & batas (dapat diubah admin lewat admin/pengaturan.php)
CREATE TABLE `pengaturan_sistem` (
  `kunci` VARCHAR(50) PRIMARY KEY,
  `nilai` INT NOT NULL,
  `keterangan` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laporan pelanggaran dari pengguna
CREATE TABLE `laporan` (
  `id_laporan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelapor` INT NOT NULL,
  `id_terlapor` INT NULL,
  `jenis` ENUM('tutor','pelajar','kelas','komentar','lainnya') NOT NULL,
  `alasan` TEXT NOT NULL,
  `status` ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  `tindakan` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_pelapor`) REFERENCES `users`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_terlapor`) REFERENCES `users`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DATA DUMMY (SIAP DEMO DAN TESTING SEMUA FITUR)
-- Catatan: Password semua akun adalah "123456"
-- ========================================================

-- Users (1 Admin, 2 Tutor aktif, 1 Tutor pending, 3 Pelajar)
INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `status_akun`, `keahlian`, `foto_profil`, `xp`, `level`, `streak_harian`, `last_login`) VALUES
(1, 'Administrator Questudy', 'admin@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'admin', 'aktif', 'Sistem Administrator & Analis Pendidikan', 'default.png', 0, 1, 1, NOW()),
(2, 'Budi Santoso', 'pengajar@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'pengajar', 'aktif', 'Web Development, PHP 8 & AI', 'default.png', 0, 1, 5, NOW()),
(3, 'Siti Aminah', 'siti@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'pengajar', 'aktif', 'Basis Data MySQL & Desain UI/UX', 'default.png', 0, 1, 3, NOW()),
(4, 'Reza Pratama', 'tutor.pending@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'pengajar', 'pending', 'Cloud Computing & DevOps', 'default.png', 0, 1, 0, NULL),
(5, 'Ahmad Fauzi', 'pelajar@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'pelajar', 'aktif', 'Frontend & Full-Stack Learner', 'default.png', 450, 5, 7, NOW()),
(6, 'Dewi Lestari', 'dewi@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'pelajar', 'aktif', 'UI/UX Enthusiast', 'default.png', 220, 3, 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 'Rizky Ramadhan', 'rizky@questudy.id', '$2y$10$MaQbP4HlHjxX3JnrRuYJM.CnlmTgjtPEjwGKwgP0nlZqR0GuFDtym', 'pelajar', 'aktif', 'Junior Programmer', 'default.png', 40, 1, 0, DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Aturan Gamifikasi
INSERT INTO `gamifikasi_aturan` (`id_aturan`, `aksi`, `keterangan`, `xp_didapat`) VALUES
(1, 'login_harian', 'Bonus Login Harian & Menjaga Streak', 10),
(2, 'selesai_materi', 'Menyelesaikan Membaca Modul Materi', 20),
(3, 'kumpul_tugas', 'Mengumpulkan Tugas Tepat Waktu', 50),
(4, 'kuis_sempurna', 'Menyelesaikan Kuis dengan Skor Sempurna', 40),
(5, 'sesi_pomodoro', 'Menuntaskan Sesi Fokus Belajar Pomodoro 25 Menit', 15),
(6, 'sesi_tutor', 'Menyelesaikan Sesi Mentoring dengan Tutor', 30);

-- Badges
INSERT INTO `badges` (`id_badge`, `nama_badge`, `deskripsi`, `syarat_xp`, `ikon`) VALUES
(1, 'Pionir Belajar', 'Langkah pertama menapaki dunia ilmu pengetahuan dengan 50 XP.', 50, '🌱'),
(2, 'Pembelajar Rajin', 'Konsisten meningkatkan wawasan belajar hingga mencapai 150 XP.', 150, '📖'),
(3, 'Master Kuis', 'Berprestasi tinggi dan menyelesaikan tantangan hingga 300 XP.', 300, '🏆'),
(4, 'Pendekar Fokus', 'Mendedikasikan waktu belajar ruang pribadi hingga 500 XP.', 500, '🔥'),
(5, 'Cendekiawan Questudy', 'Tingkat kemahiran dan kontribusi puncak dengan 1000 XP.', 1000, '👑');

-- User Badges (Ahmad dapat 3 badges, Dewi 2 badges, Rizky 0 badges)
INSERT INTO `user_badges` (`id_pelajar`, `id_badge`, `diperoleh_pada`) VALUES
(5, 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(5, 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(5, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 2, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Aturan Peringatan Dini
INSERT INTO `peringatan_dini_aturan` (`id_rule`, `max_telat_tugas`, `min_rata_nilai`, `max_hari_tidak_login`) VALUES
(1, 2, 65, 5);

-- Kelas (2 Kelas)
INSERT INTO `kelas` (`id_kelas`, `id_pengajar`, `kode_kelas`, `nama_kelas`, `deskripsi`) VALUES
(1, 2, 'PW2026', 'Pemrograman Web', 'Mempelajari arsitektur web modern, PHP 8 Native, integrasi database relasional, dan implementasi UI/UX ramah pengguna yang inklusif.'),
(2, 3, 'BD2026', 'Basis Data & SQL Komprehensif', 'Perancangan skema relasional, normalisasi 1NF-3NF, optimalisasi query MySQL, dan keamanan data aplikasi.');

-- Anggota Kelas
INSERT INTO `anggota_kelas` (`id_kelas`, `id_pelajar`, `bergabung_pada`) VALUES
(1, 5, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 6, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 7, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 5, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 6, DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Pengumuman Kelas
INSERT INTO `pengumuman` (`id_pengumuman`, `id_kelas`, `judul`, `isi`, `created_at`) VALUES
(1, 1, 'Selamat Datang di Pembelajaran Interaktif Questudy!', 'Selamat datang! Mari belajar dengan disiplin dan menyenangkan. Kumpulkan XP dari setiap materi, tugas, dan kuis.', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(2, 1, 'Pengingat: Tugas 02 & Kuis 01 Sudah Dibuka', 'Pastikan membaca materi pendukung terlebih dahulu di menu Materi sebelum mengerjakan kuis dan mengunggah tugas.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 'Materi Normalisasi Relasional Telah Tersedia', 'Silakan unduh dokumen panduan perancangan relasi database di tab Materi.', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Materi Kelas
INSERT INTO `materi` (`id_materi`, `id_kelas`, `judul`, `tipe`, `konten_teks`, `file_materi`, `tautan_url`, `urutan`, `created_at`) VALUES
(1, 1, '1. Pengenalan PHP 8 Native & Keamanan Prepared Statements', 'teks', 'PHP 8 membawa berbagai peningkatan performa signifikan berkat JIT Compiler. Dalam membangun aplikasi database, keamanan adalah prioritas nomor satu. Penggunaan Prepared Statements dengan PDO atau MySQLi memisahkan antara instruksi SQL dan parameter input data pengguna secara mutlak. Hal ini menutup celah serangan SQL Injection secara tuntas.\n\nContoh implementasi aman PDO:\n$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");\n$stmt->execute([$email]);\n$user = $stmt->fetch();', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 1, '2. Panduan Desain Antarmuka dengan Tailwind CSS', 'tautan', NULL, NULL, 'https://tailwindcss.com/docs', 2, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(3, 1, '3. Modul Praktikum Integrasi PDO & Transaksi', 'file', NULL, 'modul_php_pdo.pdf', NULL, 3, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(4, 2, '1. Konsep Normalisasi Data 1NF hingga 3NF', 'teks', 'Normalisasi adalah proses pengorganisasian data dalam database relasional guna mengurangi redundansi dan meningkatkan integritas data.\n\n- 1NF: Setiap kolom bernilai atomik (tidak ada multivalue/repeating groups).\n- 2NF: Memenuhi 1NF dan setiap kolom non-kunci bergantung penuh pada Primary Key (menghilangkan partial dependency).\n- 3NF: Memenuhi 2NF dan tidak ada ketergantungan transitif antar kolom non-kunci.', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Progres Materi (Ahmad selesai materi 1, 2, 3; Dewi materi 1; Rizky belum ada)
INSERT INTO `progres_materi` (`id_materi`, `id_pelajar`, `selesai_pada`) VALUES
(1, 5, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 5, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(3, 5, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 6, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Catatan Pribadi Pelajar
INSERT INTO `catatan_pribadi` (`id_materi`, `id_pelajar`, `isi_catatan`) VALUES
(1, 5, 'Kunci keamanan PHP: Selalu gunakan PDO Prepared Statements dan aktifkan PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION untuk menangani error dengan bersih.'),
(1, 6, 'Perlu mendalami perbedaan bindParam() vs execute([$param]).');

-- Forum Diskusi Materi
INSERT INTO `forum_materi` (`id_forum`, `id_materi`, `id_user`, `isi_komentar`, `created_at`) VALUES
(1, 1, 5, 'Izin bertanya Pak Tutor, bagaimana strategi terbaik menangani sanitasi XSS ketika menampilkan kembali data teks ke halaman HTML?', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 1, 2, 'Pertanyaan yang sangat bagus, Ahmad! Gunakan fungsi htmlspecialchars($str, ENT_QUOTES, "UTF-8") saat mencetak output ke browser. Jangan pernah mencetak raw input pengguna secara langsung.', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Tugas
INSERT INTO `tugas` (`id_tugas`, `id_kelas`, `judul`, `petunjuk`, `tenggat_waktu`, `created_at`) VALUES
(1, 1, 'Tugas 01 — Autentikasi & Prepared Statements', 'Bangun modul autentikasi (login, register, logout) menggunakan PHP Native dan PDO Prepared Statements. Sertakan validasi input dan penanganan password hashing bcrypt.', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 1, 'Tugas 02 — Desain Ruang Belajar & Gamifikasi', 'Rancang halaman antarmuka gamifikasi dengan Tailwind CSS dan logika perhitungan level berdasarkan perolehan XP. Unggah screenshot dan berkas zip kode.', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 'Tugas 01 — Perancangan Skema Database Normal 3NF', 'Buatlah file database.sql yang memuat relasi skema perpustakaan digital lengkap dengan Foreign Key ON DELETE CASCADE.', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Pengumpulan Tugas
-- Pelajar 5 (Ahmad): Kumpul tepat waktu Tugas 1 (nilai 95), Kumpul Tugas 2 (belum dinilai)
-- Pelajar 6 (Dewi): Kumpul tepat waktu Tugas 1 (nilai 80)
-- Pelajar 7 (Rizky): Kumpul TERLAMBAT Tugas 1 (nilai 55) -> Trigger Early Warning!
INSERT INTO `pengumpulan_tugas` (`id_kumpul`, `id_tugas`, `id_pelajar`, `jawaban_teks`, `file_jawaban`, `waktu_kumpul`, `status`, `nilai`, `komentar_pengajar`) VALUES
(1, 1, 5, 'Berikut tautan repository dan penjelasan arsitektur PDO yang saya terapkan. Sistem telah dilengkapi sanitasi lengkap dan CSRF token.', NULL, DATE_SUB(NOW(), INTERVAL 4 DAY), 'tepat_waktu', 95, 'Implementasi sangat rapi, struktur kode terstandar, dan dokumentasi lengkap. Pertahankan!'),
(2, 1, 6, 'Tugas autentikasi telah selesai dikerjakan sesuai spesifikasi dan telah diuji pada browser lokal.', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), 'tepat_waktu', 80, 'Bagus dan berfungsi normal. Perhatikan konsistensi penamaan variabel.'),
(3, 1, 7, 'Maaf pak baru sempat submit tugas karena ada kendala laptop.', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), 'terlambat', 55, 'Tugas terlambat dikumpulkan dan validasi sanitasi input masih belum lengkap. Mohon perbaiki.'),
(4, 2, 5, 'Rancangan gamifikasi telah selesai dibuat dengan Tailwind CSS dan integrasi XP bar interaktif.', NULL, NOW(), 'tepat_waktu', NULL, NULL);

-- Kuis
INSERT INTO `kuis` (`id_kuis`, `id_kelas`, `judul`, `durasi_menit`, `maks_percobaan`, `tenggat_waktu`, `created_at`) VALUES
(1, 1, 'Kuis 01 — Pemrograman Web & PHP 8 Native', 15, 2, DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 2, 'Kuis 01 — Dasar Perancangan Basis Data Relasional', 10, 2, DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Soal Kuis (Kuis 1)
INSERT INTO `soal_kuis` (`id_soal`, `id_kuis`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`) VALUES
(1, 1, 'Fitur PHP 8 yang memungkinkan penulisan tipe data gabungan (misalnya int|float) disebut...', 'Named Arguments', 'Union Types', 'Match Expression', 'Nullsafe Operator', 'b'),
(2, 1, 'Fungsi PHP standar yang paling aman dan direkomendasikan untuk mengenkripsi password dengan algoritma bcrypt adalah...', 'md5()', 'sha1()', 'password_hash()', 'crypt()', 'c'),
(3, 1, 'Konfigurasi PDO untuk melempar eksepsi secara otomatis ketika terjadi kegagalan query SQL adalah...', 'PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION', 'PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT', 'PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC', 'PDO::ATTR_AUTOCOMMIT => false', 'a'),
(4, 1, 'Fungsi PHP untuk memulai atau melanjutkan session adalah...', 'session_open()', 'session_start()', 'start_session()', 'session_begin()', 'b'),
(5, 1, 'Class Tailwind CSS manakah yang digunakan untuk membuat sudut kartu berbentuk melengkung halus?', 'rounded-none', 'rounded-xl', 'border-radius-10', 'corner-smooth', 'b');

-- Soal Kuis (Kuis 2)
INSERT INTO `soal_kuis` (`id_soal`, `id_kuis`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`) VALUES
(6, 2, 'Syarat utama sebuah tabel memenuhi Bentuk Normal Pertama (1NF) adalah...', 'Semua kolom bernilai tunggal (atomik)', 'Tidak memiliki Primary Key', 'Memiliki minimal dua Foreign Key', 'Semua kolom non-kunci bergantung parsial', 'a'),
(7, 2, 'Perintah SQL DDL yang digunakan untuk mengubah struktur tabel yang sudah ada adalah...', 'UPDATE', 'MODIFY', 'ALTER TABLE', 'CHANGE', 'c');

-- Hasil Kuis
INSERT INTO `hasil_kuis` (`id_hasil`, `id_kuis`, `id_pelajar`, `percobaan_ke`, `skor`, `waktu_selesai`) VALUES
(1, 1, 5, 1, 100, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 6, 1, 80, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 1, 7, 1, 40, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Booking Tutor
INSERT INTO `booking_tutor` (`id_booking`, `id_mahasiswa`, `id_tutor`, `id_kelas`, `tanggal_sesi`, `jam_sesi`, `link_meeting`, `catatan`, `status`, `rating`, `ulasan`) VALUES
(1, 5, 2, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'https://meet.google.com/qsy-tutor-sesi1', 'Diskusi optimalisasi query relasional dan penanganan event gamifikasi.', 'disetujui', NULL, NULL),
(2, 6, 2, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'https://meet.google.com/qsy-tutor-sesi2', 'Konsultasi implementasi komponen UI/UX dan alur navigasi aplikasi.', 'selesai', 5, 'Bimbingan sangat membantu! Penjelasan konsep sangat terstruktur dan mudah diaplikasikan ke kode.'),
(3, 5, 3, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:30:00', NULL, 'Review perancangan tabel 3NF dan indeks pencarian cepat.', 'pending', NULL, NULL);

-- Sesi Pomodoro
INSERT INTO `sesi_pomodoro` (`id_sesi`, `id_pelajar`, `durasi_menit`, `tanggal`, `created_at`) VALUES
(1, 5, 25, CURDATE(), NOW()),
(2, 5, 25, CURDATE(), NOW()),
(3, 5, 25, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NOW()),
(4, 5, 25, DATE_SUB(CURDATE(), INTERVAL 2 DAY), NOW()),
(5, 6, 25, CURDATE(), NOW()),
(6, 6, 25, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NOW());

-- Flashcards
INSERT INTO `flashcards` (`id_card`, `id_pelajar`, `pertanyaan`, `jawaban`, `status_hafal`) VALUES
(1, 5, 'Apa fungsi utama dari PDO Prepared Statements?', 'Mencegah SQL Injection dengan memisahkan kode query dan data input pengguna.', 'hafal'),
(2, 5, 'Apa fungsi session_start() di PHP?', 'Memulai atau melanjutkan session agar data pengguna bisa dipakai di banyak halaman.', 'hafal'),
(3, 5, 'Apa perbedaan klausa WHERE dan HAVING di SQL?', 'WHERE menyaring baris sebelum pengelompokan (GROUP BY), sedangkan HAVING menyaring data hasil fungsi agregasi.', 'belum'),
(4, 6, 'Bagaimana cara mencegah celah Cross-Site Scripting (XSS)?', 'Lakukan sanitasi dan encoding seluruh output data yang bersumber dari user dengan htmlspecialchars().', 'hafal');

-- Planner Belajar
INSERT INTO `planner` (`id_plan`, `id_pelajar`, `judul_target`, `tanggal_target`, `status`) VALUES
(1, 5, 'Pelajari dokumentasi Tailwind CDN dan flexbox layout', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'belum'),
(2, 5, 'Selesaikan latihan soal kuis Web Development', CURDATE(), 'selesai'),
(3, 6, 'Eksplorasi palet warna dan tipografi sistem desain Questudy', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'belum'),
(4, 7, 'Pelajari materi dasar PHP dan siapkan tugas susulan', CURDATE(), 'belum');

-- Tindak Lanjut Early Warning
INSERT INTO `tindak_lanjut` (`id_tl`, `id_kelas`, `id_pengajar`, `id_pelajar`, `catatan_tindak_lanjut`, `created_at`) VALUES
(1, 1, 2, 7, 'Siswa telah dihubungi melalui pesan pengingat untuk sesi mentoring privat dan asistensi remedial tugas.', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ========================================================
-- DATA TAMBAHAN: profil tutor, kategori kelas, kuota, pengaturan
-- ========================================================
UPDATE `users` SET `keahlian`='Pemrograman Web, Basis Data', `deskripsi_tutor`='Pengembang web dengan pengalaman mengajar pemula. Fokus pada PHP, MySQL, dan dasar pemrograman.', `portofolio_url`='https://contoh.dev/budi' WHERE `id_user`=2;
UPDATE `users` SET `keahlian`='Desain UI/UX, Desain Grafis', `deskripsi_tutor`='Desainer antarmuka yang suka membimbing pemula membuat desain rapi dan mudah dipakai.', `portofolio_url`='https://contoh.dev/siti' WHERE `id_user`=3;
UPDATE `users` SET `keahlian`='Cloud Computing, DevOps', `deskripsi_tutor`='Praktisi infrastruktur cloud yang ingin berbagi dasar-dasar deployment aplikasi.', `portofolio_url`='https://contoh.dev/reza' WHERE `id_user`=4;

UPDATE `kelas` SET `kategori`='Teknologi', `tingkat`='pemula', `kuota`=20 WHERE `id_kelas`=1;
UPDATE `kelas` SET `kategori`='Teknologi', `tingkat`='menengah', `kuota`=20 WHERE `id_kelas`=2;

INSERT INTO `kelas` (`id_kelas`, `id_pengajar`, `kode_kelas`, `nama_kelas`, `deskripsi`, `kategori`, `tingkat`, `kuota`) VALUES
(3, 3, 'DG2026', 'Desain Grafis untuk Pemula', 'Belajar dasar komposisi, warna, dan tipografi, lalu membuat poster dan media sosial sederhana.', 'Desain', 'pemula', 15);

INSERT INTO `materi` (`id_kelas`, `judul`, `tipe`, `konten_teks`, `urutan`) VALUES
(3, 'Dasar Komposisi dan Hierarki Visual', 'teks', 'Komposisi yang baik membuat mata pembaca mengikuti urutan informasi: judul, subjudul, isi, lalu ajakan bertindak. Latih dengan satu poster sederhana.', 1),
(3, 'Memilih Warna dan Font', 'teks', 'Gunakan maksimal dua font dan tiga warna utama. Pastikan kontras teks dan latar cukup agar mudah dibaca.', 2);

INSERT INTO `pengumuman` (`id_kelas`, `judul`, `isi`) VALUES
(3, 'Selamat datang di kelas Desain Grafis', 'Siapkan laptop atau HP, dan pilih satu topik poster yang ingin kamu buat selama kelas berlangsung.');

INSERT INTO `pengaturan_sistem` (`kunci`, `nilai`, `keterangan`) VALUES
('kuota_kelas_default', 20, 'Kuota peserta bawaan saat tutor membuat kelas'),
('maks_kelas_tutor', 3, 'Maksimal kelas yang dimiliki satu tutor'),
('maks_kelas_pelajar', 5, 'Maksimal kelas yang diikuti satu pelajar'),
('maks_booking_mingguan', 10, 'Maksimal sesi booking per tutor dalam satu minggu'),
('maks_booking_pending', 2, 'Maksimal booking berstatus menunggu per pelajar'),
('batas_batal_jam', 2, 'Batas pembatalan booking (jam sebelum sesi)');

INSERT INTO `laporan` (`id_pelapor`, `id_terlapor`, `jenis`, `alasan`, `status`) VALUES
(6, 7, 'pelajar', 'Komentar di forum materi kurang sopan dan mengganggu diskusi.', 'baru');
