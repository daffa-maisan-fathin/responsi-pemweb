# Questudy — Platform Belajar Skill dengan Gamifikasi, Ruang Belajar Pribadi, dan Peringatan Dini

> **Tema SDGs 4: Quality Education** (pendidikan berkualitas, inklusif, dan setara)
> **Responsi 2 Praktikum Pemrograman Web I** — Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman (2026)

---

## Daftar Isi

1. [Nama dan Deskripsi Proyek](#1-nama-dan-deskripsi-proyek)
2. [Latar Belakang dan Tujuan Proyek](#2-latar-belakang-dan-tujuan-proyek)
3. [Daftar Modul dan Fitur](#3-daftar-modul-dan-fitur)
4. [Teknologi yang Digunakan](#4-teknologi-yang-digunakan)
5. [Panduan Instalasi dan Konfigurasi](#5-panduan-instalasi-dan-konfigurasi)
6. [Panduan Menjalankan Aplikasi](#6-panduan-menjalankan-aplikasi)
7. [Informasi Pendukung](#7-informasi-pendukung)

---

## 1. Nama dan Deskripsi Proyek

**Nama proyek:** Questudy

**Questudy** adalah platform belajar *(Learning Management System)* untuk pengembangan skill. Tutor membuka kelas keterampilan, pelajar bergabung dan belajar, dan seluruh aktivitas belajar dihubungkan ke tiga pilar utama:

1. **Gamifikasi.** Aktivitas belajar menghasilkan *Experience Points* (XP), menaikkan level, membuka lencana, dan masuk ke papan peringkat.
2. **Ruang Belajar Pribadi.** Pomodoro timer, flashcard, planner target, dan statistik belajar mingguan.
3. **Peringatan Dini (Early Warning System).** Sistem membaca tugas terlambat, rata-rata nilai, dan lama tidak login untuk menandai pelajar yang perlu perhatian, lalu tutor mencatat tindak lanjutnya.

Aplikasi memiliki **11 modul fitur** dan dibangun dengan **PHP native** tanpa framework, database **MySQL** lewat **PDO**, dan tampilan **Tailwind CSS**.

### Tim Pengembang

| No | Nama | NIM | Peran Utama |
|:---:|---|:---:|---|
| 1 | **Daffa Maisan Fathin** | H1D025054 | **Backend Developer**: arsitektur database MySQL, logika bisnis PHP, integrasi gamifikasi, keamanan dan autentikasi |
| 2 | **Sari Nur Aini** | H1D025082 | **Frontend Developer**: antarmuka web, maskot paus dan Web Audio API, integrasi Tailwind CSS dan JavaScript |
| 3 | **Fattati Yumna Nurjanah** | H1D025162 | **UI/UX Designer**: desain antarmuka, wireframe, konsistensi visual, aset grafis dan media showcase |

---

## 2. Latar Belakang dan Tujuan Proyek

### Latar Belakang
Belajar keterampilan baru secara mandiri sering terhenti di tengah jalan. Beberapa masalah yang diangkat proyek ini:

- **Motivasi belajar mudah turun.** Tanpa tantangan, pengakuan, dan target yang jelas, pelajar kehilangan semangat sebelum materi selesai.
- **Belajar mandiri tidak terpantau.** Tutor sulit mengetahui pelajar mana yang mulai tertinggal sampai nilainya sudah jatuh atau pelajar berhenti mengikuti kelas.
- **Alat belajar tersebar.** Materi, tugas, kuis, catatan, timer fokus, dan jadwal belajar biasanya ada di aplikasi yang berbeda-beda.
- **Akses ke tutor tidak merata.** Pelajar kesulitan menemukan tutor yang sesuai dan mengatur sesi bimbingan secara tertib.

### Tujuan
1. Menyediakan satu platform yang menghubungkan tutor dan pelajar, mencakup kelas, materi, tugas, kuis, dan penilaian.
2. Meningkatkan motivasi belajar melalui gamifikasi: XP, level, lencana, streak, dan papan peringkat.
3. Membantu pelajar belajar mandiri lewat ruang belajar pribadi berisi Pomodoro, flashcard, planner, dan statistik.
4. Membantu tutor mendeteksi pelajar yang berisiko tertinggal sedini mungkin lewat sistem peringatan dini dan mencatat tindak lanjutnya.
5. Menerapkan materi Praktikum Pemrograman Web I (HTML, CSS, JavaScript, PHP, CRUD, dan Session) dalam aplikasi web yang utuh dan terintegrasi dengan 11 modul fitur.

### Manfaat
- **Pelajar:** belajar lebih terarah, termotivasi, dan terpantau perkembangannya.
- **Tutor:** pengelolaan kelas lebih mudah dan mengetahui siapa yang perlu didampingi.
- **Admin:** dapat memverifikasi tutor, menjaga kualitas platform, dan mengatur aturan sistem tanpa mengubah kode.

### Keterkaitan dengan SDGs
Proyek ini mendukung **SDGs 4: Quality Education**, yaitu menjamin pendidikan yang berkualitas, inklusif, dan setara serta mendorong kesempatan belajar sepanjang hayat. Questudy membuka akses belajar skill lewat kelas dan tutor, menjaga kualitas belajar lewat penilaian dan pendampingan, serta memperkecil risiko pelajar berhenti di tengah jalan lewat peringatan dini.

---

## 3. Daftar Modul dan Fitur

Questudy disusun dari **dua kelompok modul**:

| Kelompok | Jumlah | Fungsi |
|---|:---:|---|
| **A. Modul Praktikum (Asprak)** | 7 | Materi Praktikum Pemrograman Web I (HTML, HTML Lanjut, CSS, JavaScript, PHP, PHP CRUD, PHP Session) yang diterapkan sebagai dasar teknis aplikasi |
| **B. Modul Fitur Aplikasi** | 11 | Pembagian fitur Questudy berdasarkan fungsi bisnis; saling terhubung lewat database dan fungsi `tambahXP()` |

Jumlah modul fitur (11) memenuhi ketentuan minimal 9 modul.

### A. Modul Praktikum (Asprak)

Setiap modul praktikum diterapkan langsung pada kode Questudy. Kolom lokasi menunjukkan berkas tempat penerapannya.

#### Modul Praktikum 1 — HTML

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| Struktur dasar dokumen (`DOCTYPE`, `html`, `head`, `title`, `body`) | Setiap halaman memiliki struktur dasar HTML; bagian head dan penutup dipakai ulang lewat berkas layout | `index.php`, `includes/layout_header.php`, `includes/layout_footer.php` |
| Heading `<h1>`–`<h6>` | Judul halaman, judul kartu, dan subjudul | seluruh halaman |
| Gambar `<img>` (`src`, `alt`) | Foto profil pengguna pada profil, leaderboard, daftar pengguna, dan kartu tutor | `profil.php`, `pelajar/gamifikasi.php`, `admin/pengguna.php` |
| Baris baru `<br>` | Pemisah baris pada beranda, halaman masuk, dan halaman daftar | `index.php`, `auth/login.php`, `auth/register.php` |

#### Modul Praktikum 2 — HTML Lanjut

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| Layout semantik (`header`, `nav`, `main`, `section`, `aside`, `footer`) | Beranda memakai header, nav, section, dan footer; halaman aplikasi memakai nav, aside (sidebar), main, dan footer | `index.php`, `includes/layout_header.php`, `includes/layout_footer.php`, `auth/login.php`, `auth/register.php` |
| Hyperlink (`<a href>`) | Menu antar halaman; tautan anchor `#beranda` dan `#fitur` pada beranda; `target="_blank"` untuk tautan materi eksternal dan tautan Google Meet | `index.php`, `pelajar/materi.php`, `pengajar/materi.php`, `pelajar/booking_tutor.php`, `pengajar/booking.php` |
| Form: `name`, `method`, `action` | Method `POST` untuk menyimpan data dan `GET` untuk filter; `enctype="multipart/form-data"` untuk unggah berkas | `auth/register.php`, `profil.php`, `pelajar/tugas.php`, `pengajar/materi.php` |
| Jenis input | `text`, `email`, `password`, `number`, `date`, `radio` (pilih peran dan jawaban kuis), `checkbox`, `file`, `url`, `hidden`, `submit`, serta `select` dan `textarea` | `auth/register.php`, `pelajar/kuis.php`, `pelajar/ruang_belajar.php`, `pelajar/booking_tutor.php` |
| Atribut form | `required` dan `min` untuk validasi dasar; `<label for>` pada halaman pengaturan | `auth/register.php`, `admin/pengaturan.php` |
| Tabel (`table`, `tr`, `th`, `td`) | Daftar pengguna, hasil kuis, daftar kelas tutor, dan rapor kelas | `admin/pengguna.php`, `pelajar/kuis.php`, `pengajar/kuis.php`, `pengajar/kelas.php`, `pengajar/rapor.php` |

#### Modul Praktikum 3 — CSS

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| Inline style | Atribut `style="..."` untuk nilai dinamis seperti lebar bar progres dan tinggi grafik | `pelajar/dashboard.php`, `admin/dashboard.php`, `pelajar/gamifikasi.php`, `pelajar/katalog.php` |
| Internal style | Blok `<style>` untuk gaya khusus halaman: animasi, kartu, tombol gradasi, kolom isian | `index.php`, `auth/login.php`, `auth/register.php`, `pelajar/rapor.php`, `pelajar/sertifikat.php`, `includes/layout_header.php`, `includes/maskot_paus.php` |
| External style | `<link rel="stylesheet">` ke Font Awesome dan Google Fonts; Tailwind CSS dimuat dari CDN. Aplikasi tidak memakai berkas `.css` lokal | `includes/layout_header.php`, `index.php`, `auth/login.php` |
| Selector | Class (`.field`, `.btn-grad`), id (`#mainSidebar`), group selector (`.animate-pulse, .animate-bounce`), dan pseudo-class (`:hover`, `:focus`, `:nth-child`) | `includes/layout_header.php`, `auth/login.php`, `includes/maskot_paus.php` |
| Properti | `background-image` (gradasi), `box-shadow`, `border`, `padding`, `margin`, `transform`, `transition`, `@keyframes` untuk animasi, dan `@media` untuk tampilan responsif serta preferensi kurangi gerakan | `includes/layout_header.php`, `index.php` |

#### Modul Praktikum 4 — JavaScript

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| Penempatan script | Internal `<script>` di akhir `<body>`; eksternal `<script src>` dari CDN (Tailwind CSS dan canvas-confetti) | `auth/login.php`, `pelajar/kuis.php`, `includes/layout_footer.php` |
| Variabel (`let`, `const`) | Dipakai untuk elemen DOM, penghitung waktu, dan data sementara | `pelajar/kuis.php`, `pelajar/ruang_belajar.php`, `pelajar/katalog.php` |
| Perulangan | `for` untuk membangkitkan suara air; `forEach` untuk menelusuri banyak elemen | `pelajar/ruang_belajar.php`, `includes/layout_footer.php` |
| Fungsi | Fungsi biasa dan arrow function, contoh `flipCard()` dan `filterKartuRealtime()` | `pelajar/ruang_belajar.php`, `pelajar/katalog.php` |
| DOM | `getElementById`, `querySelector`, `querySelectorAll`, `classList`, `getAttribute` | `auth/login.php`, `pelajar/katalog.php`, `includes/layout_footer.php` |
| Event | `onclick`, `onsubmit`, `onchange`, dan `addEventListener('click')` | `pelajar/rating_ulasan.php`, `pelajar/kuis.php`, `auth/login.php`, `pengajar/tugas.php` |
| Dialog | `confirm()` sebelum menghapus atau membatalkan; `alert()` saat sesi Pomodoro selesai dan saat waktu kuis habis | `pengajar/kuis.php`, `pelajar/ruang_belajar.php`, `pelajar/kuis.php` |
| Timer | `setInterval` untuk hitung mundur kuis dan Pomodoro; `setTimeout` untuk jadwal tetesan suara air dan jeda efek perayaan | `pelajar/kuis.php`, `pelajar/ruang_belajar.php` |
| Interaksi | Pencarian katalog realtime, flip flashcard 3D, rating bintang, tombol lihat/sembunyi sandi, sidebar mobile, animasi angka, efek confetti | `pelajar/katalog.php`, `pelajar/ruang_belajar.php`, `pelajar/rating_ulasan.php`, `auth/login.php`, `includes/layout_footer.php` |

#### Modul Praktikum 5 — PHP

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| Tag PHP, variabel, `echo` | Seluruh halaman memakai `<?php ... ?>` dan `<?= ... ?>` untuk menampilkan data dinamis | seluruh halaman |
| Tipe data dan operator | Perhitungan level `floor(xp / 100) + 1`, persentase kuota kelas, rata-rata nilai rapor dengan `round()`, dan nilai akhir | `config/koneksi.php`, `pelajar/katalog.php`, `pelajar/rapor.php` |
| `if` / `elseif` / `else` | Validasi input, penentuan status tugas, dan konversi nilai ke huruf mutu A–E | `auth/register.php`, `pelajar/tugas.php`, `pelajar/rapor.php` |
| `foreach` | Menampilkan daftar kelas, materi, tugas, pengguna, dan leaderboard | `pelajar/kelas.php`, `pelajar/gamifikasi.php`, `admin/pengguna.php` |
| `for` | Grafik 7 hari terakhir, kalender planner, dan bintang rating | `admin/dashboard.php`, `pelajar/dashboard.php`, `pelajar/rating_ulasan.php` |
| Array | Indexed (`daftarKategori()`), asosiatif (hasil query dan `$_SESSION`), dan multidimensi (peta status booking berisi warna latar dan warna teks) | `config/koneksi.php`, `pelajar/booking_tutor.php`, `pengajar/booking.php` |
| `include` / `require` | `require_once` untuk koneksi database dan layout header serta footer di setiap halaman | seluruh halaman |
| Function | Fungsi berparameter dan berpengembalian nilai: `e()`, `setFlash()`, `getFlash()`, `cekRole()`, `tambahXP()`, `getPengaturan()`, `gabungKelas()`, `daftarKategori()`; fungsi tampilan `maskotPaus()`, `keadaanKosong()`, `latarLaut()` | `config/koneksi.php`, `includes/maskot_paus.php` |

#### Modul Praktikum 6 — PHP CRUD

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| Variabel dan tipe data (boolean, integer, float, string, array) | Status benar/salah, XP dan level (integer), rata-rata nilai dengan `round()` (float), teks pesan (string), dan array hasil query | `config/koneksi.php`, `pelajar/rapor.php` |
| Operator (aritmatika, string, increment, logika, perbandingan) | Aritmatika untuk level dan persentase kuota; operator string `.` dan `.=` untuk menyusun pesan; `++` untuk menghitung lencana; `&&`, `\|\|`, `!` untuk syarat; `===` dan `!==` untuk perbandingan ketat | `pelajar/tugas.php`, `profil.php`, `config/koneksi.php` |
| Struktur kontrol (`if`/`else`, `foreach`, `for`, `do…while`, `break`) | `do…while` membuat kode kelas acak sampai tidak bentrok dengan kode lain; `break` menghentikan pencarian saat data aktif ditemukan | `pengajar/kelas.php`, `pelajar/tugas.php`, `pelajar/materi.php` |
| Percobaan 1: database dan tabel | `CREATE DATABASE` dan `CREATE TABLE` dengan primary key, `AUTO_INCREMENT`, dan *foreign key* `ON DELETE CASCADE` untuk 24 tabel | `database.sql`, `database_production_lokal.sql` |
| Percobaan 2: koneksi database | Modul memakai `mysqli_connect`; Questudy memakai **PDO** dengan *prepared statements* dan penanganan error | `config/koneksi.php` |
| Percobaan 3: CREATE | Form `POST`, cek `isset($_POST[...])`, `INSERT`, lalu `header("Location: ...")`; dipakai untuk tambah kelas, materi, tugas, kuis, soal, pengumuman, flashcard, booking, laporan, dan pendaftaran akun | `pengajar/kelas.php`, `pengajar/materi.php`, `auth/register.php` |
| Percobaan 4: READ | `SELECT` lalu `foreach` untuk menampilkan data dalam tabel dan kartu; diperluas dengan `JOIN`, `GROUP BY`, dan pengurutan | `admin/pengguna.php`, `pelajar/gamifikasi.php`, `pengajar/rapor.php` |
| Percobaan 5: UPDATE | Form ubah data yang membawa id lewat input `hidden`, lalu `UPDATE`; dipakai untuk kelas, profil, status akun, nilai tugas, dan status booking | `pengajar/kelas.php`, `profil.php`, `admin/pengguna.php`, `pengajar/tugas.php` |
| Percobaan 6: DELETE | Hapus data berdasarkan id. Questudy memakai form `POST` dengan konfirmasi `confirm()`, bukan tautan `GET` | `pengajar/kelas.php`, `pengajar/kuis.php`, `pelajar/ruang_belajar.php`, `admin/pengguna.php` |
| Latihan: website berita (tambah, lihat, ubah, hapus post) | Pengelolaan kelas yang memiliki alur lengkap tambah, lihat, ubah, dan hapus | `pengajar/kelas.php` |
| Tugas: tiga tabel dan halaman index gabungan berdasarkan id yang sama | Relasi antar tabel dengan `JOIN`, misalnya rapor yang menggabungkan pengguna, kelas, tugas, dan hasil kuis, serta booking yang menggabungkan pelajar, tutor, dan kelas | `pengajar/rapor.php`, `pelajar/booking_tutor.php` |
| Pengembangan lanjutan: unggah berkas | `$_FILES` dan `move_uploaded_file` dengan batasan ekstensi dan ukuran | `profil.php`, `pelajar/tugas.php`, `pengajar/materi.php` |

#### Modul Praktikum 7 — PHP Session

| Materi | Penerapan di Questudy | Lokasi |
|---|---|---|
| `session_start()` | Dipanggil di berkas koneksi yang dimuat semua halaman | `config/koneksi.php` |
| `$_SESSION` | Menyimpan `id_user`, `nama`, `email`, `role`, `foto_profil`, `xp`, `level`, `streak_harian`, dan `last_activity` | `auth/login.php`, `config/koneksi.php` |
| Registrasi pengguna | Validasi format email, panjang sandi minimal 6 karakter, kecocokan konfirmasi sandi, pemeriksaan email yang sudah terdaftar, lalu sandi disimpan dengan `password_hash` | `auth/register.php` |
| Proses autentikasi | Mencari email di database, mencocokkan sandi dengan `password_verify`, menolak akun berstatus pending atau nonaktif, lalu membuat session dan memanggil `session_regenerate_id(true)` | `auth/login.php` |
| Pengecekan session di setiap halaman | `cekRole()` mengalihkan ke halaman login bila session tidak ada, dan ke dashboard peran yang sesuai bila peran tidak berhak | `config/koneksi.php` |
| Logout | Mengosongkan `$_SESSION`, menghapus cookie sesi, dan `session_destroy()` | `auth/logout.php` |
| Session kedaluwarsa | Keluar otomatis setelah 30 menit tanpa aktivitas (`session_unset()` dan `session_destroy()`) | `config/koneksi.php` |

### B. Modul Fitur Aplikasi

#### Modul Fitur 1 — Autentikasi dan Multi-Role
- Registrasi mandiri: **Pelajar** langsung aktif, **Tutor** berstatus `pending` sampai disetujui admin.
- Login satu pintu, logout, dan pengalihan otomatis ke dashboard sesuai peran.
- Session menyimpan data pengguna; proteksi halaman per peran memakai `cekRole()`.
- Logout otomatis setelah **30 menit** tidak aktif.
- Kata sandi disimpan dengan `password_hash` dan diperiksa dengan `password_verify`.
- Pelajar mendapat bonus login harian (+10 XP) dan **streak** belajar.

Berkas: `auth/login.php`, `auth/register.php`, `auth/logout.php`, `config/koneksi.php`
Tabel: `users`

#### Modul Fitur 2 — Manajemen Pengguna dan Profil
- **Admin:** menyetujui atau menolak pendaftaran tutor, menambah pengguna, mengaktifkan atau menonaktifkan akun, mereset kata sandi, dan menghapus akun (akun sendiri tidak dapat dihapus).
- **Semua peran:** edit profil, unggah foto profil, dan ganti kata sandi.
- Tutor mengisi keahlian, deskripsi pengalaman (minimal 30 karakter), dan tautan portofolio saat mendaftar.

Berkas: `admin/pengguna.php`, `profil.php`
Tabel: `users`

#### Modul Fitur 3 — Manajemen Kelas
- **Tutor:** menambah, mengubah, dan menghapus kelas dengan **kode kelas unik 6 karakter**, kategori, tingkat (pemula, menengah, lanjut), dan kuota peserta; membuat pengumuman kelas.
- **Pelajar:** bergabung lewat kode kelas atau lewat **Katalog Kelas** (pencarian dan filter realtime, indikator kuota terisi); melihat kelas yang diikuti.
- Batas jumlah kelas per tutor dan per pelajar diatur admin.

Berkas: `pengajar/kelas.php`, `pelajar/kelas.php`, `pelajar/katalog.php`
Tabel: `kelas`, `anggota_kelas`, `pengumuman`

#### Modul Fitur 4 — Materi Pembelajaran
- **Tutor:** menambah dan menghapus materi dalam tiga tipe: **teks**, **berkas** (PDF, DOCX, PPTX, ZIP, gambar; maksimal 15 MB), dan **tautan**.
- **Pelajar:** membaca materi, mengunduh berkas, dan menandai selesai (**+20 XP**).
- **Catatan pribadi** per materi (simpan dan ubah) dan **forum tanya jawab** pada setiap materi; tutor dan admin dapat menghapus komentar yang tidak pantas.

Berkas: `pengajar/materi.php`, `pelajar/materi.php`
Tabel: `materi`, `progres_materi`, `catatan_pribadi`, `forum_materi`

#### Modul Fitur 5 — Tugas dan Pengumpulan
- **Tutor:** membuat dan menghapus tugas dengan tenggat, melihat pengumpulan, serta memberi nilai (0–100) dan komentar.
- **Pelajar:** mengumpulkan jawaban berupa teks atau berkas (PDF, ZIP, RAR, DOCX, PNG, JPG; maksimal 10 MB) dan melihat nilai.
- Status ditentukan otomatis: **tepat waktu** atau **terlambat**. Tepat waktu memberi **+50 XP**.

Berkas: `pengajar/tugas.php`, `pelajar/tugas.php`
Tabel: `tugas`, `pengumpulan_tugas`

#### Modul Fitur 6 — Kuis dan Penilaian
- **Tutor:** menambah dan menghapus kuis (durasi, batas percobaan, tenggat) serta bank soal pilihan ganda A–D.
- **Pelajar:** mengerjakan kuis dengan **timer hitung mundur dan kirim otomatis saat waktu habis**; skor dihitung otomatis; riwayat percobaan tersimpan. Skor sempurna memberi **+40 XP**, skor di bawah 100 memberi **+20 XP**.
- **Rapor otomatis:** rata-rata nilai tugas dan kuis, nilai akhir (bobot tugas dan kuis masing-masing 50%), dan konversi huruf mutu A–E, dapat dicetak.
- **Sertifikat** kelulusan dengan nomor seri unik, siap cetak atau simpan sebagai PDF.

Berkas: `pengajar/kuis.php`, `pelajar/kuis.php`, `pengajar/rapor.php`, `pelajar/rapor.php`, `pelajar/sertifikat.php`
Tabel: `kuis`, `soal_kuis`, `hasil_kuis`

#### Modul Fitur 7 — Gamifikasi
- XP dari aktivitas belajar; level dihitung dengan rumus `floor(XP / 100) + 1`.
- **5 lencana** terbuka otomatis saat XP mencapai syarat.
- **Streak** belajar harian dan **leaderboard** peringkat pelajar.
- Maskot paus dan efek perayaan (confetti) saat naik level atau mendapat lencana.

Berkas: `pelajar/gamifikasi.php`, fungsi `tambahXP()` di `config/koneksi.php`, `includes/maskot_paus.php`
Tabel: `gamifikasi_aturan`, `badges`, `user_badges`, `users` (kolom `xp`, `level`, `streak_harian`)

#### Modul Fitur 8 — Ruang Belajar Pribadi
Satu halaman dengan empat tab:
1. **Pomodoro:** fokus 25 menit dan istirahat 5 menit; sesi fokus yang selesai tersimpan dan memberi **+15 XP**; tersedia suara air dari Web Audio API.
2. **Flashcard:** buat dan hapus kartu, balik dengan efek 3D, tandai hafal atau belum.
3. **Planner:** agenda yang memuat tenggat tugas kelas dan checklist target pribadi (tambah, tandai selesai, hapus).
4. **Statistik:** total jam fokus, streak, dan grafik batang aktivitas mingguan.

Berkas: `pelajar/ruang_belajar.php`
Tabel: `sesi_pomodoro`, `flashcards`, `planner`

#### Modul Fitur 9 — Peringatan Dini dan Dashboard
- **Aturan risiko otomatis:** pelajar ditandai jika tugas terlambat melebihi batas, rata-rata nilai tugas di bawah batas, atau tidak login melebihi batas hari.
- **Tutor:** daftar "perlu perhatian" beserta alasannya dan formulir **catatan tindak lanjut**.
- **Dashboard per peran:** Pelajar (progres, XP, tenggat terdekat), Tutor (ringkasan kelas dan widget peringatan dini), Admin (statistik platform dan grafik aktivitas 7 hari terakhir dari data nyata).
- Pelajar tidak melihat label "berisiko"; label itu hanya tampil untuk tutor.

Berkas: `pengajar/pemantauan.php`, `pelajar/dashboard.php`, `pengajar/dashboard.php`, `admin/dashboard.php`
Tabel: `peringatan_dini_aturan`, `tindak_lanjut`

#### Modul Fitur 10 — Mentoring Tutor dan Ulasan
- **Pelajar:** mengajukan sesi mentoring privat ke tutor (tanggal, jam, catatan). Sistem menolak jadwal yang bentrok dan membatasi jumlah booking menunggu serta sesi per tutor per minggu.
- **Tutor:** menyetujui booking sambil mengisi **tautan Google Meet**, menolak atau membatalkan dengan alasan, dan menandai sesi selesai.
- **Pelajar:** membatalkan sesi sebelum batas waktu pembatalan.
- **Rating dan ulasan:** rating bintang 1–5 dan ulasan hanya setelah sesi selesai; pengiriman rating memberi **+30 XP**. Tutor dapat melihat rating yang diterima.

Berkas: `pelajar/booking_tutor.php`, `pengajar/booking.php`, `pelajar/rating_ulasan.php`
Tabel: `booking_tutor`

#### Modul Fitur 11 — Pelaporan, Moderasi, dan Pengaturan Sistem
- **Pelaporan:** pelajar dan tutor melaporkan pengguna, kelas, atau komentar beserta alasannya; admin memproses laporan, memberi peringatan, menangguhkan akun terlapor, atau mengabaikan laporan.
- **Moderasi:** admin menghapus materi dan komentar forum yang melanggar.
- **Aturan:** admin mengubah nilai XP tiap aktivitas dan ambang batas peringatan dini.
- **Pengaturan batas:** kuota kelas bawaan, maksimal kelas per tutor dan per pelajar, batas booking, dan batas pembatalan, tanpa mengubah kode.

Berkas: `laporan.php`, `admin/laporan.php`, `admin/aturan_moderasi.php`, `admin/pengaturan.php`
Tabel: `laporan`, `pengaturan_sistem`, `gamifikasi_aturan`, `peringatan_dini_aturan`

### Integrasi Antar Modul Fitur

| Aksi | Modul fitur terlibat | Dampak |
|---|---|---|
| Login | 1 → 7, 9 | Peran menentukan dashboard dan menu; bonus login harian menjaga streak dan waktu login terbaca peringatan dini |
| Tutor mendaftar | 1 → 2 | Status pending sampai admin menyetujui di menu Pengguna |
| Pelajar bergabung kelas | 3 → 4, 5, 6 | Materi, tugas, dan kuis kelas itu terbuka |
| Tutor membuat tugas | 5 → 8 | Tenggat muncul otomatis di planner pelajar |
| Pelajar selesai membaca materi | 4 → 7 | Mendapat XP dan progres naik |
| Pelajar mengumpulkan tugas | 5 → 6, 7, 9 | Masuk rapor, mendapat XP bila tepat waktu, tercatat untuk peringatan dini |
| Pelajar menyelesaikan kuis | 6 → 7 | Skor masuk rapor dan mendapat XP |
| Pelajar menyelesaikan Pomodoro | 8 → 7 | Mendapat XP dan jam fokus bertambah |
| Tugas terlambat, nilai tugas rendah, atau lama tidak login | 1, 5 → 9 | Pelajar masuk daftar perlu perhatian milik tutor |
| Mentoring selesai dan rating dikirim | 10 → 7 | Mendapat XP; tutor melihat rating |
| Pengguna melapor, admin menangguhkan akun | 11 → 2, 1 | Akun nonaktif tidak dapat login |
| Admin mengubah aturan XP atau ambang batas | 11 → 7, 9 | Perhitungan XP dan peringatan dini langsung memakai nilai baru |
| Naik level atau dapat lencana | 7 → semua halaman | Tampil di header, profil, dan kotak perayaan |

Pusat penghitungan XP adalah fungsi `tambahXP()`. Semua modul memanggil fungsi yang sama, sehingga XP, level, lencana, dan leaderboard selalu konsisten.

### Peran dan Hak Akses

| Kemampuan | Admin | Tutor | Pelajar |
|---|:---:|:---:|:---:|
| Kelola semua pengguna dan verifikasi tutor | ✓ | | |
| Mengatur XP, ambang peringatan dini, dan batas sistem | ✓ | | |
| Meninjau laporan pengguna dan memoderasi konten | ✓ | | |
| Membuat kelas, materi, tugas, dan kuis | | ✓ | |
| Menilai tugas dan melihat rapor kelas | | ✓ | |
| Memantau pelajar berisiko dan mencatat tindak lanjut | | ✓ | |
| Menyetujui booking mentoring | | ✓ | |
| Bergabung kelas, belajar, mengumpulkan tugas, dan mengerjakan kuis | | | ✓ |
| Ruang belajar pribadi, XP, lencana, leaderboard | | | ✓ |
| Booking mentoring dan memberi rating | | | ✓ |
| Edit profil dan ganti kata sandi | ✓ | ✓ | ✓ |
| Melapor | | ✓ | ✓ |

Satu akun hanya memiliki satu peran. Jika seseorang ingin dua peran, ia memakai dua email berbeda.

### Aturan Gamifikasi, Peringatan Dini, dan Batas Sistem

Nilai bawaan setelah instalasi (dapat diubah admin).

**XP per aktivitas**

| Aktivitas | XP |
|---|:---:|
| Login harian pelajar (menjaga streak) | +10 |
| Menyelesaikan materi, atau kuis dengan skor di bawah 100 | +20 |
| Mengumpulkan tugas tepat waktu | +50 |
| Kuis dengan skor sempurna | +40 |
| Sesi Pomodoro 25 menit | +15 |
| Mengirim rating setelah sesi mentoring selesai | +30 |

**Lencana**

| Lencana | Syarat XP |
|---|:---:|
| 🌱 Pionir Belajar | 50 |
| 📖 Pembelajar Rajin | 150 |
| 🏆 Master Kuis | 300 |
| 🔥 Pendekar Fokus | 500 |
| 👑 Cendekiawan Questudy | 1000 |

**Ambang peringatan dini:** tugas terlambat lebih dari **2**, rata-rata nilai tugas di bawah **65**, atau tidak login lebih dari **5** hari.

**Batas sistem** (`admin/pengaturan.php`)

| Pengaturan | Bawaan |
|---|:---:|
| Kuota peserta kelas | 20 |
| Maksimal kelas per tutor | 3 |
| Maksimal kelas yang diikuti pelajar | 5 |
| Maksimal sesi booking per tutor per minggu | 10 |
| Maksimal booking menunggu per pelajar | 2 |
| Batas pembatalan booking (jam sebelum sesi) | 2 |

---

## 4. Teknologi yang Digunakan

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8+ native (tanpa framework) |
| Database | MySQL / MariaDB, koneksi **PDO** dengan *prepared statements* |
| Tampilan | **Tailwind CSS** (CDN), Font Awesome 6, font **Plus Jakarta Sans** |
| Skrip klien | JavaScript ES6 murni (timer kuis, Pomodoro, flashcard 3D, rating bintang, pencarian realtime, menu mobile) |
| Audio | Web Audio API untuk suara air, tanpa berkas audio |
| Pustaka CDN | Tailwind CSS, Font Awesome 6, Google Fonts, dan canvas-confetti |

Palet warna: Iced Denim `#5798D7`, Frost `#E6F1FA`, Pistachio `#B9C255`, Lemon Cream `#EDE986`, dan aksen Raspberry Rose `#F2619C`.

Tailwind dan Font Awesome dimuat dari CDN, sehingga halaman membutuhkan koneksi internet saat dibuka.

---

## 5. Panduan Instalasi dan Konfigurasi

### Prasyarat
- Web server dengan PHP 8 atau lebih baru dan MySQL/MariaDB (XAMPP atau Laragon)
- Peramban modern dan koneksi internet (Tailwind CSS dan Font Awesome dimuat dari CDN)
- Git (opsional, untuk mengunduh dari repository)

### Memilih Berkas Database

| Berkas | Isi | Dipakai untuk |
|---|---|---|
| `database.sql` | Struktur lengkap, `CREATE DATABASE`, `DROP TABLE`, dan **data contoh beserta akun demo** | Demo atau pengembangan di localhost |
| `database_production_lokal.sql` | Struktur, `CREATE DATABASE`, `USE`, `DROP TABLE`, data awal, dan 1 akun admin; **tanpa data demo** | Uji versi bersih di localhost |
| `database_production.sql` | Struktur, data awal, dan 1 akun admin; **tanpa** `CREATE DATABASE`, `USE`, dan `DROP` | Hosting (database kosong dari penyedia) |

### Instalasi di XAMPP
1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder proyek ke `C:\xampp\htdocs\` (contoh: `C:\xampp\htdocs\Questudy\responsi-pemweb`).
3. Buka `http://localhost/phpmyadmin`, lalu **Import** berkas SQL:
   - `database.sql` untuk demo dengan data contoh, atau
   - `database_production_lokal.sql` untuk versi bersih.
   Kedua berkas membuat database `db_questudy` secara otomatis.
4. Buka `http://localhost/Questudy/responsi-pemweb/` (sesuaikan dengan nama folder).

### Instalasi di Laragon
1. Klik **Start All**.
2. Salin folder proyek ke `C:\laragon\www\`.
3. Import berkas SQL lewat phpMyAdmin atau terminal:
   ```bash
   mysql -u root -p < database.sql
   ```
4. Buka `http://localhost/nama-folder` atau `http://nama-folder.test`.

### Konfigurasi Koneksi Database
Pengaturan database ada di `config/koneksi.php` (host, nama database, pengguna, dan kata sandi). Bawaan XAMPP/Laragon: pengguna `root`, kata sandi kosong, database `db_questudy`.

### Deploy ke Hosting

1. Buat database kosong di panel hosting, lalu **import `database_production.sql`** ke database itu.
2. Ubah `config/koneksi.php` sesuai data dari hosting (host, nama database, pengguna, kata sandi).
3. Unggah seluruh folder proyek ke `htdocs` atau `public_html`.
4. Pastikan folder `uploads/materi`, `uploads/profil`, dan `uploads/tugas` dapat ditulis oleh server.
5. Login sebagai admin (`admin@questudy.id`) dan **segera ganti kata sandi** di menu Profil.

Jangan mengunggah kredensial database hosting ke repositori publik.

---

## 6. Panduan Menjalankan Aplikasi

1. Pastikan Apache dan MySQL berjalan, dan database sudah diimpor (lihat bagian 5).
2. Buka aplikasi di peramban, misalnya `http://localhost/Questudy/responsi-pemweb/` (sesuaikan dengan nama folder). Halaman beranda akan tampil.
3. Klik **Masuk** atau **Daftar** untuk memulai.

### Akun dan Cara Login

| Peran | Cara mendapatkan akun |
|---|---|
| **Pelajar** | Daftar di `auth/register.php`, pilih peran Pelajar. Akun langsung aktif. |
| **Tutor** | Daftar di `auth/register.php`, pilih peran Tutor, isi keahlian, deskripsi, dan portofolio. Status awal `pending`; admin harus menyetujui di menu Pengguna. |
| **Admin** | Tidak bisa mendaftar lewat web. Akun dibuat dari berkas SQL (`admin@questudy.id`). Ganti kata sandi lewat menu Profil setelah login pertama. |

Jika memakai `database.sql` (data demo), tersedia tiga akun contoh dengan sandi `123456`: `admin@questudy.id`, `pengajar@questudy.id`, dan `pelajar@questudy.id`. Akun demo tidak ada di versi produksi.

### Alur Penggunaan Singkat

**Admin**
1. Login, buka menu **Pengguna**, lalu setujui pendaftaran tutor yang berstatus menunggu.
2. Atur XP, ambang peringatan dini, dan batas sistem di menu **Aturan** dan **Pengaturan**.
3. Tinjau laporan pengguna di menu **Laporan**.

**Tutor**
1. Daftar sebagai Tutor lalu tunggu persetujuan admin.
2. Buat kelas dan bagikan **kode kelas** kepada pelajar.
3. Tambahkan materi, tugas, dan kuis dengan bank soal.
4. Nilai pengumpulan tugas, lihat rapor kelas, dan pantau pelajar yang perlu perhatian di menu **Pemantauan**.

**Pelajar**
1. Daftar sebagai Pelajar (langsung aktif), lalu gabung kelas lewat **kode** atau **Katalog Kelas**.
2. Baca materi, kumpulkan tugas, dan kerjakan kuis untuk mengumpulkan XP.
3. Gunakan **Ruang Belajar** untuk Pomodoro, flashcard, dan planner.
4. Pantau level, lencana, dan peringkat di menu **Gamifikasi**; cetak rapor dan sertifikat bila tersedia.

---

## 7. Informasi Pendukung

### Database

Database `db_questudy` terdiri dari **24 tabel** berelasi dengan *foreign key*.

| Kelompok | Tabel |
|---|---|
| Pengguna | `users`, `laporan` |
| Kelas | `kelas`, `anggota_kelas`, `pengumuman` |
| Materi | `materi`, `progres_materi`, `catatan_pribadi`, `forum_materi` |
| Tugas dan kuis | `tugas`, `pengumpulan_tugas`, `kuis`, `soal_kuis`, `hasil_kuis` |
| Gamifikasi | `gamifikasi_aturan`, `badges`, `user_badges` |
| Ruang belajar | `sesi_pomodoro`, `flashcards`, `planner` |
| Peringatan dini | `peringatan_dini_aturan`, `tindak_lanjut` |
| Mentoring | `booking_tutor` |
| Pengaturan | `pengaturan_sistem` |

### Struktur Direktori

```text
├── admin/
│   ├── dashboard.php          # Statistik platform dan grafik aktivitas 7 hari
│   ├── pengguna.php           # Verifikasi tutor, tambah/nonaktifkan/reset/hapus akun
│   ├── aturan_moderasi.php    # Nilai XP dan ambang peringatan dini
│   ├── pengaturan.php         # Kuota dan batas sistem
│   └── laporan.php            # Tinjauan laporan pengguna
├── auth/
│   ├── login.php              # Login satu pintu untuk semua peran
│   ├── register.php           # Pendaftaran pelajar dan tutor (pending)
│   └── logout.php             # Logout dan penghancuran sesi
├── config/
│   └── koneksi.php            # PDO, timeout sesi, cekRole(), tambahXP(), fungsi bantu
├── includes/
│   ├── layout_header.php      # Navbar, pil XP/level, sidebar
│   ├── layout_footer.php      # Footer, drawer mobile, efek perayaan
│   └── maskot_paus.php        # Maskot paus SVG dan keadaan kosong
├── pelajar/
│   ├── dashboard.php          # Progres, streak, tenggat terdekat
│   ├── katalog.php            # Katalog kelas dengan pencarian realtime
│   ├── kelas.php              # Gabung kelas via kode
│   ├── materi.php             # Baca materi, catatan, forum
│   ├── tugas.php              # Kumpul tugas dan lihat nilai
│   ├── kuis.php               # Kuis dengan timer
│   ├── rapor.php              # Rapor dan huruf mutu
│   ├── sertifikat.php         # Sertifikat kelulusan
│   ├── gamifikasi.php         # XP, lencana, leaderboard
│   ├── ruang_belajar.php      # Pomodoro, flashcard, planner, statistik
│   ├── booking_tutor.php      # Pengajuan mentoring
│   └── rating_ulasan.php      # Rating dan ulasan
├── pengajar/
│   ├── dashboard.php          # Ringkasan kelas dan widget peringatan dini
│   ├── kelas.php              # CRUD kelas, anggota, pengumuman
│   ├── materi.php             # CRUD materi dan moderasi forum
│   ├── tugas.php              # CRUD tugas dan penilaian
│   ├── kuis.php               # CRUD kuis dan bank soal
│   ├── rapor.php              # Rapor kelas
│   ├── pemantauan.php         # Peringatan dini dan tindak lanjut
│   └── booking.php            # Persetujuan booking dan tautan Meet
├── uploads/                   # materi/, profil/, tugas/
├── database.sql               # Struktur + data demo (localhost)
├── database_production_lokal.sql  # Struktur + admin, tanpa demo (localhost)
├── database_production.sql    # Struktur + admin, untuk hosting
├── index.php                  # Beranda publik
├── profil.php                 # Edit profil, foto, ganti kata sandi
├── laporan.php                # Pelaporan pengguna
└── README.md
```

### Keamanan

1. **SQL Injection:** seluruh query dinamis memakai PDO *prepared statements*.
2. **XSS:** keluaran dari input pengguna dibungkus `e()` (`htmlspecialchars` dengan `ENT_QUOTES`, UTF-8).
3. **Kata sandi:** disimpan dengan Bcrypt (`password_hash` dan `password_verify`).
4. **Sesi:** timeout 30 menit tanpa aktivitas dan ID sesi diregenerasi saat login.
5. **Akses per peran:** setiap halaman memanggil `cekRole()`; akses tanpa izin dialihkan ke dashboard peran yang bersangkutan.
6. **Unggahan:** ekstensi dibatasi dan ukuran maksimal ditentukan (materi 15 MB, tugas 10 MB).
7. **Perbaikan sebelumnya:** celah login yang menerima sandi `123456` untuk akun mana pun sudah dihapus, dan bug pendaftaran akun (jumlah kolom dan nilai `INSERT` tidak sama) sudah diperbaiki.

### Tampilan dan Pengalaman Pengguna

- **Gaya cerah** dengan gradasi biru–ungu dan satu font di semua halaman; teks dibuat cukup besar dan animasi tidak berlebihan.
- **Maskot paus** beranimasi di beranda, dashboard, halaman Pomodoro (bereaksi saat fokus, jeda, dan istirahat), panel masuk dan daftar, kotak perayaan, serta tampilan kosong ("Belum ada kelas", "Belum ada tugas", dan sejenisnya).
- **Latar laut** di halaman masuk dan daftar, dengan ombak, gelembung, dan paus yang berenang.
- **Efek perayaan** berupa confetti saat naik level atau mendapat lencana.
- **Animasi angka dan grafik:** batang grafik, bar progres, dan angka naik perlahan; kartu muncul bertahap.
- **Responsif:** menu mobile, sidebar drawer, dan kartu contoh di beranda yang ikut tampil di layar HP.
- **Aksesibilitas gerak:** animasi otomatis mati bila perangkat memakai pengaturan "kurangi gerakan".
- **Beranda** memiliki bagian Fitur, Cara Kerja, dan FAQ.
