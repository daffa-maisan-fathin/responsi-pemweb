<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Dashboard Tutor — Questudy';
$userId = $_SESSION['id_user'];

// 1. Total kelas yang diampu
$stmtK = $conn->prepare("SELECT COUNT(*) AS total_kelas FROM kelas WHERE id_pengajar = ?");
$stmtK->execute([$userId]);
$totalKelas = (int)$stmtK->fetch()['total_kelas'];

// 2. Total pelajar unik yang dibimbing
$stmtMhs = $conn->prepare("
    SELECT COUNT(DISTINCT ak.id_pelajar) AS total_mhs 
    FROM anggota_kelas ak 
    JOIN kelas k ON ak.id_kelas = k.id_kelas 
    WHERE k.id_pengajar = ?
");
$stmtMhs->execute([$userId]);
$totalMahasiswa = (int)$stmtMhs->fetch()['total_mhs'];

// 3. Total tugas aktif
$stmtTgs = $conn->prepare("
    SELECT COUNT(*) AS total_tugas 
    FROM tugas t 
    JOIN kelas k ON t.id_kelas = k.id_kelas 
    WHERE k.id_pengajar = ?
");
$stmtTgs->execute([$userId]);
$totalTugas = (int)$stmtTgs->fetch()['total_tugas'];

// 4. Booking mentoring yang pending
$stmtBookPending = $conn->prepare("SELECT COUNT(*) AS pending_book FROM booking_tutor WHERE id_tutor = ? AND status = 'pending'");
$stmtBookPending->execute([$userId]);
$bookingPending = (int)$stmtBookPending->fetch()['pending_book'];

// 5. Early Warning Quick Alert (Pelajar berisiko)
$stmtRule = $conn->query("SELECT * FROM peringatan_dini_aturan LIMIT 1");
$rule = $stmtRule->fetch() ?: ['max_telat_tugas' => 2, 'min_rata_nilai' => 65, 'max_hari_tidak_login' => 5];

// Query pelajar berisiko di kelas pengajar ini
$stmtAtRisk = $conn->prepare("
    SELECT DISTINCT u.id_user, u.nama, k.nama_kelas,
        (SELECT COUNT(*) FROM pengumpulan_tugas pt JOIN tugas t2 ON pt.id_tugas = t2.id_tugas WHERE pt.id_pelajar = u.id_user AND t2.id_kelas = k.id_kelas AND pt.status = 'terlambat') AS telat_count,
        (SELECT AVG(pt2.nilai) FROM pengumpulan_tugas pt2 JOIN tugas t3 ON pt2.id_tugas = t3.id_tugas WHERE pt2.id_pelajar = u.id_user AND t3.id_kelas = k.id_kelas AND pt2.nilai IS NOT NULL) AS avg_nilai,
        DATEDIFF(NOW(), COALESCE(u.last_login, u.created_at)) AS hari_inaktif
    FROM anggota_kelas ak
    JOIN kelas k ON ak.id_kelas = k.id_kelas
    JOIN users u ON ak.id_pelajar = u.id_user
    WHERE k.id_pengajar = ?
    HAVING telat_count >= ? OR (avg_nilai IS NOT NULL AND avg_nilai < ?) OR hari_inaktif >= ?
    LIMIT 4
");
$stmtAtRisk->execute([$userId, $rule['max_telat_tugas'], $rule['min_rata_nilai'], $rule['max_hari_tidak_login']]);
$pelajarBerisiko = $stmtAtRisk->fetchAll();

// 6. Daftar kelas pengajar beserta statistik ringkas
$stmtDaftarKelas = $conn->prepare("
    SELECT k.*, 
        (SELECT COUNT(*) FROM anggota_kelas ak WHERE ak.id_kelas = k.id_kelas) AS jml_siswa,
        (SELECT COUNT(*) FROM materi m WHERE m.id_kelas = k.id_kelas) AS jml_materi,
        (SELECT COUNT(*) FROM tugas t WHERE t.id_kelas = k.id_kelas) AS jml_tugas
    FROM kelas k
    WHERE k.id_pengajar = ?
    ORDER BY k.created_at DESC
");
$stmtDaftarKelas->execute([$userId]);
$daftarKelas = $stmtDaftarKelas->fetchAll();

// 7. Aktivitas pelajar di kelas tutor selama 7 hari (sesi fokus, serta tugas dan kuis)
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$stmtAkt = $conn->prepare("
    SELECT tgl, jenis, SUM(jml) AS total FROM (
        SELECT sp.tanggal AS tgl, 'fokus' AS jenis, COUNT(*) AS jml
        FROM sesi_pomodoro sp
        WHERE sp.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          AND sp.id_pelajar IN (
              SELECT ak.id_pelajar FROM anggota_kelas ak
              JOIN kelas k ON ak.id_kelas = k.id_kelas WHERE k.id_pengajar = ?
          )
        GROUP BY sp.tanggal
        UNION ALL
        SELECT DATE(pt.waktu_kumpul) AS tgl, 'tugas' AS jenis, COUNT(*) AS jml
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.id_tugas = t.id_tugas
        JOIN kelas k ON t.id_kelas = k.id_kelas
        WHERE k.id_pengajar = ? AND DATE(pt.waktu_kumpul) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(pt.waktu_kumpul)
        UNION ALL
        SELECT DATE(hk.waktu_selesai) AS tgl, 'tugas' AS jenis, COUNT(*) AS jml
        FROM hasil_kuis hk
        JOIN kuis q ON hk.id_kuis = q.id_kuis
        JOIN kelas k ON q.id_kelas = k.id_kelas
        WHERE k.id_pengajar = ? AND DATE(hk.waktu_selesai) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(hk.waktu_selesai)
    ) x GROUP BY tgl, jenis
");
$stmtAkt->execute([$userId, $userId, $userId]);
$petaAktivitas = [];
foreach ($stmtAkt->fetchAll() as $rowA) {
    $petaAktivitas[$rowA['tgl']][$rowA['jenis']] = (int)$rowA['total'];
}
$chartAktif = [];
for ($d = 6; $d >= 0; $d--) {
    $tgl = date('Y-m-d', strtotime("-$d day"));
    $chartAktif[] = [
        'label' => $namaHari[(int)date('w', strtotime($tgl))],
        'fokus' => $petaAktivitas[$tgl]['fokus'] ?? 0,
        'tugas' => $petaAktivitas[$tgl]['tugas'] ?? 0,
        'hariIni' => ($d === 0),
    ];
}
$maksAktif = max(1, max(array_map(function ($c) { return $c['fokus'] + $c['tugas']; }, $chartAktif)));
$totalAktif = array_sum(array_map(function ($c) { return $c['fokus'] + $c['tugas']; }, $chartAktif));
$namaTutor = $_SESSION['nama'] ?? 'Tutor';

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- SAPAAN + MASKOT -->
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <?= maskotPaus('w-16 shrink-0'); ?>
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Halo, <?= e($namaTutor); ?>!</h1>
            <p class="text-sm text-slate-500">Pantau kelas dan pelajarmu.</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('pengajar/kelas.php'); ?>" class="px-5 py-2.5 rounded-full bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold">+ Buat kelas</a>
        <a href="<?= base_url('pengajar/pemantauan.php'); ?>" class="px-5 py-2.5 rounded-full bg-white border border-indigo-100 text-indigo-600 text-sm font-bold hover:bg-indigo-50 hover:-translate-y-0.5">Pemantauan</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

    <!-- GRAFIK AKTIVITAS PELAJAR -->
    <section class="lg:col-span-5 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm lift">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-800">Aktivitas Pelajar</h2>
                <p class="text-xs text-slate-500">7 hari terakhir di kelasmu</p>
            </div>
            <div class="text-right">
                <div class="text-lg font-extrabold text-indigo-600"><span data-hitung="<?= (int)$totalAktif; ?>"><?= (int)$totalAktif; ?></span></div>
                <div class="text-xs text-slate-500">aktivitas</div>
            </div>
        </div>
        <div class="mt-3 flex items-end gap-2.5 h-36">
            <?php foreach ($chartAktif as $ca):
                $tot = $ca['fokus'] + $ca['tugas'];
                $tinggi = $tot > 0 ? max(8, round(($tot / $maksAktif) * 100)) : 4;
            ?>
                <div class="group flex-1 h-full flex flex-col justify-end items-center gap-1.5">
                    <div class="relative w-full flex-1 flex items-end">
                        <span class="absolute left-1/2 -translate-x-1/2 -top-1 px-2 py-0.5 rounded-lg bg-slate-800 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10">Fokus <?= (int)$ca['fokus']; ?> · Tugas/kuis <?= (int)$ca['tugas']; ?></span>
                        <div class="batang-naik w-full rounded-xl overflow-hidden flex flex-col-reverse transition-all duration-300 group-hover:scale-y-105 origin-bottom" style="height: <?= $tinggi; ?>%">
                            <?php if ($tot > 0): ?>
                                <div class="bg-gradient-to-t from-indigo-500 to-sky-400" style="flex: <?= (int)$ca['fokus']; ?> 1 0"></div>
                                <div class="bg-amber-300" style="flex: <?= (int)$ca['tugas']; ?> 1 0"></div>
                            <?php else: ?>
                                <div class="flex-1 bg-slate-100"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="text-xs font-semibold <?= $ca['hariIni'] ? 'text-amber-700' : 'text-slate-500'; ?>"><?= e($ca['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
            <span><i class="inline-block w-2.5 h-2.5 rounded-sm bg-indigo-500 mr-1.5"></i>Sesi fokus</span>
            <span><i class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-300 mr-1.5"></i>Tugas dan kuis</span>
        </div>
    </section>

    <!-- STATISTIK -->
    <section class="lg:col-span-3 grid grid-cols-2 gap-3 content-start">
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-sky-100 text-indigo-600 grid place-items-center"><i class="fa-solid fa-chalkboard-user"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)($totalKelas); ?>"><?= $totalKelas; ?></span></div>
            <div class="text-xs text-slate-500">Kelas</div>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 grid place-items-center"><i class="fa-solid fa-users"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)($totalMahasiswa); ?>"><?= $totalMahasiswa; ?></span></div>
            <div class="text-xs text-slate-500">Pelajar</div>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-pink-100 text-pink-600 grid place-items-center"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)(count($pelajarBerisiko)); ?>"><?= count($pelajarBerisiko); ?></span></div>
            <div class="text-xs text-slate-500">Perlu perhatian</div>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 grid place-items-center"><i class="fa-solid fa-handshake-angle"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)($bookingPending); ?>"><?= $bookingPending; ?></span></div>
            <div class="text-xs text-slate-500">Booking menunggu</div>
        </div>
    </section>

    <!-- PERINGATAN DINI -->
    <section class="lg:col-span-4 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800">Perlu Perhatian</h2>
            <a href="<?= base_url('pengajar/pemantauan.php'); ?>" class="text-xs font-bold text-indigo-600 hover:underline">Detail</a>
        </div>
        <?php if (empty($pelajarBerisiko)): ?>
            <div class="mt-3 p-5 rounded-2xl bg-emerald-50 text-center text-sm text-emerald-700">
                <i class="fa-solid fa-circle-check mr-1"></i>Semua pelajar aman.
            </div>
        <?php else: ?>
            <div class="mt-3 space-y-2">
                <?php foreach (array_slice($pelajarBerisiko, 0, 3) as $pb): ?>
                    <div class="p-3 rounded-2xl bg-pink-50 hover:bg-pink-100/70 transition">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold text-slate-800 truncate"><?= e($pb['nama']); ?></span>
                            <span class="text-xs text-slate-500 truncate"><?= e($pb['nama_kelas']); ?></span>
                        </div>
                        <div class="text-xs text-pink-700 mt-0.5">
                            <?php
                            $alasan = [];
                            if ($pb['telat_count'] >= $rule['max_telat_tugas']) $alasan[] = 'Telat tugas ' . (int)$pb['telat_count'] . 'x';
                            if ($pb['avg_nilai'] !== null && $pb['avg_nilai'] < $rule['min_rata_nilai']) $alasan[] = 'Nilai ' . round($pb['avg_nilai'], 1);
                            if ($pb['hari_inaktif'] >= $rule['max_hari_tidak_login']) $alasan[] = 'Tidak login ' . (int)$pb['hari_inaktif'] . ' hari';
                            echo e(implode(' · ', $alasan));
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- KELAS DIAMPU -->
    <section class="lg:col-span-8 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800">Kelas yang Diampu</h2>
            <a href="<?= base_url('pengajar/kelas.php'); ?>" class="text-xs font-bold text-indigo-600 hover:underline">Kelola semua</a>
        </div>
        <?php if (empty($daftarKelas)): ?>
            <?= keadaanKosong('Belum ada kelas. Klik "+ Buat kelas" untuk memulai.'); ?>
        <?php else: ?>
            <div class="mt-3 space-y-2">
                <?php foreach (array_slice($daftarKelas, 0, 3) as $kls): ?>
                    <div class="flex flex-wrap items-center gap-3 p-3 rounded-2xl hover:bg-indigo-50 transition">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-slate-800 truncate"><?= e($kls['nama_kelas']); ?></div>
                            <div class="text-xs text-slate-500">Kode <?= e($kls['kode_kelas']); ?> · <?= (int)$kls['jml_siswa']; ?> pelajar</div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= base_url('pengajar/materi.php?id_kelas=' . $kls['id_kelas']); ?>" class="px-3 py-1.5 rounded-full bg-white border border-indigo-100 text-indigo-600 text-xs font-bold hover:bg-indigo-50 transition">Materi <?= (int)$kls['jml_materi']; ?></a>
                            <a href="<?= base_url('pengajar/tugas.php?id_kelas=' . $kls['id_kelas']); ?>" class="px-3 py-1.5 rounded-full bg-white border border-indigo-100 text-indigo-600 text-xs font-bold hover:bg-indigo-50 transition">Tugas <?= (int)$kls['jml_tugas']; ?></a>
                            <a href="<?= base_url('pengajar/rapor.php?id_kelas=' . $kls['id_kelas']); ?>" class="px-3 py-1.5 rounded-full bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold">Rapor</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- BOOKING -->
    <a href="<?= base_url('pengajar/booking.php'); ?>" class="lg:col-span-4 block p-5 rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-sky-400 text-white hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-200 transition">
        <div class="text-base font-bold">Sesi mentoring</div>
        <div class="text-sm text-white/85 mt-0.5"><?= $bookingPending; ?> permintaan menunggu konfirmasi.</div>
        <span class="inline-block mt-4 px-4 py-1.5 rounded-full bg-white text-indigo-700 text-sm font-bold">Kelola</span>
    </a>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
