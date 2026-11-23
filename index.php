<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/koneksi.php';

$isLoggedIn = isset($_SESSION['id_user']);
$userRole = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questudy — LMS Gamifikasi, Ruang Belajar Pribadi & Peringatan Dini </title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 900: '#0F294A', 800: '#153660' },
                        brand: { DEFAULT: '#0066CC', hover: '#0052A3', soft: '#EFF6FF' }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-[#F4F6F9] text-slate-800 antialiased font-sans">

    <!-- Top Navigation Bar (#0F294A) -->
    <header class="bg-[#0F294A] text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#0066CC] to-blue-400 flex items-center justify-center text-white shadow-md text-xl font-bold">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xl font-black tracking-tight text-white">Questudy</span>
                    </div>
                    <p class="text-[10px] text-slate-300 tracking-wide hidden sm:block">Belajar lebih konsisten</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <?php if ($isLoggedIn): ?>
                    <?php 
                        $dashUrl = base_url('pelajar/dashboard.php');
                        if ($userRole === 'admin') $dashUrl = base_url('admin/dashboard.php');
                        elseif ($userRole === 'pengajar') $dashUrl = base_url('pengajar/dashboard.php');
                    ?>
                    <a href="<?= $dashUrl; ?>" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Buka Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('auth/login.php'); ?>" class="text-xs font-bold text-slate-200 hover:text-white px-3 py-2 transition">
                        Masuk
                    </a>
                    <a href="<?= base_url('auth/register.php'); ?>" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm shadow-blue-500/20">
                        Daftar Akun
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative bg-gradient-to-b from-[#0F294A] to-[#153660] text-white pt-16 pb-24 px-4 overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#0066CC_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="max-w-5xl mx-auto text-center relative z-10 space-y-6">
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                Belajar Lebih Menyenangkan dengan <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 via-amber-300 to-emerald-300">Gamifikasi & Ruang Belajar Pribadi</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-300 max-w-3xl mx-auto leading-relaxed">
                Questudy adalah platform LMS generasi baru yang menggabungkan mekanisme RPG Gamifikasi (XP, Level, Badges), Ruang Belajar Terfokus (Pomodoro, 3D Flashcards, Planner), dan Sistem Peringatan Dini (Early Warning System) untuk memastikan tidak ada pelajar yang tertinggal.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
                <a href="<?= base_url('auth/login.php'); ?>" class="px-6 py-3 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                    <span>Mulai Belajar Sekarang</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="#fitur" class="px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm border border-white/20 transition">
                    Pelajari Fitur
                </a>
            </div>
        </div>
    </section>

    <!-- 4 FITUR UTAMA QUESTUDY -->
    <section id="fitur" class="py-16 px-4 max-w-7xl mx-auto -mt-12 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Pilar 1 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl font-bold mb-4 border border-amber-200">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-2">Gamifikasi & Motivasi</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Tingkatkan motivasi belajar melalui perolehan XP, kenaikan Level, papan peringkat kelas (Leaderboard), dan 5 koleksi Badge prestisius.
                </p>
            </div>

            <!-- Pilar 2 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#0066CC] flex items-center justify-center text-xl font-bold mb-4 border border-blue-200">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-2">Ruang Belajar Pribadi</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pemberdayaan diri dengan Timer Pomodoro interaktif (25m/5m), Flashcard 3D flip untuk daya ingat, dan Planner target belajar harian.
                </p>
            </div>

            <!-- Pilar 3 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl font-bold mb-4 border border-red-200">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-2">Peringatan Dini (EWS)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Deteksi otomatis pelajar yang berisiko tertinggal berdasarkan keterlambatan tugas, nilai rendah, atau keaktifan, agar pengajar dapat segera memberi tindak lanjut.
                </p>
            </div>

            <!-- Pilar 4 -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-md hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold mb-4 border border-emerald-200">
                    <i class="fa-solid fa-handshake-angle"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-2">Mentoring & Tutor</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Kemudahan booking sesi pendampingan belajar bersama Tutor, tautan Google Meet terintegrasi, dan transparansi ulasan rating bintang.
                </p>
            </div>
        </div>
    </section>

    <!-- ARSITEKTUR KELAS & LMS LENGKAP -->
    <section class="py-12 px-4 max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-sm">
            <div class="max-w-3xl mb-8">
                <div class="text-xs font-bold uppercase tracking-wider text-[#0066CC] mb-1">Fitur Komprehensif</div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800">Dirancang untuk 3 Peran: Pelajar, Tutor & Admin</h2>
                <p class="text-sm text-slate-600 mt-2">
                    Alur pembelajaran terintegrasi dari penyampaian materi interaktif, pengumpulan tugas, kuis online dengan pembatasan waktu, hingga rapor rekap nilai otomatis A–E.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Box Pelajar -->
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-2 font-bold text-slate-800 text-sm mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>Untuk Pelajar</span>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2">
                        <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i> Masuk kelas via kode acak unik</li>
                        <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i> Catatan pribadi & forum diskusi per materi</li>
                        <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i> Kuis interaktif dengan timer mundur & auto-grading</li>
                        <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i> Rapor nilai pribadi & kalkulasi huruf mutu A-E</li>
                    </ul>
                </div>

                <!-- Box Tutor -->
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-2 font-bold text-slate-800 text-sm mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0066CC]"></span>
                        <span>Untuk Tutor</span>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2">
                        <li><i class="fa-solid fa-check text-[#0066CC] mr-1.5"></i> Manajemen kelas & pengumuman</li>
                        <li><i class="fa-solid fa-check text-[#0066CC] mr-1.5"></i> Unggah materi teks, tautan, & PDF</li>
                        <li><i class="fa-solid fa-check text-[#0066CC] mr-1.5"></i> Penilaian tugas & feedback komentar</li>
                        <li><i class="fa-solid fa-check text-[#0066CC] mr-1.5"></i> Panel Early Warning & pencatatan tindak lanjut</li>
                    </ul>
                </div>

                <!-- Box Admin -->
                <div class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center gap-2 font-bold text-slate-800 text-sm mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        <span>Untuk Administrator</span>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2">
                        <li><i class="fa-solid fa-check text-purple-500 mr-1.5"></i> Verifikasi aktivasi pengajar baru</li>
                        <li><i class="fa-solid fa-check text-purple-500 mr-1.5"></i> Manajemen status pengguna & reset password</li>
                        <li><i class="fa-solid fa-check text-purple-500 mr-1.5"></i> Konfigurasi nilai XP & ambang batas peringatan</li>
                        <li><i class="fa-solid fa-check text-purple-500 mr-1.5"></i> Moderasi konten materi & komentar forum</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-[#0F294A] text-slate-300 py-10 px-4 mt-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <span class="text-white font-bold text-sm">Questudy</span> — Platform Pembelajaran
                </div>
            </div>
            <p>&copy; <?= date('Y'); ?> Questudy. Dikembangkan dengan PHP 8 Native, MySQL & Tailwind CSS.</p>
            <div class="flex items-center gap-4">
                <a href="<?= base_url('auth/login.php'); ?>" class="hover:text-white transition">Masuk</a>
                <a href="<?= base_url('auth/register.php'); ?>" class="hover:text-white transition">Daftar</a>
            </div>
        </div>
    </footer>

</body>
</html>
