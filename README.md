# Questudy — LMS Gamifikasi, Ruang Belajar Pribadi & Peringatan Dini
> **Tema Pembangunan Berkelanjutan (SDGs 4): Quality Education (Pendidikan Berkualitas, Inklusif, dan Setara)**

---

## 🌟 Tentang Proyek Questudy
**Questudy** adalah platform *Learning Management System* (LMS) modern dan inklusif yang dirancang untuk menjawab tantangan kesenjangan motivasi belajar dan angka putus studi pada pendidikan tinggi. Mengacu pada target **SDGs 4 (Quality Education)**, Questudy mengintegrasikan tiga pilar inovasi:

1. **Mekanisme Gamifikasi RPG (Gamified Learning):** Mengubah proses belajar menjadi pengalaman yang memotivasi lewat akumulasi *Experience Points* (XP), kenaikan *Level*, papan peringkat (*Leaderboard*), dan koleksi *Badges* pencapaian.
2. **Ruang Belajar Mandiri (Personal Study Room):** Fasilitas produktivitas belajar mandiri lengkap dengan *Pomodoro Focus Timer* (25m/5m), *Interactive 3D Flashcards* untuk daya ingat konsep, *Daily Planner* target belajar, dan grafik statistik mingguan.
3. **Sistem Peringatan Dini (Early Warning System / EWS):** Fitur analitik otomatis bagi Pengajar untuk mendeteksi sedini mungkin mahasiswa yang "Perlu Perhatian" (berisiko tertinggal akibat keterlambatan tugas, nilai rendah, atau inaktivitas login) agar segera mendapatkan pendampingan (*Tindak Lanjut*).

---

## 🧭 Pemetaan 9 Modul Terintegrasi

| No | Modul Sistem | Deskripsi Implementasi Fitur | Berkas Utama |
|---|---|---|---|
| **1** | **Autentikasi & Autorisasi Multi-Role** | Login satu pintu, pendaftaran mandiri (Pelajar langsung aktif, Pengajar status pending verifikasi admin), proteksi role `cekRole()`, *session inactivity timeout* 30 menit, update *streak* harian, dan sanitasi XSS `e()`. | `auth/login.php`<br>`auth/register.php`<br>`auth/logout.php`<br>`config/koneksi.php` |
| **2** | **Manajemen Kelas & Silabus** | Pembuatan kelas dengan *generator* kode acak unik 6 karakter, pendaftaran kelas via kode, pengelolaan anggota kelas, dan publikasi pengumuman kelas. | `pengajar/kelas.php`<br>`pelajar/kelas.php` |
| **3** | **Materi Pembelajaran Multimodal** | Dukungan materi artikel teks terformat, unggahan modul dokumen berkas (PDF/DOCX/ZIP), dan tautan referensi eksternal, lengkap dengan tombol status "Tandai Selesai" (+20 XP). | `pengajar/materi.php`<br>`pelajar/materi.php` |
| **4** | **Catatan Mandiri & Forum Diskusi** | CRUD catatan pribadi (*personal notes*) mahasiswa per materi dan forum tanya-jawab dua arah antara pengajar dan mahasiswa di setiap modul materi. | `pelajar/materi.php`<br>`pengajar/materi.php` |
| **5** | **Manajemen Tugas & Penilaian** | Publikasi tugas bertenggat waktu, pengumpulan jawaban teks & berkas tugas, deteksi otomatis status *Tepat Waktu* (+50 XP) vs *Terlambat*, serta formulir penilaian 0–100 dan komentar evaluasi pengajar. | `pengajar/tugas.php`<br>`pelajar/tugas.php` |
| **6** | **Kuis Online & Bank Soal** | Pembuatan kuis dengan batas durasi menit dan batas kuota percobaan, bank soal pilihan ganda (A, B, C, D), *Countdown Timer JavaScript* berfitur *auto-submit* saat waktu habis, *auto-grading*, dan bonus XP skor 100. | `pengajar/kuis.php`<br>`pelajar/kuis.php` |
| **7** | **Mentoring Tutor Sebaya & Ulasan** | Pengajuan booking konsultasi privat mahasiswa ke tutor/pengajar, integrasi tautan Google Meet, sistem rating bintang 1–5 interaktif, dan ulasan kepuasan (+30 XP). | `pelajar/booking_tutor.php`<br>`pelajar/rating_ulasan.php`<br>`pengajar/booking.php` |
| **8** | **Ruang Belajar Mandiri (4 Tab)** | **Tab 1: Pomodoro Timer** (25m fokus, 5m istirahat, auto simpan +15 XP); **Tab 2: 3D Flashcards** (efek flip rotasi 3D dan tanda status hafal); **Tab 3: Planner** (agenda tenggat tugas & checklist target mandiri); **Tab 4: Statistik** (jam fokus, streak, dan grafik batang aktivitas mingguan). | `pelajar/ruang_belajar.php` |
| **9** | **Gamifikasi & Peringatan Dini (EWS)** | **Gamifikasi:** Formula XP, Leveling (`floor(xp/100)+1`), Leaderboard peringkat kelas, dan 5 Koleksi Lencana.<br>**Early Warning System:** Algoritma pemindai siswa berisiko (tugas terlambat > X, rata-rata nilai < Y, inaktif > Z hari) dan formulir pencatatan tindak lanjut bimbingan. | `pelajar/gamifikasi.php`<br>`pengajar/pemantauan.php`<br>`admin/aturan_moderasi.php` |

