# Questudy — LMS Gamifikasi, Ruang Belajar Pribadi & Peringatan Dini
> **Tema Pembangunan Berkelanjutan (SDGs 4): Quality Education (Pendidikan Berkualitas, Inklusif, dan Setara)**  
> **Responsi 2 Praktikum Pemrograman Web I — Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman (2026)**

---

### Tim Pengembang (Kelompok)
| No | Nama Anggota | NIM | Peran Utama |
|:---:|---|:---:|---|
| **1** | **Daffa Maisan Fathin** | **H1D025054** | **Backend Developer** (Arsitektur Database MySQL, Logika Bisnis PHP, Integrasi Gamifikasi, Keamanan & Autentikasi) |
| **2** | **Sari Nur Aini** | **H1D025082** | **Frontend Developer** (Implementasi Antarmuka Web, Komponen Interaktif Maskot Paus & Web Audio API, Integrasi Tailwind CSS & JavaScript) |
| **3** | **Fattati Yumna Nurjanah** | **H1D025162** | **UI/UX Designer** (Perancangan Desain Antarmuka, Wireframing, Konsistensi Visual Pengguna, Aset Grafis & Media Showcase) |

---

## Tentang Proyek Questudy
**Questudy** adalah platform *Learning Management System* (LMS) modern dan inklusif yang dirancang untuk menjawab tantangan kesenjangan motivasi belajar dan angka putus studi pada pembelajaran. Mengacu pada target **SDGs 4 (Quality Education)**, Questudy mengintegrasikan tiga pilar inovasi:

1. **Mekanisme Gamifikasi RPG (Gamified Learning):** Mengubah proses belajar menjadi pengalaman yang memotivasi lewat akumulasi *Experience Points* (XP), kenaikan *Level*, papan peringkat (*Leaderboard*), dan koleksi *Badges* pencapaian.
2. **Ruang Belajar Mandiri (Personal Study Room):** Fasilitas produktivitas belajar mandiri lengkap dengan *Pomodoro Focus Timer* (25m/5m), *Interactive 3D Flashcards* untuk daya ingat konsep, *Daily Planner* target belajar, dan grafik statistik mingguan.
3. **Sistem Peringatan Dini (Early Warning System / EWS):** Fitur analitik otomatis bagi Tutor untuk mendeteksi sedini mungkin pelajar yang "Perlu Perhatian" (berisiko tertinggal akibat keterlambatan tugas, nilai rendah, atau inaktivitas login) agar segera mendapatkan pendampingan (*Tindak Lanjut*).

---

## Penerapan 7 Modul Praktikum Pemrograman Web 1 (Asprak Informatika Unsoed)

Aplikasi **Questudy** dibangun dengan menerapkan seluruh materi dari **7 Modul Praktikum Pemrograman Web 1** yang telah dipelajari selama perkuliahan di Jurusan Informatika Unsoed:

