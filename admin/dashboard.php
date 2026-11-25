<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('admin');

$pageTitle = 'Dashboard Administrator — Questudy';
$userId = $_SESSION['id_user'];

// 1. Statistik Pengguna
$stmtUsersCount = $conn->query("
    SELECT 
        COUNT(*) AS total_semua,
        SUM(CASE WHEN role = 'pelajar' THEN 1 ELSE 0 END) AS total_pelajar,
        SUM(CASE WHEN role = 'pengajar' AND status_akun = 'aktif' THEN 1 ELSE 0 END) AS pengajar_aktif,
        SUM(CASE WHEN role = 'pengajar' AND status_akun = 'pending' THEN 1 ELSE 0 END) AS pengajar_pending,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS total_admin
    FROM users
");
$uStats = $stmtUsersCount->fetch();

// 2. Statistik Pembelajaran
$totalKelas = (int)$conn->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$totalMateri = (int)$conn->query("SELECT COUNT(*) FROM materi")->fetchColumn();
$totalTugas = (int)$conn->query("SELECT COUNT(*) FROM tugas")->fetchColumn();
$totalKuis = (int)$conn->query("SELECT COUNT(*) FROM kuis")->fetchColumn();

// 3. Tutor yang Menunggu Verifikasi
$stmtPending = $conn->query("SELECT * FROM users WHERE role = 'pengajar' AND status_akun = 'pending' ORDER BY created_at DESC");
$pendingTeachers = $stmtPending->fetchAll();

// 4. Aktivitas 7 hari terakhir (sesi fokus + pengumpulan tugas + kuis) untuk grafik batang
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$aktivitasMap = [];
$stmtAkt = $conn->query("
    SELECT tgl, SUM(jml) AS total FROM (
        SELECT tanggal AS tgl, COUNT(*) AS jml FROM sesi_pomodoro
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY tanggal
        UNION ALL
        SELECT DATE(waktu_kumpul) AS tgl, COUNT(*) AS jml FROM pengumpulan_tugas
        WHERE DATE(waktu_kumpul) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(waktu_kumpul)
        UNION ALL
        SELECT DATE(waktu_selesai) AS tgl, COUNT(*) AS jml FROM hasil_kuis
        WHERE DATE(waktu_selesai) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(waktu_selesai)
    ) x GROUP BY tgl
");
foreach ($stmtAkt->fetchAll() as $rowA) {
    $aktivitasMap[$rowA['tgl']] = (int)$rowA['total'];
}
$chartData = [];
for ($d = 6; $d >= 0; $d--) {
    $tgl = date('Y-m-d', strtotime("-$d day"));
    $chartData[] = [
        'hari' => $namaHari[(int)date('w', strtotime($tgl))],
        'aktivitas' => $aktivitasMap[$tgl] ?? 0,
        'hariIni' => ($d === 0),
    ];
}
$maxAktivitas = max(1, max(array_column($chartData, 'aktivitas')));
$totalAktivitas = array_sum(array_column($chartData, 'aktivitas'));

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- SAPAAN + MASKOT -->
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <?= maskotPaus('w-16 shrink-0'); ?>
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Halo, Admin!</h1>
            <p class="text-sm text-slate-500">Ringkasan platform Questudy.</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('admin/pengguna.php'); ?>" class="px-5 py-2.5 rounded-full bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold">Kelola pengguna</a>
        <a href="<?= base_url('admin/aturan_moderasi.php'); ?>" class="px-5 py-2.5 rounded-full bg-white border border-indigo-100 text-indigo-600 text-sm font-bold hover:bg-indigo-50 hover:-translate-y-0.5">Aturan</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

    <!-- GRAFIK AKTIVITAS -->
    <section class="lg:col-span-7 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm lift">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-800">Aktivitas Belajar</h2>
                <p class="text-xs text-slate-500">Sesi fokus, tugas, dan kuis 7 hari terakhir</p>
            </div>
            <div class="text-right">
                <div class="text-lg font-extrabold text-indigo-600"><span data-hitung="<?= (int)($totalAktivitas); ?>"><?= $totalAktivitas; ?></span></div>
                <div class="text-xs text-slate-500">aktivitas</div>
            </div>
        </div>
        <div class="mt-4 flex items-end gap-2.5 h-40">
            <?php foreach ($chartData as $cd):
                $tinggi = max(5, round(($cd['aktivitas'] / $maxAktivitas) * 100));
            ?>
                <div class="group flex-1 h-full flex flex-col justify-end items-center gap-1.5">
                    <div class="relative w-full flex-1 flex items-end">
                        <span class="absolute left-1/2 -translate-x-1/2 -top-1 px-2 py-0.5 rounded-lg bg-slate-800 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10"><?= (int)$cd['aktivitas']; ?></span>
                        <div class="batang-naik w-full rounded-xl transition-all duration-300 group-hover:scale-y-105 origin-bottom <?= $cd['hariIni'] ? 'bg-gradient-to-t from-amber-400 to-amber-300' : 'bg-gradient-to-t from-indigo-500 to-sky-400 group-hover:from-indigo-600 group-hover:to-sky-300'; ?>" style="height: <?= $tinggi; ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold <?= $cd['hariIni'] ? 'text-amber-700' : 'text-slate-500'; ?>"><?= e($cd['hari']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- STATISTIK -->
    <section class="lg:col-span-5 grid grid-cols-2 gap-3">
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-sky-100 text-indigo-600 grid place-items-center"><i class="fa-solid fa-user-graduate"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)((int)$uStats['total_pelajar']); ?>"><?= (int)$uStats['total_pelajar']; ?></span></div>
            <div class="text-xs text-slate-500">Pelajar</div>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 grid place-items-center"><i class="fa-solid fa-chalkboard-user"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)((int)$uStats['pengajar_aktif']); ?>"><?= (int)$uStats['pengajar_aktif']; ?></span></div>
            <div class="text-xs text-slate-500">Tutor aktif</div>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 grid place-items-center"><i class="fa-solid fa-user-clock"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)((int)$uStats['pengajar_pending']); ?>"><?= (int)$uStats['pengajar_pending']; ?></span></div>
            <div class="text-xs text-slate-500">Tutor menunggu</div>
        </div>
        <div class="bg-white rounded-3xl p-4 border border-indigo-100 shadow-sm lift">
            <span class="w-9 h-9 rounded-xl bg-violet-100 text-violet-600 grid place-items-center"><i class="fa-solid fa-school"></i></span>
            <div class="mt-2 text-2xl font-extrabold text-slate-800"><span data-hitung="<?= (int)($totalKelas); ?>"><?= $totalKelas; ?></span></div>
            <div class="text-xs text-slate-500">Kelas</div>
        </div>
    </section>

    <!-- VERIFIKASI TUTOR -->
    <section class="lg:col-span-7 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800">Verifikasi Tutor (<?= count($pendingTeachers); ?>)</h2>
            <a href="<?= base_url('admin/pengguna.php?filter=pending'); ?>" class="text-xs font-bold text-indigo-600 hover:underline">Semua</a>
        </div>
        <?php if (empty($pendingTeachers)): ?>
            <div class="mt-3 p-5 rounded-2xl bg-emerald-50 text-center text-sm text-emerald-700">
                <i class="fa-solid fa-circle-check mr-1"></i>Tidak ada tutor yang menunggu.
            </div>
        <?php else: ?>
            <div class="mt-3 space-y-2">
                <?php foreach (array_slice($pendingTeachers, 0, 2) as $pt): ?>
                    <div class="flex flex-wrap items-center gap-3 p-3 rounded-2xl bg-amber-50 hover:bg-amber-100/70 transition">
                        <img src="<?= base_url('uploads/profil/' . e($pt['foto_profil'])); ?>" class="w-10 h-10 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($pt['nama']); ?>&background=E6F1FA&color=3D7BBF'">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-slate-800 truncate"><?= e($pt['nama']); ?></div>
                            <div class="text-xs text-slate-600 truncate"><?= e($pt['keahlian'] ?: 'Keahlian belum diisi'); ?></div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= base_url('admin/pengguna.php?action=verifikasi&id=' . $pt['id_user'] . '&status=aktif'); ?>" onclick="return confirm('Setujui tutor ini?');" class="px-4 py-1.5 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition">Terima</a>
                            <a href="<?= base_url('admin/pengguna.php?action=verifikasi&id=' . $pt['id_user'] . '&status=tolak'); ?>" onclick="return confirm('Tolak tutor ini?');" class="px-4 py-1.5 rounded-full bg-pink-100 hover:bg-pink-200 text-pink-700 text-xs font-bold transition">Tolak</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- KONTEN DAN PINTASAN -->
    <section class="lg:col-span-5 bg-white rounded-3xl p-5 border border-indigo-100 shadow-sm">
        <h2 class="text-base font-bold text-slate-800">Konten Platform</h2>
        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-2xl bg-sky-50 py-3"><div class="text-lg font-extrabold text-slate-800"><span data-hitung="<?= (int)($totalMateri); ?>"><?= $totalMateri; ?></span></div><div class="text-xs text-slate-500">Materi</div></div>
            <div class="rounded-2xl bg-amber-50 py-3"><div class="text-lg font-extrabold text-slate-800"><span data-hitung="<?= (int)($totalTugas); ?>"><?= $totalTugas; ?></span></div><div class="text-xs text-slate-500">Tugas</div></div>
            <div class="rounded-2xl bg-violet-50 py-3"><div class="text-lg font-extrabold text-slate-800"><span data-hitung="<?= (int)($totalKuis); ?>"><?= $totalKuis; ?></span></div><div class="text-xs text-slate-500">Kuis</div></div>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <a href="<?= base_url('admin/laporan.php'); ?>" class="px-3 py-2.5 rounded-2xl bg-slate-50 hover:bg-indigo-50 hover:-translate-y-0.5 transition text-sm font-bold text-slate-700 text-center"><i class="fa-solid fa-flag mr-1.5 text-pink-500"></i>Laporan</a>
            <a href="<?= base_url('admin/pengaturan.php'); ?>" class="px-3 py-2.5 rounded-2xl bg-slate-50 hover:bg-indigo-50 hover:-translate-y-0.5 transition text-sm font-bold text-slate-700 text-center"><i class="fa-solid fa-sliders mr-1.5 text-indigo-500"></i>Kuota</a>
        </div>
    </section>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
