<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/koneksi.php';

$isLoggedIn = isset($_SESSION['id_user']);
$userRole = $_SESSION['role'] ?? null;

$dashboardUrl = 'auth/login.php';
if ($isLoggedIn) {
    if ($userRole === 'admin') $dashboardUrl = 'admin/dashboard.php';
    elseif ($userRole === 'pengajar') $dashboardUrl = 'pengajar/dashboard.php';
    else $dashboardUrl = 'pelajar/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questudy — Belajar skill baru, tanpa bosan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { indigo:{50:'#F1F7FD',100:'#E6F1FA',200:'#C9E0F5',300:'#A3C8EE',400:'#7DB1E4',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0',800:'#264F82',900:'#1F3A5A'},blue:{50:'#F1F7FD',100:'#E6F1FA',200:'#C9E0F5',300:'#A3C8EE',400:'#7DB1E4',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0',800:'#264F82',900:'#1F3A5A'},sky:{50:'#F2F9FE',100:'#E1F0FC',200:'#BFDFF7',300:'#93C8F0',400:'#6BB0E7',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0'},violet:{50:'#FAF3FE',100:'#F4E4FC',200:'#E7BEF8',300:'#D9A0F1',400:'#C67FE6',500:'#AE63D6',600:'#944FBA',700:'#783F96'},amber:{50:'#FEFDEB',100:'#FBF8CC',200:'#F6F1A4',300:'#EDE986',400:'#E1DC5E',500:'#C8C23A',600:'#8F8A18',700:'#6F6A12',800:'#55510F',900:'#3F3C0C',gold:'#F59E0B'},emerald:{50:'#F7F9E8',100:'#EEF2CC',200:'#DFE79F',300:'#CCD777',400:'#B9C255',500:'#9DA83C',600:'#6E7A22',700:'#566018',800:'#434B13',900:'#33390F',fresh:'#10B981'},pink:{50:'#FEF1F6',100:'#FDE3EE',200:'#FAC5DC',300:'#F79BC0',400:'#F47BAE',500:'#F2619C',600:'#D84582',700:'#B2336A'},rose:{50:'#FEF1F6',100:'#FDE3EE',200:'#FAC5DC',300:'#F79BC0',400:'#F47BAE',500:'#F2619C',600:'#D84582',700:'#B2336A'} }, fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] } } } }</script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .grad { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF); }
        .grad-text { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .btn-grad { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF); color: #fff; transition: transform .18s ease, box-shadow .18s ease, filter .18s ease; }
        .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(61, 123, 191, .4); filter: brightness(1.06); }
        .lift { transition: transform .2s ease, box-shadow .2s ease; }
        .lift:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(61, 123, 191, .2); }
        .float-a { animation: fl 6s ease-in-out infinite; }
        .float-b { animation: fl 7s ease-in-out infinite reverse; }
        @keyframes fl { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        details > summary { list-style: none; cursor: pointer; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .chev { transform: rotate(180deg); }
        .field { width: 100%; padding: .8rem 1rem; border-radius: 1rem; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 15px; transition: all .18s ease; }
        .field:focus { outline: none; background: #fff; border-color: #78AADE; box-shadow: 0 0 0 4px rgba(120, 170, 222, .2); }
        .field:hover { border-color: #BED8F1; }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased bg-gradient-to-br from-sky-50 via-white to-violet-50">

<!-- NAVBAR -->
<header class="sticky top-0 z-50 backdrop-blur bg-white/70 border-b border-white/60">
    <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
        <a href="#beranda" class="flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-2xl grad text-white grid place-items-center"><i class="fa-solid fa-graduation-cap"></i></span>
            <span class="text-xl font-extrabold">Questudy</span>
        </a>
        <nav class="hidden md:flex items-center gap-1 text-sm font-semibold text-slate-600">
            <a href="#beranda" class="px-4 py-2 rounded-full hover:bg-indigo-50 hover:text-indigo-600 transition">Beranda</a>
            <a href="#fitur" class="px-4 py-2 rounded-full hover:bg-indigo-50 hover:text-indigo-600 transition">Fitur</a>
            <a href="#cara-kerja" class="px-4 py-2 rounded-full hover:bg-indigo-50 hover:text-indigo-600 transition">Cara kerja</a>
            <a href="#faq" class="px-4 py-2 rounded-full hover:bg-indigo-50 hover:text-indigo-600 transition">FAQ</a>
        </nav>
        <div class="flex items-center gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="<?= base_url($dashboardUrl); ?>" class="btn-grad px-5 py-2.5 rounded-full text-sm font-bold">Ke dashboard</a>
            <?php else: ?>
                <a href="<?= base_url('auth/login.php'); ?>" class="px-5 py-2.5 rounded-full text-sm font-bold text-indigo-600 bg-white border border-indigo-100 hover:bg-indigo-50 hover:-translate-y-0.5 transition">Masuk</a>
                <a href="<?= base_url('auth/register.php'); ?>" class="btn-grad px-5 py-2.5 rounded-full text-sm font-bold">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- HERO -->
<section id="beranda" class="relative overflow-hidden">
    <div class="absolute -top-20 -left-20 w-96 h-96 rounded-full bg-sky-200/50 blur-3xl"></div>
    <div class="absolute top-20 right-0 w-96 h-96 rounded-full bg-violet-200/60 blur-3xl"></div>
    <div class="relative max-w-6xl mx-auto px-5 py-16 md:py-24 grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-block px-4 py-1.5 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold">Platform belajar skill</span>
            <h1 class="mt-5 text-5xl md:text-6xl font-extrabold leading-tight">Belajar skill baru,<br><span class="grad-text">tanpa bosan.</span></h1>
            <p class="mt-5 text-lg text-slate-500 max-w-md">Pilih kelas, belajar bareng tutor, dan kumpulkan XP di setiap langkah.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= base_url($dashboardUrl); ?>" class="btn-grad px-8 py-4 rounded-full font-bold">Lanjut belajar</a>
                <?php else: ?>
                    <a href="<?= base_url('auth/register.php'); ?>" class="btn-grad px-8 py-4 rounded-full font-bold">Daftar gratis</a>
                    <a href="<?= base_url('auth/login.php'); ?>" class="px-8 py-4 rounded-full font-bold text-indigo-600 bg-white border border-indigo-100 hover:bg-indigo-50 hover:-translate-y-0.5 transition">Saya sudah punya akun</a>
                <?php endif; ?>
            </div>
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold text-slate-500">
                <span><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i>Gratis</span>
                <span><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i>Banyak kategori</span>
                <span><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i>Tutor terverifikasi</span>
            </div>
        </div>

        <!-- Contoh tampilan -->
        <div>
            <div class="relative h-[400px] hidden sm:block">
                <div class="absolute top-8 left-0 w-64 p-5 rounded-3xl grad text-white shadow-2xl shadow-indigo-300/50 float-a">
                    <div class="text-sm font-semibold text-white/80">Level 5</div>
                    <div class="text-3xl font-extrabold">450 XP</div>
                    <div class="mt-3 h-2.5 rounded-full bg-white/30 overflow-hidden"><div class="h-full w-3/4 rounded-full bg-amber-300"></div></div>
                    <div class="mt-2 text-xs text-white/80">50 XP lagi ke level 6</div>
                </div>
                <div class="absolute top-0 right-4 bg-white rounded-3xl p-4 shadow-xl flex items-center gap-3 float-b hover:scale-105 transition">
                    <span class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-500 grid place-items-center text-xl"><i class="fa-solid fa-fire"></i></span>
                    <div><div class="text-xs text-slate-500">Streak</div><div class="font-extrabold text-lg">7 hari</div></div>
                </div>
                <div class="absolute top-36 right-0 w-56 bg-white rounded-3xl p-5 shadow-xl float-a hover:scale-105 transition">
                    <div class="text-sm font-semibold text-slate-500">Sesi fokus</div>
                    <div class="text-4xl font-extrabold">25:00</div>
                    <div class="mt-3 inline-block px-4 py-1.5 rounded-full grad text-white text-sm font-bold">Mulai</div>
                </div>
                <div class="absolute bottom-6 left-6 w-60 bg-white rounded-3xl p-4 shadow-xl flex items-center gap-3 float-b hover:scale-105 transition">
                    <span class="w-12 h-12 rounded-full bg-violet-100 text-violet-600 grid place-items-center font-extrabold">T</span>
                    <div><div class="font-bold">Desain Grafis</div><div class="text-sm text-amber-500"><i class="fa-solid fa-star"></i> 4,8 · Pemula</div></div>
                </div>
                <div class="absolute bottom-0 right-8 bg-amber-100 text-amber-800 rounded-2xl px-4 py-3 shadow-lg font-bold float-a hover:scale-105 transition">
                    <i class="fa-solid fa-trophy mr-1.5"></i>Lencana baru!
                </div>
            </div>
            <p class="text-center text-sm text-slate-400 mt-3">Contoh tampilan dengan data fiktif</p>
        </div>
    </div>
</section>

<!-- FITUR -->
<section id="fitur" class="py-16">
    <div class="max-w-6xl mx-auto px-5">
        <div class="text-center max-w-xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-extrabold">Semua yang kamu butuh</h2>
            <p class="mt-2 text-slate-500">Satu tempat untuk belajar skill.</p>
        </div>
        <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-600 grid place-items-center text-xl"><i class="fa-solid fa-book-open"></i></div>
                    <h3 class="mt-4 text-lg font-bold">Kelas skill</h3>
                    <p class="mt-1 text-slate-500">Pilih kelas sesuai minat dan tingkatmu.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-600 grid place-items-center text-xl"><i class="fa-solid fa-handshake-angle"></i></div>
                    <h3 class="mt-4 text-lg font-bold">Tutor dan booking</h3>
                    <p class="mt-1 text-slate-500">Booking sesi bareng tutor pilihan.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 grid place-items-center text-xl"><i class="fa-solid fa-bolt"></i></div>
                    <h3 class="mt-4 text-lg font-bold">XP dan lencana</h3>
                    <p class="mt-1 text-slate-500">Belajar dapat poin, level, dan badge.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 grid place-items-center text-xl"><i class="fa-solid fa-brain"></i></div>
                    <h3 class="mt-4 text-lg font-bold">Ruang belajar</h3>
                    <p class="mt-1 text-slate-500">Pomodoro, flashcard, dan planner.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-pink-100 text-pink-600 grid place-items-center text-xl"><i class="fa-solid fa-circle-question"></i></div>
                    <h3 class="mt-4 text-lg font-bold">Kuis dan tugas</h3>
                    <p class="mt-1 text-slate-500">Latihan dan nilai langsung terlihat.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 grid place-items-center text-xl"><i class="fa-solid fa-hand-holding-heart"></i></div>
                    <h3 class="mt-4 text-lg font-bold">Pendampingan</h3>
                    <p class="mt-1 text-slate-500">Tutor tahu kapan kamu butuh bantuan.</p>
                </div>
        </div>
    </div>
</section>

<!-- CARA KERJA -->
<section id="cara-kerja" class="py-16">
    <div class="max-w-5xl mx-auto px-5">
        <div class="text-center max-w-xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-extrabold">Mulai dalam 3 langkah</h2>
        </div>
        <div class="mt-10 grid md:grid-cols-3 gap-5">
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full grad text-white grid place-items-center text-xl font-extrabold">1</div>
                    <h3 class="mt-4 text-lg font-bold">Daftar</h3>
                    <p class="mt-1 text-slate-500">Pilih peran Pelajar atau Tutor.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-violet-500 text-white grid place-items-center text-xl font-extrabold">2</div>
                    <h3 class="mt-4 text-lg font-bold">Pilih kelas</h3>
                    <p class="mt-1 text-slate-500">Cari di katalog, lalu gabung.</p>
                </div>
                <div class="lift bg-white rounded-3xl p-6 border border-indigo-50 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500 text-white grid place-items-center text-xl font-extrabold">3</div>
                    <h3 class="mt-4 text-lg font-bold">Belajar dan naik level</h3>
                    <p class="mt-1 text-slate-500">Kumpulkan XP di setiap aktivitas.</p>
                </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-16">
    <div class="max-w-3xl mx-auto px-5">
        <div class="text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold">Pertanyaan umum</h2>
        </div>
        <div class="mt-10 space-y-3">
                <details class="group bg-white rounded-2xl border border-indigo-50 p-5 hover:border-indigo-200 hover:shadow-md transition">
                    <summary class="flex items-center justify-between gap-4 font-bold">
                        <span>Apakah Questudy gratis?</span>
                        <i class="fa-solid fa-chevron-down chev text-indigo-500 transition"></i>
                    </summary>
                    <p class="mt-3 text-slate-500">Ya. Daftar, gabung kelas, dan belajar tanpa biaya.</p>
                </details>
                <details class="group bg-white rounded-2xl border border-indigo-50 p-5 hover:border-indigo-200 hover:shadow-md transition">
                    <summary class="flex items-center justify-between gap-4 font-bold">
                        <span>Siapa yang bisa jadi tutor?</span>
                        <i class="fa-solid fa-chevron-down chev text-indigo-500 transition"></i>
                    </summary>
                    <p class="mt-3 text-slate-500">Siapa pun yang punya keahlian. Daftar sebagai Tutor, isi profil dan portofolio, lalu tunggu persetujuan admin.</p>
                </details>
                <details class="group bg-white rounded-2xl border border-indigo-50 p-5 hover:border-indigo-200 hover:shadow-md transition">
                    <summary class="flex items-center justify-between gap-4 font-bold">
                        <span>Bagaimana cara gabung kelas?</span>
                        <i class="fa-solid fa-chevron-down chev text-indigo-500 transition"></i>
                    </summary>
                    <p class="mt-3 text-slate-500">Buka Katalog Kelas, pilih kelas, lalu klik Gabung. Bisa juga memakai kode kelas.</p>
                </details>
                <details class="group bg-white rounded-2xl border border-indigo-50 p-5 hover:border-indigo-200 hover:shadow-md transition">
                    <summary class="flex items-center justify-between gap-4 font-bold">
                        <span>Apa itu XP dan level?</span>
                        <i class="fa-solid fa-chevron-down chev text-indigo-500 transition"></i>
                    </summary>
                    <p class="mt-3 text-slate-500">Poin dari aktivitas belajar seperti membaca materi, mengumpulkan tugas, dan kuis. Makin banyak XP, makin tinggi levelmu.</p>
                </details>
                <details class="group bg-white rounded-2xl border border-indigo-50 p-5 hover:border-indigo-200 hover:shadow-md transition">
                    <summary class="flex items-center justify-between gap-4 font-bold">
                        <span>Bisa jadi tutor sekaligus pelajar?</span>
                        <i class="fa-solid fa-chevron-down chev text-indigo-500 transition"></i>
                    </summary>
                    <p class="mt-3 text-slate-500">Satu akun untuk satu peran. Kalau butuh keduanya, daftar dengan email berbeda.</p>
                </details>
                <details class="group bg-white rounded-2xl border border-indigo-50 p-5 hover:border-indigo-200 hover:shadow-md transition">
                    <summary class="flex items-center justify-between gap-4 font-bold">
                        <span>Bagaimana jika kelas penuh?</span>
                        <i class="fa-solid fa-chevron-down chev text-indigo-500 transition"></i>
                    </summary>
                    <p class="mt-3 text-slate-500">Setiap kelas punya kuota. Pilih kelas lain atau coba lagi nanti.</p>
                </details>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16">
    <div class="max-w-5xl mx-auto px-5">
        <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-indigo-600 via-indigo-500 to-sky-400 text-white text-center p-10 md:p-14">
            <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/10"></div>
            <h2 class="relative text-3xl md:text-4xl font-extrabold">Siap mulai belajar?</h2>
            <p class="relative mt-2 text-white/85">Gabung sekarang dan raih XP pertamamu.</p>
            <?php if (!$isLoggedIn): ?>
                <a href="<?= base_url('auth/register.php'); ?>" class="relative inline-block mt-6 px-8 py-4 rounded-full bg-white text-indigo-600 font-bold hover:scale-105 hover:shadow-xl transition">Daftar gratis</a>
            <?php else: ?>
                <a href="<?= base_url($dashboardUrl); ?>" class="relative inline-block mt-6 px-8 py-4 rounded-full bg-white text-indigo-600 font-bold hover:scale-105 hover:shadow-xl transition">Ke dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="py-8 text-center text-sm text-slate-400">&copy; <?= date('Y'); ?> Questudy</footer>

</body>
</html>
