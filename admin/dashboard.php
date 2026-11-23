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

// 3. Pengajar yang Menunggu Verifikasi
$stmtPending = $conn->query("SELECT * FROM users WHERE role = 'pengajar' AND status_akun = 'pending' ORDER BY created_at DESC");
$pendingTeachers = $stmtPending->fetchAll();

// 4. Data Dummy / Rekap Aktivitas Harian 7 Hari Terakhir untuk Grafik Batang (Screen 12)
$chartData = [
    ['hari' => 'Senin', 'aktivitas' => 45],
    ['hari' => 'Selasa', 'aktivitas' => 60],
    ['hari' => 'Rabu', 'aktivitas' => 52],
    ['hari' => 'Kamis', 'aktivitas' => 78],
    ['hari' => 'Jumat', 'aktivitas' => 90],
    ['hari' => 'Sabtu', 'aktivitas' => 110],
    ['hari' => 'Minggu', 'aktivitas' => 85],
];
$maxAktivitas = max(array_column($chartData, 'aktivitas'));

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 12) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <span class="text-slate-800 font-semibold">Dashboard Administrator</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Ikhtisar Platform & Ekosistem Questudy</h1>
            <p class="text-xs text-slate-500">Monitoring metrik pengguna, moderasi pengajar baru, dan analitik pembelajaran SDGs 4.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('admin/pengguna.php'); ?>" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                Kelola Pengguna
            </a>
            <a href="<?= base_url('admin/aturan_moderasi.php'); ?>" class="px-4 py-2 rounded-xl bg-white border border-[#E2E8F0] hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                Aturan & Moderasi
            </a>
        </div>
    </div>
</div>

<!-- STATS CARDS -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    
    <!-- Total Pelajar -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pelajar</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm border border-emerald-200">
                <i class="fa-solid fa-user-graduate"></i>
            </span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800"><?= (int)$uStats['total_pelajar']; ?></div>
        <p class="text-xs text-slate-500 mt-1">Siswa aktif terdaftar di sistem.</p>
    </div>

    <!-- Total Pengajar Aktif -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pengajar Terverifikasi</span>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#0066CC] flex items-center justify-center font-bold text-sm border border-blue-200">
                <i class="fa-solid fa-chalkboard-user"></i>
            </span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800"><?= (int)$uStats['pengajar_aktif']; ?></div>
        <p class="text-xs text-slate-500 mt-1">Dosen & tutor terdaftar aktif.</p>
    </div>

    <!-- Verifikasi Pending -->
    <div class="bg-white rounded-xl border border-amber-200 p-5 shadow-sm bg-amber-50/20">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Tutor Pending</span>
            <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm border border-amber-300">
                <i class="fa-solid fa-user-clock"></i>
            </span>
        </div>
        <div class="text-3xl font-extrabold text-amber-700"><?= (int)$uStats['pengajar_pending']; ?></div>
        <p class="text-xs text-slate-500 mt-1">Menunggu persetujuan aktivasi admin.</p>
    </div>

    <!-- Total Kelas Pembelajaran -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelas Pembelajaran</span>
            <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm border border-purple-200">
                <i class="fa-solid fa-school"></i>
            </span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800"><?= $totalKelas; ?></div>
        <p class="text-xs text-slate-500 mt-1"><?= $totalMateri; ?> modul & <?= $totalTugas; ?> tugas dibuat.</p>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    <!-- GRAFIK BATANG AKTIVITAS MINGGUAN PLATFORM (Screen 12) (2 Kolom) -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Pertumbuhan Aktivitas Belajar Mingguan</h3>
                    <p class="text-xs text-slate-500">Volume interaksi kuis, pengumpulan tugas, dan sesi belajar Pomodoro.</p>
                </div>
                <span class="text-xs font-bold text-[#0066CC] bg-blue-50 px-3 py-1 rounded-full">
                    Aktivitas Tertinggi: <?= $maxAktivitas; ?>
                </span>
            </div>

            <!-- CSS Bar Chart (Primary #0066CC) -->
            <div class="pt-8 pb-4">
                <div class="h-56 flex items-end justify-between gap-4 sm:gap-8 px-4">
                    <?php foreach ($chartData as $cd): ?>
                        <?php 
                            $barHeight = round(($cd['aktivitas'] / $maxAktivitas) * 100);
                        ?>
                        <div class="flex-1 flex flex-col items-center gap-2 group">
                            <span class="text-[11px] font-bold text-slate-500 group-hover:text-[#0066CC] transition">
                                <?= $cd['aktivitas']; ?>
                            </span>
                            <div class="w-full bg-[#0066CC] rounded-t-lg transition-all duration-500 group-hover:bg-[#0052A3]" style="height: <?= $barHeight; ?>%"></div>
                            <span class="text-xs font-bold text-slate-600 mt-1">
                                <?= $cd['hari']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DAFTAR PENGAJAR MENUNGGU VERIFIKASI (1 Kolom) -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-user-check text-amber-500"></i>
                    <span>Verifikasi Pengajar (<?= count($pendingTeachers); ?>)</span>
                </h3>
                <a href="<?= base_url('admin/pengguna.php?filter=pending'); ?>" class="text-[11px] font-bold text-[#0066CC] hover:underline">
                    Semua &rarr;
                </a>
            </div>

            <?php if (empty($pendingTeachers)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">
                    <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-2 block"></i>
                    Tidak ada pengajar yang menunggu verifikasi saat ini.
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($pendingTeachers as $pt): ?>
                        <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200 text-xs space-y-2">
                            <div class="flex items-center gap-2.5">
                                <img src="<?= base_url('uploads/profil/' . e($pt['foto_profil'])); ?>" class="w-8 h-8 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($pt['nama']); ?>&background=F59E0B&color=fff';">
                                <div class="truncate">
                                    <span class="font-bold text-slate-800 block truncate"><?= e($pt['nama']); ?></span>
                                    <span class="text-[10px] text-slate-500"><?= e($pt['email']); ?></span>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-600">
                                Keahlian: <strong><?= e($pt['keahlian'] ?: 'Belum diisi'); ?></strong>
                            </div>
                            <div class="flex items-center gap-2 pt-1 border-t border-amber-200/80">
                                <a href="<?= base_url('admin/pengguna.php?action=verifikasi&id=' . $pt['id_user'] . '&status=aktif'); ?>" onclick="return confirm('Setujui dan aktifkan akun pengajar ini?');" class="flex-1 text-center py-1.5 rounded-lg bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-[11px] transition shadow-sm">
                                    Terima
                                </a>
                                <a href="<?= base_url('admin/pengguna.php?action=verifikasi&id=' . $pt['id_user'] . '&status=tolak'); ?>" onclick="return confirm('Tolak pendaftaran akun pengajar ini? Akun akan dihapus.');" class="py-1.5 px-3 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 font-bold text-[11px] transition">
                                    Tolak
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