| No | Modul Praktikum | Materi Silabus Lab | Penerapan Nyata di Aplikasi Questudy | Berkas Utama |
|:---:|---|---|---|---|
| **Modul 1** | **HTML Dasar** | Struktur DTD `<!DOCTYPE html>`, tag `<html>`, `<head>`, `<body>`, heading `<h1>`–`<h6>`, format teks (`<b>`, `<i>`, `<mark>`), gambar `<img>`, dan list `<ul>`, `<ol>`, `<dl>`. | Kerangka seluruh halaman aplikasi, tipografi heading pada dashboard, render avatar profil `<img>`, dan navigasi sidebar list. | `index.php`<br>`pelajar/dashboard.php`<br>`pengajar/dashboard.php` |
| **Modul 2** | **HTML Lanjut** | Elemen semantik (`<header>`, `<main>`, `<nav>`, `<footer>`), *hyperlink* `<a>`, formulir (`<form>`, `<input>`, `<textarea>`, `<select>`), dan tabel (`<table>`, `<tr>`, `<td>`, `<th>`, colspan, rowspan). | Layout modular semantik, formulir input data (tugas, kuis, kelas), tabel rekapitulasi nilai rapor dan pemantauan Early Warning System (EWS). | `includes/layout_header.php`<br>`pelajar/rapor.php`<br>`pengajar/pemantauan.php` |
| **Modul 3** | **CSS (Cascading Style Sheet)** | Selector (Tag, ID, Class, Group), penataan tata letak (*box model*, margin, padding, border), warna, shadow, background, inline style, internal style, dan external CSS. | Tampilan antarmuka modern responsif menggunakan Tailwind CSS, elevasi kartu dashboard, progress bar XP, dan transisi hover interaktif. | `includes/layout_header.php`<br>`pelajar/ruang_belajar.php`<br>`pelajar/sertifikat.php` |
| **Modul 4** | **JavaScript** | Client-side scripting, variabel `let`/`const`, event handling (`onClick`, `onMouseOver`, `onInput`), validasi formulir di browser, manipulasi DOM, dan countdown timer. | Timer hitung mundur kuis online dengan auto-submit, engine Pomodoro focus timer 25m/5m, efek 3D rotasi Flashcards, filter pencarian realtime instan pada katalog, animasi Maskot Paus SVG, dan sintesis audio air alami via Web Audio API. | `pelajar/ruang_belajar.php`<br>`pelajar/kuis.php`<br>`pelajar/katalog.php`<br>`includes/maskot_paus.php` |
| **Modul 5** | **PHP Dasar** | Sintaks `<?php ?>`, variabel `$`, tipe data, operator aritmatika & logika, struktur kontrol (`if-else`, loops `foreach`, `while`), modularisasi `include`/`require`, dan fungsi kustom (*functions*). | Logika bisnis backend, kalkulasi formula XP & leveling `floor(xp/100)+1`, modularisasi tata letak (`layout_header.php`, `layout_footer.php`), pemrosesan array dinamis, dan helper sanitasi `e()`. | `config/koneksi.php`<br>`pelajar/gamifikasi.php`<br>`profil.php` |
| **Modul 6** | **PHP CRUD & Database MySQL** | Koneksi database server, manipulasi data CRUD (*Create, Read, Update, Delete*), query relasional SQL, filter data, dan eksekusi query interaktif. | Manajemen database 22+ tabel relasional via PDO Prepared Statements, operasi CRUD lengkap (kelas, materi, tugas, kuis, jadwal mentoring, ulasan rating). | `pengajar/kelas.php`<br>`pengajar/materi.php`<br>`pengajar/tugas.php`<br>`pengajar/kuis.php` |
| **Modul 7** | **PHP SESSION & User Authentication** | Konsep session (`session_start()`, `session_destroy()`, `session_unset()`, `$_SESSION`), User Authentication (Registrasi akun, Login verifikasi kata sandi, proteksi akses halaman/session check, dan logout). | Sistem otentikasi login single-door multi-role (Admin, Tutor, Pelajar), registrasi akun baru, proteksi role `cekRole()`, *inactivity timeout* otomatis 30 menit, regenerasi session ID, dan penyimpanan data sesi pengguna aktif. | `auth/login.php`<br>`auth/register.php`<br>`auth/logout.php`<br>`config/koneksi.php` |

---

## 9 Fitur Utama Aplikasi (Logika Bisnis & Penjelasan Kode)

Sesuai dengan ketentuan **Bab II Pasal 2.3 Buku Panduan Responsi** (minimal 9 fitur/modul yang mendukung logika bisnis aplikasi dan mengintegrasikan materi praktikum), berikut adalah rincian fungsional dan penjelasan kode dari **9 Fitur Utama Questudy**:

### 1. Autentikasi Multi-Role & Single-Door Login
* **Fungsi Bisnis:** Satu pintu masuk untuk 3 hak akses pengguna berbeda (**Admin**, **Tutor**, dan **Pelajar**). Dilengkapi tombol *Akun Demo Cepat (1-Klik)* untuk mempermudah penilaian. Calon tutor yang mendaftar mandiri otomatis berstatus `pending` dan wajib diverifikasi oleh administrator sebelum dapat membuka kelas.
* **Penjelasan Kode & Logika:**
  * Pengamanan kata sandi menggunakan hashing standar industri Bcrypt melalui `password_hash()` saat registrasi dan `password_verify()` saat login di `auth/login.php`.
  * Proteksi sesi menggunakan `session_regenerate_id(true)` untuk mencegah serangan *session fixation*.
  * Otentikasi dan otorisasi halaman diproteksi oleh fungsi modular `cekRole('pelajar')` di `config/koneksi.php`.
  * Mekanisme *inactivity timeout* otomatis menghancurkan sesi (`session_destroy()`) jika pengguna tidak aktif selama lebih dari 30 menit.

