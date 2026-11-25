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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        indigo:{50:'#F1F7FD',100:'#E6F1FA',200:'#C9E0F5',300:'#A3C8EE',400:'#7DB1E4',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0',800:'#264F82',900:'#1F3A5A'},blue:{50:'#F1F7FD',100:'#E6F1FA',200:'#C9E0F5',300:'#A3C8EE',400:'#7DB1E4',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0',800:'#264F82',900:'#1F3A5A'},sky:{50:'#F2F9FE',100:'#E1F0FC',200:'#BFDFF7',300:'#93C8F0',400:'#6BB0E7',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0'},violet:{50:'#FAF3FE',100:'#F4E4FC',200:'#E7BEF8',300:'#D9A0F1',400:'#C67FE6',500:'#AE63D6',600:'#944FBA',700:'#783F96'},amber:{50:'#FEFDEB',100:'#FBF8CC',200:'#F6F1A4',300:'#EDE986',400:'#E1DC5E',500:'#C8C23A',600:'#8F8A18',700:'#6F6A12',800:'#55510F',900:'#3F3C0C',gold:'#F59E0B'},emerald:{50:'#F7F9E8',100:'#EEF2CC',200:'#DFE79F',300:'#CCD777',400:'#B9C255',500:'#9DA83C',600:'#6E7A22',700:'#566018',800:'#434B13',900:'#33390F',fresh:'#10B981'},pink:{50:'#FEF1F6',100:'#FDE3EE',200:'#FAC5DC',300:'#F79BC0',400:'#F47BAE',500:'#F2619C',600:'#D84582',700:'#B2336A'},rose:{50:'#FEF1F6',100:'#FDE3EE',200:'#FAC5DC',300:'#F79BC0',400:'#F47BAE',500:'#F2619C',600:'#D84582',700:'#B2336A'},
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
                        crimson: {
                            danger: '#EF4444',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
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
    
        /* ===== Tema Questudy: warna cerah, satu font, skala teks konsisten ===== */
        html { scroll-behavior: smooth; }
        body, input, button, select, textarea { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        body { font-size: 15px; line-height: 1.6; color: #1E293B; background: linear-gradient(135deg, #E6F1FA 0%, #F4F9FD 55%, #F3F7E4 100%) fixed !important; }
        .text-\[10px\], .text-\[11px\], .text-xs { font-size: 13px !important; line-height: 1.5 !important; }
        .text-sm { font-size: 14.5px !important; }
        .text-base { font-size: 16px !important; }
        .text-lg { font-size: 18px !important; }
        .text-xl { font-size: 20px !important; }
        .text-2xl { font-size: 26px !important; line-height: 1.25 !important; }
        .text-3xl { font-size: 30px !important; line-height: 1.2 !important; }
        .font-black, .font-extrabold { font-weight: 800 !important; }
        .uppercase { text-transform: none !important; letter-spacing: normal !important; }
        .tracking-tight, .tracking-wide, .tracking-wider { letter-spacing: normal !important; }
        .animate-pulse, .animate-bounce { animation: none !important; }

        /* Warna utama: gradasi biru ke ungu */
        .bg-\[\#0066CC\] { background-color: #3D7BBF !important; background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF) !important; color: #fff !important; }
        .hover\:bg-\[\#0052A3\]:hover { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 8px 18px rgba(61, 123, 191, .35); }
        .text-\[\#0066CC\] { color: #3D7BBF !important; }
        .border-\[\#0066CC\] { border-color: #3D7BBF !important; }
        .focus\:ring-\[\#0066CC\]:focus { --tw-ring-color: #BED8F1 !important; }
        .bg-\[\#EFF6FF\] { background-color: #E6F1FA !important; }

        /* Kartu dan efek hover */
        main .bg-white.rounded-xl { border-radius: 20px !important; border-color: rgba(190, 216, 241, .35) !important; box-shadow: 0 4px 14px rgba(61, 123, 191, .08) !important; transition: transform .2s ease, box-shadow .2s ease; }
        main .grid > .bg-white.rounded-xl:hover { transform: translateY(-4px); box-shadow: 0 14px 32px rgba(61, 123, 191, .18) !important; }
        main button, main a.rounded-xl, main a.rounded-lg { transition: all .18s ease; }
        main button.rounded-xl, main a.rounded-xl { border-radius: 14px !important; }
        main table tbody tr { transition: background .15s ease; }
        main table tbody tr:hover { background: #EEF5FC; }
        main input, main select, main textarea { transition: border-color .15s ease, box-shadow .15s ease; }
        main input:hover, main select:hover, main textarea:hover { border-color: #BED8F1; }

        .lift { transition: transform .2s ease, box-shadow .2s ease; }
        .lift:hover { transform: translateY(-4px); box-shadow: 0 14px 32px rgba(61, 123, 191, .18); }

        /* Sidebar */
        #mainSidebar { background: rgba(255, 255, 255, .85) !important; backdrop-filter: blur(8px); border-right: 1px solid rgba(190, 216, 241, .35) !important; }
        #mainSidebar a { transition: all .18s ease; }
        #mainSidebar a.bg-\[\#EFF6FF\], #mainSidebar a.bg-blue-50 { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF) !important; color: #fff !important; border-left-width: 0 !important; box-shadow: 0 6px 16px rgba(61, 123, 191, .3); }
        #mainSidebar a.bg-\[\#EFF6FF\] i, #mainSidebar a.bg-\[\#EFF6FF\] span, #mainSidebar a.bg-blue-50 i, #mainSidebar a.bg-blue-50 span { color: #fff !important; }
        #mainSidebar a:not(.bg-\[\#EFF6FF\]):not(.bg-blue-50):hover { background: #E6F1FA !important; transform: translateX(3px); }
    
    
        /* ===== Animasi halus dan responsif ===== */
        @keyframes muncul { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @keyframes naikBatang { from { transform: scaleY(0); } to { transform: scaleY(1); } }
        @keyframes isiProgres { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        main > * { animation: muncul .45s ease backwards; }
        main .grid > * { animation: muncul .5s ease backwards; }
        main .grid > *:nth-child(1) { animation-delay: .04s; } main .grid > *:nth-child(2) { animation-delay: .10s; }
        main .grid > *:nth-child(3) { animation-delay: .16s; } main .grid > *:nth-child(4) { animation-delay: .22s; }
        main .grid > *:nth-child(5) { animation-delay: .28s; } main .grid > *:nth-child(6) { animation-delay: .34s; }
        .batang-naik { transform-origin: bottom; animation: naikBatang .9s cubic-bezier(.2, .8, .2, 1) backwards; }
        .group:nth-child(1) .batang-naik { animation-delay: .10s; } .group:nth-child(2) .batang-naik { animation-delay: .18s; }
        .group:nth-child(3) .batang-naik { animation-delay: .26s; } .group:nth-child(4) .batang-naik { animation-delay: .34s; }
        .group:nth-child(5) .batang-naik { animation-delay: .42s; } .group:nth-child(6) .batang-naik { animation-delay: .50s; }
        .group:nth-child(7) .batang-naik { animation-delay: .58s; }
        .bar-isi { transform-origin: left; animation: isiProgres 1s cubic-bezier(.2, .8, .2, 1) .2s backwards; }
        img, svg { max-width: 100%; }
        @media (max-width: 640px) {
            .text-2xl { font-size: 22px !important; }
            .text-3xl { font-size: 26px !important; }
            main table { display: block; overflow-x: auto; white-space: nowrap; }
        }
        @media (hover: none) {
            .lift:hover, main .grid > .bg-white.rounded-xl:hover { transform: none !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            main > *, main .grid > *, .batang-naik, .bar-isi { animation: none !important; }
        }
    </style>
    <?= maskotGayaCss(); ?>
</head>
<body class="bg-[#F4F6F9] text-slate-800 min-h-screen flex flex-col antialiased">

    <!-- TOP NAVBAR (#0F294A) -->
    <header class="bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-400 text-white sticky top-0 z-50 shadow-lg shadow-indigo-200/60">
        <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand / Logo -->
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" class="lg:hidden text-white hover:text-white p-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <a href="<?= base_url(); ?>" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-[#0066CC] to-blue-400 flex items-center justify-center text-white shadow-inner font-bold text-lg">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-extrabold tracking-tight text-white group-hover:text-blue-100 transition max-[420px]:hidden">Questudy</span>
                        </div>
                        <p class="text-[10px] text-slate-300 tracking-wide hidden sm:block">Belajar lebih konsisten</p>
                    </div>
                </a>
            </div>

            <!-- User Info & Action Icons -->
            <div class="flex items-center gap-2 sm:gap-4">
                <?php if ($user_role === 'pelajar'): ?>
                    <!-- Gamification XP & Level Pill for Students -->
                    <?php $pctXpHeader = ((int)$user_xp) % 100; ?>
                    <a href="<?= base_url('pelajar/gamifikasi.php'); ?>" title="Level <?= (int)$user_level; ?> · <?= number_format($user_xp); ?> XP" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-white/20 border border-white/30 hover:bg-white/30 hover:-translate-y-0.5 transition whitespace-nowrap">
                        <span class="w-7 h-7 rounded-full bg-amber-300 text-amber-800 grid place-items-center text-xs font-extrabold shadow-sm"><?= (int)$user_level; ?></span>
                        <span class="leading-tight">
                            <span class="block text-xs font-bold"><span data-hitung="<?= (int)$user_xp; ?>"><?= number_format($user_xp); ?></span> XP</span>
                            <span class="block w-14 sm:w-20 h-1 rounded-full bg-white/30 overflow-hidden"><span class="bar-isi block h-full rounded-full bg-amber-300" style="width: <?= $pctXpHeader; ?>%"></span></span>
                        </span>
                    </a>
                <?php endif; ?>

                <?php if ($user_id): ?>
                    <!-- User Profile Dropdown Pill -->
                    <div class="flex items-center gap-2.5 pl-1 sm:pl-3 sm:border-l sm:border-white/30">
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
                                        elseif ($user_role === 'pengajar') echo '<span class="text-sky-300">Tutor</span>';
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

                        <a href="<?= base_url('pelajar/katalog.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'katalog.php') ? 'bg-blue-50 text-[#0066CC]' : 'text-slate-600 hover:bg-slate-50'; ?>">
                            <i class="fa-solid fa-compass w-5 text-center text-base text-slate-400"></i>
                            <span>Katalog Kelas</span>
                        </a>

                        <a href="<?= base_url('pelajar/tugas.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'tugas.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-list-check w-5 text-center text-base <?= ($currentPage === 'tugas.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Tugas</span>
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

                        <a href="<?= base_url('laporan.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'laporan.php') ? 'bg-blue-50 text-[#0066CC]' : 'text-slate-600 hover:bg-slate-50'; ?>">
                            <i class="fa-solid fa-flag w-5 text-center text-base text-slate-400"></i>
                            <span>Laporkan Masalah</span>
                        </a>

                    <?php elseif ($user_role === 'pengajar'): ?>
                        <!-- MENU PENGAJAR -->
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Manajemen Akademik</div>

                        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'dashboard.php') ? 'bg-[#EFF6FF] text-[#0066CC] border-l-4 border-[#0066CC]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                            <i class="fa-solid fa-chart-line w-5 text-center text-base <?= ($currentPage === 'dashboard.php') ? 'text-[#0066CC]' : 'text-slate-400'; ?>"></i>
                            <span>Dashboard Tutor</span>
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

                        <a href="<?= base_url('laporan.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'laporan.php') ? 'bg-blue-50 text-[#0066CC]' : 'text-slate-600 hover:bg-slate-50'; ?>">
                            <i class="fa-solid fa-flag w-5 text-center text-base text-slate-400"></i>
                            <span>Laporkan Masalah</span>
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

                        <a href="<?= base_url('admin/laporan.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'laporan.php') ? 'bg-blue-50 text-[#0066CC]' : 'text-slate-600 hover:bg-slate-50'; ?>">
                            <i class="fa-solid fa-flag w-5 text-center text-base text-slate-400"></i>
                            <span>Laporan Pengguna</span>
                        </a>

                        <a href="<?= base_url('admin/pengaturan.php'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= ($currentPage === 'pengaturan.php') ? 'bg-blue-50 text-[#0066CC]' : 'text-slate-600 hover:bg-slate-50'; ?>">
                            <i class="fa-solid fa-sliders w-5 text-center text-base text-slate-400"></i>
                            <span>Kuota dan Batas</span>
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
