<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/config/koneksi.php';

// Pastikan sesi aktif untuk header
$user_id = $_SESSION['id_user'] ?? null;
$user_nama = $_SESSION['nama'] ?? 'Tamu';
$user_role = $_SESSION['role'] ?? 'guest';
$user_foto = $_SESSION['foto_profil'] ?? 'default.png';

// Ambil info XP & Level realtime jika role adalah pelajar
$user_xp = $_SESSION['xp'] ?? 0;
$user_level = $_SESSION['level'] ?? 1;
if ($user_role === 'pelajar' && $user_id) {
    $stmtUserLive = $conn->prepare("SELECT xp, level, foto_profil FROM users WHERE id_user = ?");
    $stmtUserLive->execute([$user_id]);
    if ($u = $stmtUserLive->fetch()) {
        $user_xp = (int)$u['xp'];
        $user_level = (int)$u['level'];
        $user_foto = $u['foto_profil'] ?: 'default.png';
        $_SESSION['xp'] = $user_xp;
        $_SESSION['level'] = $user_level;
        $_SESSION['foto_profil'] = $user_foto;
    }
}

// Menentukan menu aktif
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

$pageTitle = $pageTitle ?? 'Questudy — LMS Gamifikasi & Early Warning';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            DEFAULT: '#0F294A',
                            800: '#14355d',
                            900: '#0F294A',
                        },
                        brand: {
                            DEFAULT: '#0066CC',
                            hover: '#0052A3',
                            soft: '#EFF6FF',
                        },
                        amber: {
                            gold: '#F59E0B',
                        },
                        emerald: {
                            fresh: '#10B981',
                        },
                        crimson: {
                            danger: '#EF4444',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', Inter, sans-serif;
            background-color: #F4F6F9;
        }
        .perspective-1000 {
            perspective: 1000px;
        }
        .transform-style-preserve-3d {
            transform-style: preserve-3d;
        }
        .backface-hidden {
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        .rotate-y-180 {
            transform: rotateY(180deg);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
    </style>
</head>
<body class="bg-[#F4F6F9] text-slate-800 min-h-screen flex flex-col antialiased">

    <!-- TOP NAVBAR (#0F294A) -->
    <header class="bg-[#0F294A] text-white sticky top-0 z-50 shadow-md">
        <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand / Logo -->
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" class="lg:hidden text-slate-300 hover:text-white p-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <a href="<?= base_url(); ?>" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-[#0066CC] to-blue-400 flex items-center justify-center text-white shadow-inner font-bold text-lg">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-extrabold tracking-tight text-white group-hover:text-blue-200 transition">Questudy</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-500/30 text-blue-300 border border-blue-400/30">SDGs 4</span>
                        </div>
                        <p class="text-[10px] text-slate-300 tracking-wide hidden sm:block">Gamified LMS & Early Warning</p>
                    </div>
                </a>
            </div>

            <!-- User Info & Action Icons -->
            <div class="flex items-center gap-3 sm:gap-4">
                <?php if ($user_role === 'pelajar'): ?>
                    <!-- Gamification XP & Level Pill for Students -->
                    <a href="<?= base_url('pelajar/gamifikasi.php'); ?>" class="flex items-center gap-2 bg-[#14355d] border border-blue-400/20 px-3 py-1.5 rounded-full hover:bg-blue-900/60 transition shadow-sm" title="Lihat Peringkat & Koleksi Badge">
                        <div class="flex items-center text-amber-400 font-bold text-xs">
                            <i class="fa-solid fa-bolt mr-1"></i>
                            <span>Lvl <?= $user_level; ?></span>
                        </div>
                        <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                        <div class="flex items-center text-xs font-semibold text-amber-300">
                            <span><?= number_format($user_xp); ?> XP</span>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($user_id): ?>
                    <!-- User Profile Dropdown Pill -->
                    <div class="flex items-center gap-2.5 pl-2 sm:pl-3 border-l border-slate-700">
                        <a href="<?= base_url('profil.php'); ?>" class="flex items-center gap-2 text-left group">
                            <?php 
                                $avatarSrc = base_url('uploads/profil/' . e($user_foto));
                            ?>
                            <img src="<?= $avatarSrc; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover ring-2 ring-blue-500/40 group-hover:ring-blue-400 transition" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_nama); ?>&background=0066CC&color=fff';">
                            <div class="hidden md:block">
                                <div class="text-xs font-bold text-slate-100 group-hover:text-blue-300 transition truncate max-w-[130px]"><?= e($user_nama); ?></div>
                                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-300">
                                    <?php 
                                        if ($user_role === 'admin') echo '<span class="text-purple-300">Administrator</span>';
                                        elseif ($user_role === 'pengajar') echo '<span class="text-sky-300">Pengajar / Tutor</span>';
                                        else echo '<span class="text-emerald-300">Pelajar Aktif</span>';
                                    ?>
                                </div>
                            </div>
                        </a>
                        <!-- Logout Button -->
                        <a href="<?= base_url('auth/logout.php'); ?>" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="p-2 text-slate-300 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition" title="Logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('auth/login.php'); ?>" class="text-xs font-semibold bg-[#0066CC] hover:bg-[#0052A3] text-white px-4 py-2 rounded-lg transition shadow-sm">
                        Masuk
                    </a>
                    <a href="<?= base_url('auth/register.php'); ?>" class="text-xs font-semibold bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg transition border border-white/20 hidden sm:inline-block">
                        Daftar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- MAIN WRAPPER (SIDEBAR + CONTENT) -->
    <div class="flex-1 flex overflow-hidden">
        
        <?php if ($user_id): ?>
        <!-- SIDEBAR KIRI (#FFFFFF, border-r #E2E8F0) -->
        <aside id="mainSidebar" class="w-64 bg-white border-r border-[#E2E8F0] flex-shrink-0 flex flex-col justify-between hidden lg:flex transition-all duration-200 z-40">
            <div class="py-5 px-3 space-y-6 overflow-y-auto max-h-[calc(100vh-4rem)]">
                
                <!-- Quick User Status Card -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center gap-3">
                    <img src="<?= base_url('uploads/profil/' . e($user_foto)); ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_nama); ?>&background=0066CC&color=fff';">
                    <div class="truncate">
                        <p class="text-xs font-bold text-slate-800 truncate"><?= e($user_nama); ?></p>
                        <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded 
                            <?php 
                                if ($user_role === 'admin') echo 'bg-purple-100 text-purple-700';
                                elseif ($user_role === 'pengajar') echo 'bg-blue-100 text-[#0066CC]';
                                else echo 'bg-emerald-100 text-emerald-700';
                            ?>">
                            <?= ucfirst($user_role); ?>
                        </span>
                    </div>
                </div>

                <!-- Navigasi Utama Sesuai Role -->
                <nav class="space-y-1">
                    <?php if ($user_role === 'pelajar'): ?>
                        <!-- MENU PELAJAR -->
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Menu Belajar</div>
                        
                        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'dashboard.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-gauge-high w-5 text-center text-base <?= ($currentPage === 'dashboard.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="<?= base_url('pelajar/kelas.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'kelas.php' || $currentPage === 'materi.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-chalkboard-user w-5 text-center text-base <?= ($currentPage === 'kelas.php' || $currentPage === 'materi.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Kelas & Materi</span>
                        </a>

                        <a href="<?= base_url('pelajar/tugas.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'tugas.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-list-check w-5 text-center text-base <?= ($currentPage === 'tugas.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Tugas Kuliah</span>
                        </a>

                        <a href="<?= base_url('pelajar/kuis.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'kuis.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-circle-question w-5 text-center text-base <?= ($currentPage === 'kuis.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Kuis Online</span>
                        </a>

                        <a href="<?= base_url('pelajar/rapor.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'rapor.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-award w-5 text-center text-base <?= ($currentPage === 'rapor.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Rapor Nilai</span>
                        </a>

                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 pt-4 mb-2">Pemberdayaan Diri</div>

                        <a href="<?= base_url('pelajar/ruang_belajar.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'ruang_belajar.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-brain w-5 text-center text-base <?= ($currentPage === 'ruang_belajar.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Ruang Belajar</span>
                        </a>

                        <a href="<?= base_url('pelajar/gamifikasi.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'gamifikasi.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-trophy w-5 text-center text-base <?= ($currentPage === 'gamifikasi.php') ? 'text-amber-500' : 'text-amber-500/80'; ?>"></i>
                            <span>Gamifikasi & XP</span>
                        </a>

                        <a href="<?= base_url('pelajar/booking_tutor.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'booking_tutor.php' || $currentPage === 'rating_ulasan.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-handshake-angle w-5 text-center text-base <?= ($currentPage === 'booking_tutor.php' || $currentPage === 'rating_ulasan.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Mentoring Tutor</span>
                        </a>

                    <?php elseif ($user_role === 'pengajar'): ?>
                        <!-- MENU PENGAJAR -->
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Manajemen Akademik</div>

                        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'dashboard.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-chart-line w-5 text-center text-base <?= ($currentPage === 'dashboard.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Dashboard Pengajar</span>
                        </a>

                        <a href="<?= base_url('pengajar/pemantauan.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'pemantauan.php') ? 'bg-red-50 text-red-600 border-l-4 border-red-500' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-triangle-exclamation w-5 text-center text-base text-red-500"></i>
                            <div class="flex items-center justify-between w-full">
                                <span>Peringatan Dini</span>
                                <span class="bg-red-100 text-red-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full">EWS</span>
                            </div>
                        </a>

                        <a href="<?= base_url('pengajar/kelas.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'kelas.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-school w-5 text-center text-base <?= ($currentPage === 'kelas.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Kelola Kelas</span>
                        </a>

                        <a href="<?= base_url('pengajar/materi.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'materi.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-book-bookmark w-5 text-center text-base <?= ($currentPage === 'materi.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Materi & Forum</span>
                        </a>

                        <a href="<?= base_url('pengajar/tugas.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'tugas.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-clipboard-check w-5 text-center text-base <?= ($currentPage === 'tugas.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Penilaian Tugas</span>
                        </a>

                        <a href="<?= base_url('pengajar/kuis.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'kuis.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-feather-pointed w-5 text-center text-base <?= ($currentPage === 'kuis.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Kelola Kuis</span>
                        </a>

                        <a href="<?= base_url('pengajar/rapor.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'rapor.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-square-poll-vertical w-5 text-center text-base <?= ($currentPage === 'rapor.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Rapor Kelas</span>
                        </a>

                        <a href="<?= base_url('pengajar/booking.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'booking.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-calendar-check w-5 text-center text-base <?= ($currentPage === 'booking.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Permintaan Mentoring</span>
                        </a>

                    <?php elseif ($user_role === 'admin'): ?>
                        <!-- MENU ADMIN -->
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Panel Administrasi</div>

                        <a href="<?= base_url('admin/dashboard.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'dashboard.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-chart-pie w-5 text-center text-base <?= ($currentPage === 'dashboard.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Dashboard Admin</span>
                        </a>

                        <a href="<?= base_url('admin/pengguna.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'pengguna.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-users-gear w-5 text-center text-base <?= ($currentPage === 'pengguna.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Kelola Pengguna</span>
                        </a>

                        <a href="<?= base_url('admin/aturan_moderasi.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'aturan_moderasi.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-sliders w-5 text-center text-base <?= ($currentPage === 'aturan_moderasi.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Aturan & Moderasi</span>
                        </a>
                    <?php endif; ?>

                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 pt-4 mb-2">Pengaturan Akun</div>

                    <a href="<?= base_url('profil.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'profil.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                        <i class="fa-solid fa-user-gear w-5 text-center text-base <?= ($currentPage === 'profil.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                        <span>Edit Profil</span>
                    </a>

                    <a href="<?= base_url('auth/logout.php'); ?>" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-red-600 hover:bg-red-50 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center text-base text-red-500"></i>
                        <span>Keluar Akun</span>
                    </a>
                </nav>
            </div>

            <!-- SDGs 4 Footer Banner in Sidebar -->
            <div class="p-4 border-t border-slate-200 bg-slate-50/70">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>SDGs 4: Quality Education</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Mendukung pemerataan akses & mutu belajar.</p>
            </div>
        </aside>
        <?php endif; ?>

        <!-- KONTEN UTAMA (Background #F4F6F9) -->
        <main class="flex-1 overflow-y-auto min-h-[calc(100vh-4rem)] p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- TAMPILAN FLASH MESSAGES -->
                <?php if ($flash = getFlash()): ?>
                    <?php
                        $alertStyles = [
                            'success' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-300', 'text' => 'text-emerald-800', 'icon' => 'fa-circle-check text-emerald-500'],
                            'error'   => ['bg' => 'bg-red-50',     'border' => 'border-red-300',     'text' => 'text-red-800',     'icon' => 'fa-circle-xmark text-red-500'],
                            'warning' => ['bg' => 'bg-amber-50',   'border' => 'border-amber-300',   'text' => 'text-amber-800',   'icon' => 'fa-triangle-exclamation text-amber-500'],
                            'info'    => ['bg' => 'bg-blue-50',    'border' => 'border-blue-300',    'text' => 'text-blue-800',    'icon' => 'fa-circle-info text-blue-500'],
                        ];
                        $st = $alertStyles[$flash['type']] ?? $alertStyles['info'];
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-xl border <?= $st['bg']; ?> <?= $st['border']; ?> <?= $st['text']; ?> shadow-sm transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid <?= $st['icon']; ?> text-lg"></i>
                            <span class="text-sm font-medium"><?= e($flash['message']); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 text-sm p-1">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                <?php endif; ?>