### 2. Manajemen Kelas, Silabus & Katalog Real-Time
* **Fungsi Bisnis:** Pengajar dapat membuat kelas dengan generator kode unik 6 karakter acak (misal: `PW2026`). Pelajar dapat bergabung melalui kode kelas atau menjelajah kelas keterampilan melalui katalog publik berdasarkan kategori dan tingkat kesulitan (pemula, menengah, lanjut) dengan pembatasan kuota siswa.
* **Penjelasan Kode & Logika:**
  * Pembuatan kode acak unik menggunakan kombinasi `substr(str_shuffle("..."), 0, 6)` pada `pengajar/kelas.php`.
  * Validasi kuota dan pengecekan apakah pelajar sudah terdaftar dieksekusi melalui transaksi relasional PDO di tabel `anggota_kelas`.
  * Pada `pelajar/katalog.php`, pencarian judul dan filter kategori bekerja secara **real-time instan** menggunakan event listener JavaScript `input` yang menyaring atribut `data-keywords` pada elemen DOM tanpa perlu reload halaman.

### 3. Materi Pembelajaran Multimodal (Teks & Berkas Dokumen)
* **Fungsi Bisnis:** Tutor dapat mempublikasikan materi artikel teks terformat, mengunggah modul berkas (PDF/DOCX/ZIP), dan menautkan referensi eksternal. Pelajar dapat membaca modul dan menekan tombol *"Tandai Selesai"* untuk mencatat progres belajarnya.
* **Penjelasan Kode & Logika:**
  * Logika upload berkas di `pengajar/materi.php` memvalidasi MIME type dan whitelist ekstensi berkas yang diizinkan serta mengamankan nama berkas unik menggunakan fungsi `time()`.
  * Saat pelajar menekan tombol selesai, sistem mencatat riwayat ke tabel `progres_materi` dan memanggil fungsi `tambahXP($conn, $userId, 'selesai_materi')` untuk menambah +20 XP ke akun pelajar.

### 4. Catatan Mandiri & Forum Diskusi Dua Arah
* **Fungsi Bisnis:** Di setiap materi pembelajaran, pelajar dapat menulis catatan pribadi (*personal study notes*) yang bersifat privat (hanya bisa dibaca dirinya sendiri). Selain itu, terdapat forum tanya-jawab publik dua arah antara pelajar dan pengajar.
* **Penjelasan Kode & Logika:**
  * Catatan pribadi disimpan pada tabel `catatan_materi` dengan filter `WHERE id_pelajar = ? AND id_materi = ?` sehingga privasi antar-siswa terjamin.
  * Forum diskusi menggunakan relasi ke tabel `diskusi_materi`, di mana setiap pesan disanitasi menggunakan fungsi `e()` (`htmlspecialchars`) untuk mencegah celah Cross-Site Scripting (XSS).

### 5. Manajemen Tugas & Penilaian Terstruktur
* **Fungsi Bisnis:** Pengajar membuat instruksi tugas bertenggat waktu (*deadline*). Pelajar mengunggah berkas jawaban atau teks. Pengajar memeriksa berkas yang dikumpulkan dan memberikan nilai angka (0–100) serta catatan umpan balik (*feedback*).
* **Penjelasan Kode & Logika:**
  * Sistem membandingkan waktu unggah dengan tenggat tugas: `time() > strtotime($deadline)`. Jika tepat waktu, status dicatat `tepat_waktu` dan memicu reward bonus +50 XP. Jika lewat, status dicatat `terlambat` tanpa bonus XP.
  * Nilai tugas langsung terintegrasi otomatis ke dalam modul Rapor Akademik untuk kalkulasi nilai akhir.

### 6. Kuis Online Countdown Timer & Auto-Grading
* **Fungsi Bisnis:** Evaluasi pembelajaran berbasis pilihan ganda (A, B, C, D) dengan batasan durasi menit pengerjaan dan kuota maksimal percobaan.
* **Penjelasan Kode & Logika:**
  * Engine countdown timer dibangun dengan JavaScript `setInterval()` pada `pelajar/kuis.php`. Ketika sisa detik mencapai nol, fungsi JavaScript secara otomatis memicu `form.submit()` (*auto-submit*).
  * Di sisi backend, jawaban siswa dibandingkan dengan kunci jawaban pada tabel `soal_kuis`. Skor dihitung proporsional `($benar / $total) * 100`. Jika meraih skor sempurna (100/100), sistem memberikan bonus reward +40 XP dan mengecek pembukaan lencana baru.

