<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Gamifikasi & Leaderboard — Questudy';
$userId = $_SESSION['id_user'];

// 1. Data User Saat Ini
$stmtU = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmtU->execute([$userId]);
$user = $stmtU->fetch();

$currentXP = (int)$user['xp'];
$currentLevel = (int)$user['level'];
$streak = (int)$user['streak_harian'];

$xpNextLevel = $currentLevel * 100;
$xpCurrentLevelBase = ($currentLevel - 1) * 100;
$xpProgressInLevel = max(0, $currentXP - $xpCurrentLevelBase);
$progressPercent = min(100, round(($xpProgressInLevel / 100) * 100));

// 2. Leaderboard Seluruh Pelajar
$stmtLead = $conn->query("
    SELECT id_user, nama, foto_profil, xp, level, streak_harian
    FROM users 
    WHERE role = 'pelajar' AND status_akun = 'aktif'
    ORDER BY xp DESC, streak_harian DESC, id_user ASC
");
$leaderboard = $stmtLead->fetchAll();

// 3. Semua Badges dan Status Kepemilikan User
$stmtBadges = $conn->prepare("
    SELECT b.*, ub.diperoleh_pada
    FROM badges b
    LEFT JOIN user_badges ub ON b.id_badge = ub.id_badge AND ub.id_pelajar = ?
    ORDER BY b.syarat_xp ASC
");
$stmtBadges->execute([$userId]);
$allBadges = $stmtBadges->fetchAll();

// 4. Daftar Aturan Gamifikasi
$stmtAturan = $conn->query("SELECT * FROM gamifikasi_aturan ORDER BY xp_didapat ASC");
$aturanList = $stmtAturan->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 03) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Gamifikasi & Peringkat</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Pusat Gamifikasi & Prestasi</h1>
            <p class="text-xs text-slate-500">Kumpulkan XP dari aktivitas belajar mandiri dan kelas untuk menaiki tangga reputasi leaderboard.</p>
        </div>
    </div>
</div>

<!-- KARTU XP & LEVEL UTAMA (Amber #F59E0B) -->
<div class="bg-gradient-to-r from-amber-500 via-amber-600 to-[#F59E0B] rounded-2xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden mb-6">
    <div class="absolute -right-10 -bottom-10 opacity-20 text-9xl">
        <i class="fa-solid fa-trophy"></i>
    </div>

    <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
        <!-- Level & XP -->
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-xs font-bold text-amber-100">
                <i class="fa-solid fa-bolt"></i>
                <span>Status Reputasi Belajar</span>
            </div>
            <h2 class="text-4xl font-black">Level <?= $currentLevel; ?></h2>
            <div class="text-sm font-semibold text-amber-100">
                Total Perolehan: <span class="text-white font-extrabold text-base"><?= number_format($currentXP); ?> XP</span>
            </div>
        </div>

        <!-- Progress Bar ke Level Selanjutnya -->
        <div class="md:col-span-2 space-y-3 bg-white/10 backdrop-blur-md p-5 rounded-xl border border-white/20">
            <div class="flex items-center justify-between text-xs font-bold text-amber-100">
                <span>Progress Menuju Level <?= $currentLevel + 1; ?></span>
                <span><?= $progressPercent; ?>% (<?= $xpProgressInLevel; ?>/100 XP)</span>
            </div>
            <div class="w-full bg-black/20 rounded-full h-3.5 overflow-hidden p-0.5">
                <div class="bg-white h-full rounded-full transition-all duration-500 shadow" style="width: <?= $progressPercent; ?>%"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-amber-100/90 font-medium">
                <span>Level <?= $currentLevel; ?> (<?= $xpCurrentLevelBase; ?> XP)</span>
                <span>Butuh <?= 100 - $xpProgressInLevel; ?> XP lagi untuk Level <?= $currentLevel + 1; ?></span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- KOLOM KIRI (2 Kolom): LEADERBOARD KELAS & KOLEKSI BADGES -->
    <div class="lg:col-span-2 space-y-6">

        <!-- LEADERBOARD PELAJAR -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-ranking-star text-[#F59E0B] text-lg"></i>
                    <h3 class="text-base font-bold text-slate-800">Papan Peringkat Pelajar (Leaderboard)</h3>
                </div>
                <span class="text-xs font-semibold text-slate-500"><?= count($leaderboard); ?> Siswa Terdaftar</span>
            </div>

            <div class="space-y-2.5">
                <?php foreach ($leaderboard as $idx => $lead): ?>
                    <?php 
                        $rank = $idx + 1;
                        $isMe = ($lead['id_user'] === $userId);

                        // Medali untuk 3 Teratas
                        $rankBadge = [
                            1 => '🥇',
                            2 => '🥈',
                            3 => '🥉',
                        ][$rank] ?? '#' . $rank;
                    ?>
                    <div class="flex items-center justify-between p-3.5 rounded-xl border transition <?= $isMe ? 'bg-blue-50/80 border-[#0066CC] shadow-sm' : 'bg-slate-50/60 border-slate-100 hover:bg-slate-50'; ?>">
                        <div class="flex items-center gap-3">
                            <span class="w-8 text-center font-black text-sm <?= ($rank <= 3) ? 'text-lg' : 'text-slate-400'; ?>">
                                <?= $rankBadge; ?>
                            </span>
                            <img src="<?= base_url('uploads/profil/' . e($lead['foto_profil'])); ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($lead['nama']); ?>&background=0066CC&color=fff';">
                            <div>
                                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <span><?= e($lead['nama']); ?></span>
                                    <?php if ($isMe): ?>
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-[#0066CC] text-white">Anda</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2">
                                    <span class="text-amber-600 font-bold">Level <?= (int)$lead['level']; ?></span>
                                    <span>&bull;</span>
                                    <span class="text-orange-600 font-semibold"><i class="fa-solid fa-fire mr-0.5"></i><?= (int)$lead['streak_harian']; ?> hari</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-sm font-extrabold text-[#0066CC]"><?= number_format($lead['xp']); ?></span>
                            <span class="text-[10px] text-slate-400 block font-semibold">XP</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- GRID KOLEKSI 5 BADGES -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-award text-emerald-500 text-lg"></i>
                    <h3 class="text-base font-bold text-slate-800">Koleksi Lencana Prestasi (Badges)</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($allBadges as $badge): ?>
                    <?php 
                        $isUnlocked = !empty($badge['diperoleh_pada']);
                    ?>
                    <div class="p-4 rounded-xl border text-center transition flex flex-col justify-between <?= $isUnlocked ? 'bg-amber-50/40 border-amber-300 shadow-sm' : 'bg-slate-50 border-slate-200 opacity-60'; ?>">
                        <div>
                            <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center text-2xl mb-3 <?= $isUnlocked ? 'bg-amber-100 border border-amber-300 shadow-inner' : 'bg-slate-200 text-slate-400'; ?>">
                                <?php if ($isUnlocked): ?>
                                    <span><?= $badge['ikon']; ?></span>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock"></i>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800"><?= e($badge['nama_badge']); ?></h4>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2"><?= e($badge['deskripsi']); ?></p>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-200/60">
                            <?php if ($isUnlocked): ?>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full inline-block">
                                    <i class="fa-solid fa-check mr-0.5"></i> Dibuka <?= date('d M Y', strtotime($badge['diperoleh_pada'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full inline-block">
                                    Syarat: <?= (int)$badge['syarat_xp']; ?> XP
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- KOLOM KANAN (1 Kolom): ATURAN PEROLEHAN XP -->
    <div class="space-y-6">

        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <i class="fa-solid fa-circle-question text-[#0066CC]"></i>
                <h3 class="text-sm font-bold text-slate-800">Cara Memperoleh XP</h3>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                Setiap kontribusi belajar di Questudy akan dihadiahi poin pengalaman (XP) otomatis:
            </p>

            <div class="space-y-3">
                <?php foreach ($aturanList as $at): ?>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 block"><?= e($at['keterangan']); ?></span>
                            <span class="text-[10px] text-slate-400 font-mono"><?= e($at['aksi']); ?></span>
                        </div>
                        <span class="text-xs font-black text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg flex-shrink-0">
                            +<?= (int)$at['xp_didapat']; ?> XP
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