---

## 🛠️ Tumpukan Teknologi (Tech Stack)
- **Backend:** PHP 8+ Native (Berorientasi Objek & Prosedural Bersih)
- **Database:** MySQL 8.x (Koneksi **PDO Prepared Statements** untuk proteksi total dari SQL Injection)
- **Frontend Styling:** **Tailwind CSS** (via CDN resmi) dengan kustomisasi Questudy Navy (`#0F294A`), Primary Blue (`#0066CC`), Amber Gamifikasi (`#F59E0B`), dan Red Alert (`#EF4444`).
- **Icons & Tipografi:** Font Awesome 6 & Google Fonts (*Plus Jakarta Sans* / *Inter*).
- **Client Scripting:** Vanilla JavaScript ES6 (Countdown Timer, Pomodoro Engine, 3D Flip Flashcards, Rating Stars, Mobile Sidebar Drawer).

---

## 🔑 Kredensial Akun Demo Siap Uji

> **Kata sandi default untuk seluruh akun demo:** `123456`

| Peran (Role) | Alamat Email | Kata Sandi | Keterangan & Karakteristik Data Demo |
|---|---|---|---|
| **Administrator** | `admin@questudy.id` | `123456` | Kelola seluruh pengguna, verifikasi pengajar pending, atur bobot XP & ambang batas peringatan dini, moderasi konten. |
| **Pengajar 1 (Aktif)** | `pengajar@questudy.id` | `123456` | **Dr. Budi Santoso, M.Kom** — Mengampu kelas *Pemrograman Web Lanjut (PW2026)*. Memiliki 1 siswa dalam pemantauan EWS (*Rizky Ramadhan*). |
| **Pengajar 2 (Aktif)** | `siti@questudy.id` | `123456` | **Siti Aminah, S.Pd., M.T.** — Mengampu kelas *Basis Data & SQL Komprehensif (BD2026)*. |
| **Pengajar 3 (Pending)**| `tutor.pending@questudy.id`| `123456` | **Reza Pratama, S.Kom** — Akun pengajar berstatus `pending` untuk demo fitur verifikasi *Terima / Tolak* di panel Admin. |
| **Pelajar 1 (Aktif)** | `pelajar@questudy.id` | `123456` | **Ahmad Fauzi** — Siswa teladan (Level 5, 450 XP, Streak 7 hari, 3 Lencana terbuka, seluruh tugas tepat waktu, nilai kuis 100). |
| **Pelajar 2 (Aktif)** | `dewi@questudy.id` | `123456` | **Dewi Lestari** — Siswa menengah (Level 3, 220 XP, Streak 3 hari, 2 Lencana terbuka, nilai rata-rata 80). |
| **Pelajar 3 (Berisiko)**| `rizky@questudy.id` | `123456` | **Rizky Ramadhan** — Siswa berisiko untuk demo **Early Warning System** (1 tugas terlambat, nilai rendah 55, inaktif > 7 hari). |