### 7. Mentoring Tutor Sebaya & Integrasi Google Meet
* **Fungsi Bisnis:** Pelajar dapat memesan (*booking*) sesi bimbingan privat 1-on-1 dengan tutor di luar jam kelas. Pengajar dapat menyetujui jadwal dan menyematkan tautan Google Meet / Zoom. Setelah sesi tuntas, pelajar memberikan rating bintang 1–5 dan ulasan.
* **Penjelasan Kode & Logika:**
  * Validasi bentrok jadwal di `pelajar/booking_tutor.php` memeriksa apakah tutor atau pelajar bersangkutan sudah memiliki sesi disetujui pada tanggal dan jam yang sama.
  * Tautan meeting yang dimasukkan tutor ditampilkan dalam bentuk tombol interaktif yang menyala aktif (*pulsing*) di antarmuka pelajar saat hari-H sesi mentoring.
  * Rata-rata rating pengajar dihitung secara dinamis melalui agregasi SQL `AVG(rating)` dan ditampilkan pada profil pengajar.

### 8. Ruang Belajar Mandiri (Pomodoro, Maskot Paus & Web Audio API)
* **Fungsi Bisnis:** Fasilitas penunjang produktivitas dan kesehatan mental belajar (mengatasi kejenuhan/burnout) yang terdiri atas: Pomodoro Timer 25 menit fokus & 5 menit istirahat, animasi pendamping Maskot Paus, generator audio white-noise air alami, 3D Flashcards, Planner target belajar, dan grafik statistik mingguan.
* **Penjelasan Kode & Logika:**
  * **Maskot Paus Interaktif:** Dibangun dengan vektor SVG murni dan animasi CSS keyframes (`includes/maskot_paus.php`). Memiliki 3 state dinamis: mode santai, mode fokus (efek semprotan air dan gelembung aktif), dan mode istirahat (mata rileks terpejam dan efek *Zzz*).
  * **Audio Relaksasi Air Alami:** Disintesis langsung di peramban menggunakan **Web Audio API** (`AudioContext`, `createBuffer` derau cokelat, dan `OscillatorNode` untuk tetesan air) tanpa menggunakan file audio MP3 eksternal sama sekali.
  * **3D Flashcards:** Menerapkan transformasi CSS 3D `perspective` dan `transform: rotateY(180deg)` saat kartu diklik untuk membalik antara pertanyaan dan jawaban.

### 9. Gamifikasi & Early Warning System (EWS)
* **Fungsi Bisnis:** 
  * **Gamifikasi:** Formula XP bertingkat, leveling otomatis `floor(xp/100)+1`, papan peringkat (*Leaderboard*), 5 lencana pencapaian (*badges*), efek modal perayaan kembang api confetti saat submit, serta modul Cetak Rapor Transkrip dan Sertifikat Kelulusan Resmi dengan nomor seri unik verifikasi.
  * **Early Warning System (EWS):** Sistem analitik otomatis di dashboard pengajar yang memindai siswa berisiko tertinggal (tugas terlambat > X, nilai < Y, inaktif > Z hari) dan memberi peringatan visual merah *"Perlu Perhatian"* agar pengajar segera mencatat bimbingan tindak lanjut.
* **Penjelasan Kode & Logika:**
  * Fungsi terpusat `tambahXP()` di `config/koneksi.php` secara otomatis mengkalkulasi kenaikan level dan memeriksa syarat XP pada tabel `badges` untuk memberikan lencana baru secara instan.
  * Algoritma EWS di `pengajar/pemantauan.php` menggunakan subquery SQL untuk mengagregasi keterlambatan pengumpulan tugas dan rata-rata skor kuis per siswa, lalu membandingkannya dengan ambang batas di tabel `aturan_moderasi`.
  * Fitur cetak Rapor dan Sertifikat di `pelajar/rapor.php` dan `pelajar/sertifikat.php` memanfaatkan CSS `@media print` untuk menyembunyikan navigasi/sidebar dan merender dokumen formal siap cetak atau simpan ke PDF.

