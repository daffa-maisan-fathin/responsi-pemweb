<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Dashboard Pelajar — Questudy';
$userId = $_SESSION['id_user'];

// 1. Ambil data pelajar terbaru (XP, Level, Streak)
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

$currentXP = (int)$user['xp'];
$currentLevel = (int)$user['level'];
$streak = (int)$user['streak_harian'];

// Perhitungan target XP level selanjutnya (tiap 100 XP naik 1 level)
$xpNextLevel = $currentLevel * 100;
$xpCurrentLevelBase = ($currentLevel - 1) * 100;
$xpProgressInLevel = max(0, $currentXP - $xpCurrentLevelBase);
$progressPercent = min(100, round(($xpProgressInLevel / 100) * 100));

// 2. Total kelas yang diikuti
$stmtKelas = $conn->prepare("SELECT COUNT(*) AS total_kelas FROM anggota_kelas WHERE id_pelajar = ?");
$stmtKelas->execute([$userId]);
$totalKelas = $stmtKelas->fetch()['total_kelas'];

// 3. Progres materi
$stmtMateriTotal = $conn->prepare("
    SELECT COUNT(m.id_materi) AS total 
    FROM materi m 
    JOIN anggota_kelas ak ON m.id_kelas = ak.id_kelas 
    WHERE ak.id_pelajar = ?
");
$stmtMateriTotal->execute([$userId]);
$totalMateriTersedia = (int)$stmtMateriTotal->fetch()['total'];

$stmtMateriSelesai = $conn->prepare("SELECT COUNT(*) AS selesai FROM progres_materi WHERE id_pelajar = ?");
$stmtMateriSelesai->execute([$userId]);
$totalMateriSelesai = (int)$stmtMateriSelesai->fetch()['selesai'];
$progresMateriPersen = ($totalMateriTersedia > 0) ? round(($totalMateriSelesai / $totalMateriTersedia) * 100) : 0;

// 4. Badges yang telah didapat
$stmtBadges = $conn->prepare("
    SELECT b.*, ub.diperoleh_pada 
    FROM user_badges ub 
    JOIN badges b ON ub.id_badge = b.id_badge 
    WHERE ub.id_pelajar = ? 
    ORDER BY b.syarat_xp DESC
");
$stmtBadges->execute([$userId]);
$myBadges = $stmtBadges->fetchAll();

// 5. Tugas terdekat yang belum dikumpulkan
$stmtTugas = $conn->prepare("
    SELECT t.*, k.nama_kelas, pt.status AS status_kumpul, pt.nilai
    FROM tugas t
    JOIN kelas k ON t.id_kelas = k.id_kelas
    JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    LEFT JOIN pengumpulan_tugas pt ON t.id_tugas = pt.id_tugas AND pt.id_pelajar = ?
    WHERE ak.id_pelajar = ?
    ORDER BY t.tenggat_waktu ASC
    LIMIT 4
");
$stmtTugas->execute([$userId, $userId]);
$daftarTugas = $stmtTugas->fetchAll();

// 6. Kuis terdekat
$stmtKuis = $conn->prepare("
    SELECT q.*, k.nama_kelas,
        (SELECT MAX(skor) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS skor_tertinggi,
        (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS jml_percobaan
    FROM kuis q
    JOIN kelas k ON q.id_kelas = k.id_kelas
    JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    WHERE ak.id_pelajar = ?
    ORDER BY q.tenggat_waktu ASC
    LIMIT 3
");
$stmtKuis->execute([$userId, $userId, $userId]);
$daftarKuis = $stmtKuis->fetchAll();

// 7. Pengumuman terbaru dari kelas yang diikuti
$stmtPengumuman = $conn->prepare("
    SELECT p.*, k.nama_kelas, u.nama AS nama_pengajar
    FROM pengumuman p
    JOIN kelas k ON p.id_kelas = k.id_kelas
    JOIN users u ON k.id_pengajar = u.id_user
    JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    WHERE ak.id_pelajar = ?
    ORDER BY p.created_at DESC
    LIMIT 2
");
$stmtPengumuman->execute([$userId]);
$daftarPengumuman = $stmtPengumuman->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <span class="text-slate-800 font-semibold">Dashboard Pelajar</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Semangat Belajar, <?= e($user['nama']); ?>! 👋</h1>
            <p class="text-xs text-slate-500">Pantau progres materi, capaian level XP, dan agenda tugas akademik Anda.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('pelajar/ruang_belajar.php'); ?>" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-stopwatch"></i>
                <span>Buka Ruang Fokus</span>
            </a>
            <a href="<?= base_url('pelajar/kelas.php'); ?>" class="px-4 py-2 rounded-xl bg-white border border-[#E2E8F0] hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                + Gabung Kelas
            </a>
        </div>
    </div>
</div>

<!-- KARTU STATISTIK & GAMIFIKASI UTAMA -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
    
    <!-- Kartu Level & Bar XP (Amber #F59E0B) -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Peringkat & XP</span>
            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center font-bold text-sm border border-amber-200">
                <i class="fa-solid fa-bolt"></i>
            </span>
        </div>
        <div class="flex items-baseline gap-2">
            <div class="text-2xl font-extrabold text-slate-800">Level <?= $currentLevel; ?></div>
            <span class="text-xs font-semibold text-amber-600">(<?= number_format($currentXP); ?> XP)</span>
        </div>
        <!-- Progress Bar ke level selanjutnya -->
        <div class="mt-3">
            <div class="flex justify-between text-[11px] font-semibold text-slate-500 mb-1">
                <span>Progress Level <?= $currentLevel + 1; ?></span>
                <span><?= $progressPercent; ?>%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-400 to-[#F59E0B] h-2.5 rounded-full transition-all duration-500" style="width: <?= $progressPercent; ?>%"></div>
            </div>
            <p class="text-[10px] text-slate-400 mt-1.5"><?= 100 - $xpProgressInLevel; ?> XP lagi untuk naik ke Level <?= $currentLevel + 1; ?></p>
        </div>
    </div>

    <!-- Kartu Streak Harian -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Streak Belajar</span>
            <span class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center font-bold text-sm border border-orange-200">
                <i class="fa-solid fa-fire"></i>
            </span>
        </div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $streak; ?> Hari Aktif</div>
        <p class="text-xs text-slate-500 mt-2">Login dan selesaikan materi tiap hari untuk melipatgandakan streak harian.</p>
    </div>

    <!-- Kartu Progres Materi -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Penyelesaian Modul</span>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#0066CC] flex items-center justify-center font-bold text-sm border border-blue-200">
                <i class="fa-solid fa-book-open"></i>
            </span>
        </div>
        <div class="flex items-baseline gap-2">
            <div class="text-2xl font-extrabold text-slate-800"><?= $totalMateriSelesai; ?> <span class="text-sm font-normal text-slate-400">/ <?= $totalMateriTersedia; ?></span></div>
            <span class="text-xs font-semibold text-[#0066CC]">(<?= $progresMateriPersen; ?>%)</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2.5 mt-3 overflow-hidden">
            <div class="bg-[#0066CC] h-2.5 rounded-full" style="width: <?= $progresMateriPersen; ?>%"></div>
        </div>
        <p class="text-[10px] text-slate-400 mt-1.5">Dari <?= $totalKelas; ?> kelas yang terdaftar</p>
    </div>

    <!-- Kartu Koleksi Badge -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Koleksi Lencana</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm border border-emerald-200">
                <i class="fa-solid fa-award"></i>
            </span>
        </div>
        <div class="text-2xl font-extrabold text-slate-800"><?= count($myBadges); ?> <span class="text-sm font-normal text-slate-400">/ 5 Badge</span></div>
        <div class="flex items-center gap-1.5 mt-3">
            <?php foreach (array_slice($myBadges, 0, 4) as $bg): ?>
                <span class="w-7 h-7 rounded-lg bg-amber-100 border border-amber-300 flex items-center justify-center text-sm" title="<?= e($bg['nama_badge']); ?>">
                    <?= $bg['ikon']; ?>
                </span>
            <?php endforeach; ?>
            <a href="<?= base_url('pelajar/gamifikasi.php'); ?>" class="text-[11px] font-bold text-[#0066CC] hover:underline ml-1">Semua &rarr;</a>
        </div>
    </div>

</div>

<!-- GRID KONTEN UTAMA: TUGAS, KUIS & PENGUMUMAN -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    <!-- KOLOM KIRI (2 Kolom): TENGGAT TUGAS & KUIS TERDEKAT -->
    <div class="lg:col-span-2 space-y-6">

        <!-- KARTU TUGAS TERDEKAT -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-800">Tugas Akademik Terdekat</h2>
                    <p class="text-xs text-slate-500">Kumpulkan sebelum tenggat untuk mendapatkan bonus XP tepat waktu.</p>
                </div>
                <a href="<?= base_url('pelajar/tugas.php'); ?>" class="text-xs font-bold text-[#0066CC] hover:underline">
                    Lihat Semua Tugas &rarr;
                </a>
            </div>

            <?php if (empty($daftarTugas)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">
                    <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-2 block"></i>
                    Belum ada tugas baru saat ini.
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($daftarTugas as $tgs): ?>
                        <?php 
                            $isOverdue = strtotime($tgs['tenggat_waktu']) < time();
                            $sudahKumpul = !empty($tgs['status_kumpul']);
                        ?>
                        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                                        <?= e($tgs['nama_kelas']); ?>
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        <i class="fa-regular fa-clock mr-1"></i>
                                        Tenggat: <?= date('d M Y, H:i', strtotime($tgs['tenggat_waktu'])); ?>
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800"><?= e($tgs['judul']); ?></h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <?php if ($sudahKumpul): ?>
                                    <div class="text-right">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold <?= ($tgs['status_kumpul'] === 'tepat_waktu') ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                            <i class="fa-solid <?= ($tgs['status_kumpul'] === 'tepat_waktu') ? 'fa-check' : 'fa-clock-rotate-left'; ?> mr-1"></i>
                                            <?= ($tgs['status_kumpul'] === 'tepat_waktu') ? 'Tepat Waktu' : 'Terlambat'; ?>
                                        </span>
                                        <?php if ($tgs['nilai'] !== null): ?>
                                            <div class="text-xs font-extrabold text-slate-800 mt-0.5">Nilai: <?= (int)$tgs['nilai']; ?>/100</div>
                                        <?php else: ?>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Menunggu penilaian</div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $isOverdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'; ?>">
                                        <?= $isOverdue ? 'Tenggat Terlewat' : 'Belum Dikumpul'; ?>
                                    </span>
                                    <a href="<?= base_url('pelajar/tugas.php?id=' . $tgs['id_tugas']); ?>" class="px-3.5 py-1.5 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition">
                                        Kumpulkan
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- KARTU KUIS AKTIF -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-800">Kuis Online Interaktif</h2>
                    <p class="text-xs text-slate-500">Uji pemahaman materi dan raih skor 100 untuk bonus XP ekstra.</p>
                </div>
                <a href="<?= base_url('pelajar/kuis.php'); ?>" class="text-xs font-bold text-[#0066CC] hover:underline">
                    Daftar Kuis &rarr;
                </a>
            </div>

            <?php if (empty($daftarKuis)): ?>
                <div class="text-center py-6 text-slate-400 text-xs">Belum ada kuis tersedia.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($daftarKuis as $ks): ?>
                        <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                                    <?= e($ks['nama_kelas']); ?>
                                </span>
                                <h3 class="text-sm font-bold text-slate-800 mt-2"><?= e($ks['judul']); ?></h3>
                                <div class="text-xs text-slate-500 mt-1 flex items-center gap-3">
                                    <span><i class="fa-solid fa-stopwatch mr-1"></i><?= (int)$ks['durasi_menit']; ?> Menit</span>
                                    <span><i class="fa-solid fa-rotate-right mr-1"></i><?= (int)$ks['jml_percobaan']; ?>/<?= (int)$ks['maks_percobaan']; ?>x</span>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200/80 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700">
                                    Skor Terbaik: <span class="text-[#0066CC]"><?= ($ks['skor_tertinggi'] !== null) ? (int)$ks['skor_tertinggi'] : '-'; ?></span>
                                </span>
                                <a href="<?= base_url('pelajar/kuis.php?id=' . $ks['id_kuis']); ?>" class="px-3 py-1.5 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition">
                                    <?= ($ks['jml_percobaan'] > 0) ? 'Ulangi' : 'Mulai'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- KOLOM KANAN (1 Kolom): PENGUMUMAN & QUICK ACCESS -->
    <div class="space-y-6">

        <!-- KARTU PENGUMUMAN KELAS TERBARU -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <i class="fa-solid fa-bullhorn text-[#0066CC]"></i>
                <h2 class="text-base font-bold text-slate-800">Pengumuman Kelas</h2>
            </div>

            <?php if (empty($daftarPengumuman)): ?>
                <div class="text-center py-6 text-slate-400 text-xs">Belum ada pengumuman kelas terbaru.</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($daftarPengumuman as $pgm): ?>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold text-[#0066CC] bg-blue-100 px-2 py-0.5 rounded">
                                <?= e($pgm['nama_kelas']); ?>
                            </span>
                            <h4 class="text-xs font-bold text-slate-800 mt-1.5"><?= e($pgm['judul']); ?></h4>
                            <p class="text-xs text-slate-600 mt-1 line-clamp-2"><?= nl2br(e($pgm['isi'])); ?></p>
                            <div class="text-[10px] text-slate-400 mt-2 flex items-center justify-between">
                                <span>Oleh <?= e($pgm['nama_pengajar']); ?></span>
                                <span><?= date('d M Y', strtotime($pgm['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- BANNER BOOKING MENTORING TUTOR -->
        <div class="bg-gradient-to-br from-[#0F294A] to-[#153660] text-white rounded-xl p-5 shadow-sm space-y-3">
            <div class="w-10 h-10 rounded-lg bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-300 text-lg">
                <i class="fa-solid fa-handshake-angle"></i>
            </div>
            <h3 class="text-sm font-bold">Butuh Bimbingan Privat?</h3>
            <p class="text-xs text-slate-300 leading-relaxed">
                Jadwalkan sesi konsultasi daring bersama Pengajar untuk membahas materi yang sulit atau asistensi tugas.
            </p>
            <a href="<?= base_url('pelajar/booking_tutor.php'); ?>" class="inline-block w-full text-center py-2 px-3 rounded-lg bg-[#0066CC] hover:bg-blue-600 text-white font-bold text-xs transition">
                Jadwalkan Sesi Tutor
            </a>
        </div>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