---

## 🚀 Panduan Instalasi & Menjalankan di Laragon

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

## 📂 Struktur Direktori Proyek

```text
├── admin/                     # Modul Administrator
│   ├── dashboard.php          # Screen 12: Statistik platform & grafik aktivitas
│   ├── pengguna.php           # Screen 13: Verifikasi pengajar & manajemen akun
│   └── aturan_moderasi.php    # Screen 14: Konfigurasi XP, ambang batas EWS & moderasi
├── auth/                      # Otentikasi Terpadu
│   ├── login.php              # Login single-door dengan akses cepat akun demo
│   ├── logout.php             # Logout aman & penghancuran sesi
│   └── register.php           # Pendaftaran Pelajar & Pengajar (pending)
├── config/
│   └── koneksi.php            # Koneksi PDO MySQL, timeout sesi, proteksi role & fungsi gamifikasi
├── includes/
│   ├── layout_header.php      # Top Navbar Navy (#0F294A), XP/Level pill, & Sidebar seragam
│   └── layout_footer.php      # Footer SDGs 4 & skrip drawer responsif
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
├── pengajar/                  # Modul Pengajar / Tutor
│   ├── dashboard.php          # Ringkasan akademik kelas & quick widget EWS
│   ├── pemantauan.php         # Screen 09: Early Warning System & pencatatan tindak lanjut
│   ├── kelas.php              # Screen 11: CRUD kelas, generate kode 6 char, kelola anggota
│   ├── materi.php             # CRUD materi (teks/file/tautan), progres & moderasi forum
│   ├── tugas.php              # Screen 10: CRUD tugas, penilaian pengumpulan mahasiswa
│   ├── kuis.php               # CRUD kuis berdurasi & kelola bank soal pilihan ganda
│   ├── rapor.php              # Rapor otomatis kelas & rekapitulasi nilai A-E
│   └── booking.php            # Persetujuan booking mentoring & link Google Meet
├── uploads/                   # Direktori Berkas Unggahan
│   ├── materi/                # File dokumen modul perkuliahan (.gitkeep)
│   ├── profil/                # Foto profil pengguna (.gitkeep & default.png)
│   └── tugas/                 # Berkas jawaban tugas mahasiswa (.gitkeep)
├── database.sql               # Skema lengkap 22 tabel relasional & dummy data siap demo
├── index.php                  # Landing page publik Questudy (SDGs 4 Education)
├── profil.php                 # Screen 08: Edit profil, unggah foto, & ganti password
└── README.md                  # Dokumentasi komprehensif sistem
```

---

## 🛡️ Aspek Keamanan Sistem
1. **Pencegahan SQL Injection:** 100% interaksi query dinamis menerapkan **PDO Prepared Statements** dengan parameter terikat (`?` / execute array).
2. **Pencegahan Cross-Site Scripting (XSS):** Seluruh output berbasis input pengguna dicetak melalui pembungkus sanitasi `e()` yang menggunakan `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
3. **Penyimpanan Kredensial Aman:** Kata sandi dienkripsi menggunakan algoritma standar industri **Bcrypt** (`password_hash` dan `password_verify`).
4. **Session Security & Timeout:** Sesi login dilengkapi *inactivity timeout* otomatis selama 30 menit serta regenerasi ID sesi saat login berhasil.
5. **Validasi Berkas Ketat:** Unggahan berkas foto profil, materi, dan tugas divalidasi berdasarkan ekstensi yang diizinkan dan pembatasan kapasitas ukuran berkas maksimum.