---
- **Backend:** PHP 8+ Native (Berorientasi Objek & Prosedural Bersih)
- **Database:** MySQL 8.x (Koneksi **PDO Prepared Statements** untuk proteksi total dari SQL Injection)
- **Frontend Styling:** **Tailwind CSS** (via CDN resmi) dengan kustomisasi Questudy Navy (`#0F294A`), Primary Blue (`#0066CC`), Amber Gamifikasi (`#F59E0B`), dan Red Alert (`#EF4444`).
- **Icons & Tipografi:** Font Awesome 6 & Google Fonts (*Plus Jakarta Sans* / *Inter*).
- **Client Scripting:** Vanilla JavaScript ES6 (Countdown Timer, Pomodoro Engine, 3D Flip Flashcards, Rating Stars, Mobile Sidebar Drawer).

---

## Kredensial Akun Demo Siap Uji

> **Kata sandi default untuk seluruh akun demo:** `123456`

| Peran (Role) | Alamat Email | Kata Sandi | Keterangan & Karakteristik Data Demo |
|---|---|---|---|
| **Administrator** | `admin@questudy.id` | `123456` | Kelola seluruh pengguna, verifikasi pengajar pending, atur bobot XP & ambang batas peringatan dini, moderasi konten. |
| **Tutor 1 (Aktif)** | `pengajar@questudy.id` | `123456` | **Budi Santoso** — Mengampu kelas *Pemrograman Web Lanjut (PW2026)*. Memiliki 1 siswa dalam pemantauan EWS (*Rizky Ramadhan*). |
| **Tutor 2 (Aktif)** | `siti@questudy.id` | `123456` | **Siti Aminah** — Mengampu kelas *Basis Data & SQL Komprehensif (BD2026)*. |
| **Tutor 3 (Pending)**| `tutor.pending@questudy.id`| `123456` | **Reza Pratama** — Akun pengajar berstatus `pending` untuk demo fitur verifikasi *Terima / Tolak* di panel Admin. |
| **Pelajar 1 (Aktif)** | `pelajar@questudy.id` | `123456` | **Ahmad Fauzi** — Siswa teladan (Level 5, 450 XP, Streak 7 hari, 3 Lencana terbuka, seluruh tugas tepat waktu, nilai kuis 100). |
| **Pelajar 2 (Aktif)** | `dewi@questudy.id` | `123456` | **Dewi Lestari** — Siswa menengah (Level 3, 220 XP, Streak 3 hari, 2 Lencana terbuka, nilai rata-rata 80). |
| **Pelajar 3 (Berisiko)**| `rizky@questudy.id` | `123456` | **Rizky Ramadhan** — Siswa berisiko untuk demo **Early Warning System** (1 tugas terlambat, nilai rendah 55, inaktif > 7 hari). |

---

## Panduan Instalasi & Menjalankan di Laragon

1. **Pastikan Laragon Aktif:**
   - Buka aplikasi **Laragon** di komputer Anda, lalu klik tombol **Start All** (memastikan Apache dan MySQL berjalan).

2. **Lokasi Berkas Proyek:**
   - Proyek ini telah dibangun lengkap di dalam folder Laragon `www`:
     `C:\laragon\www\questudy` atau `C:\laragon\www\Prak-Pemweb`

3. **Impor Database MySQL:**
   - Buka terminal Laragon atau phpMyAdmin (`http://localhost/phpmyadmin`).
   - Buat database baru bernama `db_questudy` (atau biarkan dieksekusi otomatis oleh skrip):
     ```bash
     mysql -u root -p < database.sql
     ```
   - Seluruh tabel relasional, foreign key constraints, dan dummy data siap demo akan terisi secara otomatis.

4. **Buka Aplikasi di Browser:**
   - Akses melalui URL lokal Laragon:
     - `http://localhost/questudy` (atau `http://localhost/Prak-Pemweb`)
     - Atau melalui Virtual Host: `http://questudy.test`

---

## Struktur Direktori Proyek

