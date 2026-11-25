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

// 8. Aktivitas fokus 7 hari terakhir (menit per hari)
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$stmtFokus = $conn->prepare("SELECT tanggal, SUM(durasi_menit) AS menit FROM sesi_pomodoro WHERE id_pelajar = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY tanggal");
$stmtFokus->execute([$userId]);
$fokusMap = [];
foreach ($stmtFokus->fetchAll() as $rowF) {
    $fokusMap[$rowF['tanggal']] = (int)$rowF['menit'];
}
$grafikFokus = [];
for ($d = 6; $d >= 0; $d--) {
    $tgl = date('Y-m-d', strtotime("-$d day"));
    $grafikFokus[] = [
        'label' => $namaHari[(int)date('w', strtotime($tgl))],
        'menit' => $fokusMap[$tgl] ?? 0,
        'hariIni' => ($d === 0),
    ];
}
$totalMenitMinggu = array_sum(array_column($grafikFokus, 'menit'));
$maksMenit = max(30, max(array_column($grafikFokus, 'menit')));

// 9. Progres per kelas
$stmtProgresKelas = $conn->prepare("
    SELECT k.id_kelas, k.nama_kelas, k.kategori, u.nama AS nama_tutor,
        (SELECT COUNT(*) FROM materi m WHERE m.id_kelas = k.id_kelas) AS total_materi,
        (SELECT COUNT(*) FROM progres_materi pm JOIN materi m2 ON pm.id_materi = m2.id_materi WHERE m2.id_kelas = k.id_kelas AND pm.id_pelajar = ?) AS materi_selesai
    FROM anggota_kelas ak
    JOIN kelas k ON ak.id_kelas = k.id_kelas
    JOIN users u ON k.id_pengajar = u.id_user
    WHERE ak.id_pelajar = ?
    ORDER BY k.nama_kelas ASC
");
$stmtProgresKelas->execute([$userId, $userId]);
$progresKelas = $stmtProgresKelas->fetchAll();

// 10. Kalender bulan ini: tanggal yang punya tenggat tugas atau kuis
$bulanIni = (int)date('n');
$tahunIni = (int)date('Y');
$stmtKal = $conn->prepare("
    SELECT DAY(t.tenggat_waktu) AS hari FROM tugas t JOIN anggota_kelas ak ON t.id_kelas = ak.id_kelas
    WHERE ak.id_pelajar = ? AND YEAR(t.tenggat_waktu) = ? AND MONTH(t.tenggat_waktu) = ?
    UNION
    SELECT DAY(q.tenggat_waktu) AS hari FROM kuis q JOIN anggota_kelas ak2 ON q.id_kelas = ak2.id_kelas
    WHERE ak2.id_pelajar = ? AND YEAR(q.tenggat_waktu) = ? AND MONTH(q.tenggat_waktu) = ?
");
$stmtKal->execute([$userId, $tahunIni, $bulanIni, $userId, $tahunIni, $bulanIni]);
$hariTenggat = array_map('intval', array_column($stmtKal->fetchAll(), 'hari'));
$namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$hariPertama = (int)date('N', strtotime("$tahunIni-$bulanIni-01"));
$jumlahHari = (int)date('t', strtotime("$tahunIni-$bulanIni-01"));
$warnaKelas = ['from-sky-400 to-indigo-500', 'from-emerald-400 to-emerald-500', 'from-violet-300 to-violet-500', 'from-amber-300 to-amber-400', 'from-pink-400 to-pink-500'];

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- SAPAAN + MASKOT -->
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <?= maskotPaus('w-16 shrink-0'); ?>
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Halo, <?= e($user['nama']); ?>!</h1>
            <p class="text-sm text-slate-500">Yuk lanjut belajar hari ini.</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('pelajar/ruang_belajar.php'); ?>" class="px-5 py-2.5 rounded-full bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold">Mulai fokus</a>
        <a href="<?= base_url('pelajar/katalog.php'); ?>" class="px-5 py-2.5 rounded-full bg-white border border-indigo-100 text-indigo-600 text-sm font-bold hover:bg-indigo-50 hover:-translate-y-0.5">Cari kelas</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

    <!-- BARIS 1: FOKUS, LEVEL, KALENDER -->
    <section class="lg:col-span-5 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm lift">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-800">Aktivitas Fokus</h2>
                <p class="text-xs text-slate-500">Menit belajar 7 hari terakhir</p>
            </div>
            <div class="text-right">
                <div class="text-lg font-extrabold text-indigo-600"><?= floor($totalMenitMinggu / 60); ?>j <?= $totalMenitMinggu % 60; ?>m</div>
                <div class="text-xs text-slate-500">minggu ini</div>
            </div>
        </div>
        <div class="mt-4 flex items-end gap-2.5 h-36">
            <?php foreach ($grafikFokus as $g):
                $tinggi = max(5, round(($g['menit'] / $maksMenit) * 100));
            ?>
                <div class="group flex-1 h-full flex flex-col justify-end items-center gap-1.5">
                    <div class="relative w-full flex-1 flex items-end">
                        <span class="absolute left-1/2 -translate-x-1/2 -top-1 px-2 py-0.5 rounded-lg bg-slate-800 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10"><?= (int)$g['menit']; ?> mnt</span>
                        <div class="batang-naik w-full rounded-xl transition-all duration-300 group-hover:scale-y-105 origin-bottom <?= $g['hariIni'] ? 'bg-gradient-to-t from-amber-400 to-amber-300' : 'bg-gradient-to-t from-indigo-500 to-sky-400 group-hover:from-indigo-600 group-hover:to-sky-300'; ?>" style="height: <?= $tinggi; ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold <?= $g['hariIni'] ? 'text-amber-700' : 'text-slate-500'; ?>"><?= e($g['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lg:col-span-3 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm lift flex flex-col">
        <h2 class="text-base font-bold text-slate-800">Level dan XP</h2>
        <div class="mt-3 flex items-center gap-3">
            <div class="relative w-20 h-20 rounded-full shrink-0" data-sudut="<?= $progressPercent * 3.6; ?>" style="--sudut: <?= $progressPercent * 3.6; ?>deg; background: conic-gradient(#3D7BBF var(--sudut), #E6F1FA 0);">
                <div class="absolute inset-2.5 rounded-full bg-white grid place-items-center text-center leading-none">
                    <div><div class="text-xs text-slate-500">Level</div><div class="text-xl font-extrabold text-indigo-600"><?= $currentLevel; ?></div></div>
                </div>
            </div>
            <div>
                <div class="text-xl font-extrabold text-slate-800"><span data-hitung="<?= (int)$currentXP; ?>"><?= number_format($currentXP); ?></span> XP</div>
                <p class="text-xs text-slate-500"><?= max(0, 100 - $xpProgressInLevel); ?> XP lagi ke level <?= $currentLevel + 1; ?></p>
            </div>
        </div>
        <div class="mt-auto pt-4 grid grid-cols-2 gap-2">
            <div class="rounded-2xl bg-amber-100 p-3 hover:bg-amber-200 transition">
                <i class="fa-solid fa-fire text-orange-500"></i>
                <div class="text-base font-extrabold text-slate-800"><span data-hitung="<?= (int)$streak; ?>"><?= (int)$streak; ?></span> hari</div>
                <div class="text-xs text-slate-600">Streak</div>
            </div>
            <div class="rounded-2xl bg-emerald-100 p-3 hover:bg-emerald-200 transition">
                <i class="fa-solid fa-book-open text-emerald-700"></i>
                <div class="text-base font-extrabold text-slate-800"><span data-hitung="<?= (int)$totalMateriSelesai; ?>"><?= (int)$totalMateriSelesai; ?></span>/<?= $totalMateriTersedia; ?></div>
                <div class="text-xs text-slate-600">Materi</div>
            </div>
        </div>
    </section>

    <section class="lg:col-span-4 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm lift">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800"><?= $namaBulan[$bulanIni]; ?> <?= $tahunIni; ?></h2>
            <span class="text-xs text-slate-500"><i class="fa-solid fa-circle text-amber-400 text-[8px] mr-1"></i>Tenggat</span>
        </div>
        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-400">
            <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h): ?><div><?= $h; ?></div><?php endforeach; ?>
        </div>
        <div class="mt-1 grid grid-cols-7 gap-1 text-center text-xs">
            <?php for ($k = 1; $k < $hariPertama; $k++): ?><div></div><?php endfor; ?>
            <?php for ($d = 1; $d <= $jumlahHari; $d++):
                $hariIni = ($d === (int)date('j'));
                $adaTenggat = in_array($d, $hariTenggat);
            ?>
                <div class="relative py-1 rounded-lg transition cursor-default <?= $hariIni ? 'bg-gradient-to-br from-sky-400 to-indigo-600 text-white font-bold' : 'text-slate-700 hover:bg-indigo-50'; ?>">
                    <?= $d; ?>
                    <?php if ($adaTenggat): ?><span class="absolute left-1/2 -translate-x-1/2 bottom-0 w-1.5 h-1.5 rounded-full bg-amber-400"></span><?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- BARIS 2: KELAS, TUGAS, PENGUMUMAN -->
    <section class="lg:col-span-5 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800">Kelas Saya</h2>
            <a href="<?= base_url('pelajar/kelas.php'); ?>" class="text-xs font-bold text-indigo-600 hover:underline">Lihat semua</a>
        </div>
        <?php if (empty($progresKelas)): ?>
            <?= keadaanKosong('Belum ada kelas.', '<a href="' . base_url('pelajar/katalog.php') . '" class="mt-3 px-5 py-2 rounded-full bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold">Cari kelas</a>'); ?>
        <?php else: ?>
            <div class="mt-3 space-y-2">
                <?php foreach (array_slice($progresKelas, 0, 3) as $i => $pk):
                    $persenKelas = ((int)$pk['total_materi'] > 0) ? round(((int)$pk['materi_selesai'] / (int)$pk['total_materi']) * 100) : 0;
                ?>
                    <a href="<?= base_url('pelajar/kelas.php'); ?>" class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-indigo-50 hover:translate-x-1 transition">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $warnaKelas[$i % count($warnaKelas)]; ?> text-white grid place-items-center shrink-0">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-bold text-slate-800 truncate"><?= e($pk['nama_kelas']); ?></div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="bar-isi h-full rounded-full bg-gradient-to-r <?= $warnaKelas[$i % count($warnaKelas)]; ?>" style="width: <?= $persenKelas; ?>%"></div>
                            </div>
                        </div>
                        <div class="text-sm font-extrabold text-indigo-600"><span data-hitung="<?= (int)$persenKelas; ?>"><?= (int)$persenKelas; ?></span>%</div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="lg:col-span-4 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800">Tugas Terdekat</h2>
            <a href="<?= base_url('pelajar/tugas.php'); ?>" class="text-xs font-bold text-indigo-600 hover:underline">Semua</a>
        </div>
        <?php if (empty($daftarTugas)): ?>
            <?= keadaanKosong('Tidak ada tugas. Santai dulu!'); ?>
        <?php else: ?>
            <div class="mt-3 space-y-1.5">
                <?php foreach (array_slice($daftarTugas, 0, 3) as $tgs):
                    $sudahKumpul = !empty($tgs['status_kumpul']);
                ?>
                    <a href="<?= base_url('pelajar/tugas.php?id=' . $tgs['id_tugas']); ?>" class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-indigo-50 hover:translate-x-1 transition">
                        <div class="w-9 h-9 rounded-xl grid place-items-center shrink-0 <?= $sudahKumpul ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                            <i class="fa-solid <?= $sudahKumpul ? 'fa-check' : 'fa-clock'; ?>"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-slate-800 truncate"><?= e($tgs['judul']); ?></div>
                            <div class="text-xs text-slate-500"><?= date('d M, H:i', strtotime($tgs['tenggat_waktu'])); ?></div>
                        </div>
                        <?php if ($sudahKumpul && $tgs['nilai'] !== null): ?>
                            <span class="text-sm font-extrabold text-emerald-700"><?= (int)$tgs['nilai']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="lg:col-span-3 flex flex-col gap-4">
        <section class="bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm flex-1">
            <h2 class="text-base font-bold text-slate-800">Pengumuman</h2>
            <?php if (empty($daftarPengumuman)): ?>
                <?= keadaanKosong('Belum ada pengumuman.'); ?>
            <?php else: $pgm = $daftarPengumuman[0]; ?>
                <div class="mt-3 p-3 rounded-2xl bg-sky-50 hover:bg-sky-100 transition">
                    <div class="text-xs font-semibold text-indigo-600 truncate"><?= e($pgm['nama_kelas']); ?></div>
                    <div class="mt-0.5 text-sm font-bold text-slate-800"><?= e($pgm['judul']); ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= e($pgm['nama_pengajar']); ?> · <?= date('d M Y', strtotime($pgm['created_at'])); ?></div>
                </div>
            <?php endif; ?>
        </section>
        <a href="<?= base_url('pelajar/booking_tutor.php'); ?>" class="block p-5 rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-sky-400 text-white hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-200 transition">
            <div class="text-base font-bold">Butuh bimbingan?</div>
            <div class="text-xs text-white/85 mt-0.5">Booking sesi bareng tutor.</div>
            <span class="inline-block mt-3 px-4 py-1.5 rounded-full bg-white text-indigo-700 text-sm font-bold">Booking</span>
        </a>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