```text
├── admin/                     # Modul Administrator
│   ├── dashboard.php          # Screen 12: Statistik platform & grafik aktivitas
│   ├── pengguna.php           # Screen 13: Verifikasi pengajar & manajemen akun
│   └── aturan_moderasi.php    # Screen 14: Konfigurasi XP, ambang batas EWS & moderasi
├── auth/                      # Otentikasi Terpadu
│   ├── login.php              # Login single-door dengan akses cepat akun demo
│   ├── logout.php             # Logout aman & penghancuran sesi
│   └── register.php           # Pendaftaran Pelajar & Tutor (pending)
├── config/
│   └── koneksi.php            # Koneksi PDO MySQL, timeout sesi, proteksi role & fungsi gamifikasi
├── includes/
│   ├── layout_header.php      # Top Navbar Navy (#0F294A), XP/Level pill, & Sidebar seragam
│   └── layout_footer.php      # Footer & skrip drawer responsif
├── pelajar/                   # Modul Pelajar
│   ├── dashboard.php          # Progress belajar, streak, tenggat terdekat (tanpa label berisiko)
│   ├── kelas.php              # Gabung kelas via kode unik & daftar kelas
│   ├── materi.php             # Baca materi, catatan pribadi & forum tanya jawab
│   ├── tugas.php              # Pengumpulan jawaban teks/file & cek nilai
│   ├── kuis.php               # Pengerjaan kuis ber-timer countdown auto-submit
│   ├── rapor.php              # Rapor nilai mandiri & konversi huruf mutu A-E
│   ├── booking_tutor.php      # Screen 01: Pengajuan jadwal bimbingan tutor
│   ├── rating_ulasan.php      # Screen 02: Rating bintang 1-5 & feedback mentoring
│   ├── gamifikasi.php         # Screen 03: XP Bar besar, Leaderboard, & 5 Badges
│   └── ruang_belajar.php      # Screen 04-07: Pomodoro, 3D Flashcards, Planner & Statistik
├── pengajar/                  # Modul Tutor / Tutor
│   ├── dashboard.php          # Ringkasan akademik kelas & quick widget EWS
│   ├── pemantauan.php         # Screen 09: Early Warning System & pencatatan tindak lanjut
│   ├── kelas.php              # Screen 11: CRUD kelas, generate kode 6 char, kelola anggota
│   ├── materi.php             # CRUD materi (teks/file/tautan), progres & moderasi forum
│   ├── tugas.php              # Screen 10: CRUD tugas, penilaian pengumpulan pelajar
│   ├── kuis.php               # CRUD kuis berdurasi & kelola bank soal pilihan ganda
│   ├── rapor.php              # Rapor otomatis kelas & rekapitulasi nilai A-E
│   └── booking.php            # Persetujuan booking mentoring & link Google Meet
├── uploads/                   # Direktori Berkas Unggahan
│   ├── materi/                # File dokumen modul pembelajaran (.gitkeep)
│   ├── profil/                # Foto profil pengguna (.gitkeep & default.png)
│   └── tugas/                 # Berkas jawaban tugas pelajar (.gitkeep)
├── database.sql               # Skema lengkap 22 tabel relasional & dummy data siap demo
├── index.php                  # Landing page publik Questudy 
├── profil.php                 # Screen 08: Edit profil, unggah foto, & ganti password
└── README.md                  # Dokumentasi komprehensif sistem
```

---

## Aspek Keamanan Sistem
1. **Pencegahan SQL Injection:** 100% interaksi query dinamis menerapkan **PDO Prepared Statements** dengan parameter terikat (`?` / execute array).
2. **Pencegahan Cross-Site Scripting (XSS):** Seluruh output berbasis input pengguna dicetak melalui pembungkus sanitasi `e()` yang menggunakan `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
3. **Penyimpanan Kredensial Aman:** Kata sandi dienkripsi menggunakan algoritma standar industri **Bcrypt** (`password_hash` dan `password_verify`).
4. **Session Security & Timeout:** Sesi login dilengkapi *inactivity timeout* otomatis selama 30 menit serta regenerasi ID sesi saat login berhasil.
5. **Validasi Berkas Ketat:** Unggahan berkas foto profil, materi, dan tugas divalidasi berdasarkan ekstensi yang diizinkan dan pembatasan kapasitas ukuran berkas maksimum.


## Pembaruan: Platform Tutor Pengembangan Skill

Perubahan utama pada versi ini (import ulang `database.sql` agar struktur baru terpasang):

- **Istilah:** peran `pengajar` ditampilkan sebagai **Tutor**, peran `pelajar` tetap **Pelajar**. Satu akun hanya memiliki satu peran; jika ingin dua peran, gunakan dua email berbeda.
- **Kelas keterampilan:** kelas punya kategori, tingkat (pemula/menengah/lanjut), dan kuota peserta. Pelajar dapat mencari dan bergabung lewat **Katalog Kelas** (`pelajar/katalog.php`).
- **Pendaftaran tutor:** wajib mengisi keahlian, deskripsi pengalaman (minimal 30 karakter), dan tautan portofolio; akun berstatus *pending* sampai disetujui admin.
- **Kuota dan batas** (tabel `pengaturan_sistem`, dapat diubah admin di `admin/pengaturan.php`): kuota kelas, maksimal kelas per tutor, maksimal kelas yang diikuti pelajar, maksimal booking menunggu per pelajar, maksimal sesi per tutor per minggu, dan batas waktu pembatalan.
- **Booking:** cek jadwal bentrok, batas pembatalan, status sesi (menunggu, disetujui, selesai, dibatalkan), serta rating hanya setelah sesi selesai.
- **Laporan pengguna:** pelajar dan tutor dapat melapor (`laporan.php`); admin meninjau di `admin/laporan.php` (peringatan, tangguhkan akun, atau tutup laporan).
- **Tampilan:** teks lebih besar, tanpa huruf kapital semua dan animasi berlebih, dashboard pelajar disederhanakan menjadi 3 kartu.
- **Keamanan:** celah login (password `123456` dapat masuk ke akun mana pun) dihapus; bug pendaftaran akun (jumlah kolom dan nilai INSERT tidak sama) diperbaiki.

Tabel baru: `pengaturan_sistem`, `laporan`. Kolom baru: `users.deskripsi_tutor`, `users.portofolio_url`, `kelas.kategori`, `kelas.tingkat`, `kelas.kuota`, `booking_tutor.alasan_batal`.

---

## Fitur Interaktif Tambahan Terkini
1. **Maskot Paus Interaktif & White-Noise Audio Alami (`includes/maskot_paus.php`, `pelajar/ruang_belajar.php`):**
   - Animasi SVG paus yang merespons status belajar (mode fokus, mode istirahat, dan mode santai).
   - Generator suara air alami (derau cokelat & tetesan air menenangkan) berbasis Web Audio API langsung dari peramban.
2. **Akun Demo Cepat 1-Klik (`auth/login.php`):**
   - Akses instan bagi penguji/asisten praktikum untuk login sebagai Admin, Tutor, atau Pelajar tanpa perlu mengetik manual.
3. **Cetak Rapor Akademik & Sertifikat Kelulusan Resmi (`pelajar/rapor.php`, `pelajar/sertifikat.php`):**
   - Rapor cetak resmi dengan kop surat, transkrip nilai, dan kolom tanda tangan.
   - Sertifikat kelulusan elegan dengan nomor seri unik verifikasi `QST/TAHUN/KELAS/USER` yang siap cetak atau unduh sebagai PDF.
4. **Efek Perayaan Gamifikasi & Confetti Pesta (`includes/layout_footer.php`):**
   - Pop-up modal perayaan saat kuis atau tugas diselesaikan dengan semburan efek animasi confetti warna-warni dan notifikasi naik level.
5. **Pencarian Realtime Instan Katalog Kelas (`pelajar/katalog.php`):**
   - Filter kartu kelas secara instan saat mengetik tanpa perlu reload halaman.


- **Tampilan baru:** gaya cerah berwarna (gradasi biru-ungu) dengan satu font, **Plus Jakarta Sans**, di semua halaman; halaman masuk dan daftar memakai panel deskripsi di sebelah formulir; beranda memiliki bagian Fitur, Cara kerja, dan FAQ; efek hover pada kartu, tombol, menu, dan baris tabel.
- **Palet warna dan maskot:** palet Iced Denim (#5798D7), Frost (#E6F1FA), Pistachio (#B9C255), Lemon Cream (#EDE986), dengan sentuhan Raspberry Rose (#F2619C). Maskot paus beranimasi (`includes/maskot_paus.php`) tampil di dashboard, halaman Pomodoro (bereaksi saat fokus, jeda, dan istirahat), serta panel masuk dan daftar. Dashboard pelajar dibuat ringkas dalam satu layar.
- **Latar laut dan suara air:** halaman masuk dan daftar memiliki latar ombak, gelembung, dan paus yang berenang kanan-kiri (`latarLaut()`); halaman Pomodoro punya tombol **Suara air** (dibuat dengan Web Audio API, tanpa file audio) yang menyala saat sesi berjalan. Profil pelajar menampilkan lencana yang diraih dan yang masih terkunci.
