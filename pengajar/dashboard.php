<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Dashboard Pengajar — Questudy';
$userId = $_SESSION['id_user'];

// 1. Total kelas yang diampu
$stmtK = $conn->prepare("SELECT COUNT(*) AS total_kelas FROM kelas WHERE id_pengajar = ?");
$stmtK->execute([$userId]);
$totalKelas = (int)$stmtK->fetch()['total_kelas'];

// 2. Total mahasiswa unik yang dibimbing
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

// Query mahasiswa berisiko di kelas pengajar ini
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

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <span class="text-slate-800 font-semibold">Dashboard Pengajar</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Panel Pengajar & Evaluasi Kelas</h1>
            <p class="text-xs text-slate-500">Pantau aktivitas akademik kelas, sistem peringatan dini pelajar, dan permohonan bimbingan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('pengajar/pemantauan.php'); ?>" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Pemantauan EWS</span>
            </a>
            <a href="<?= base_url('pengajar/kelas.php'); ?>" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                + Buat Kelas
            </a>
        </div>
    </div>
</div>

<!-- STATS CARDS -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    
    <!-- Total Kelas -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelas Aktif</span>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#0066CC] flex items-center justify-center font-bold text-sm border border-blue-200">
                <i class="fa-solid fa-chalkboard-user"></i>
            </span>
        </div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $totalKelas; ?> Kelas</div>
        <p class="text-xs text-slate-500 mt-1">Kelas yang sedang Anda ampu.</p>
    </div>

    <!-- Total Mahasiswa -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Mahasiswa</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm border border-emerald-200">
                <i class="fa-solid fa-users"></i>
            </span>
        </div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $totalMahasiswa; ?> Orang</div>
        <p class="text-xs text-slate-500 mt-1">Siswa unik terdaftar dalam kelas Anda.</p>
    </div>

    <!-- Peringatan Dini / EWS Alert -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Perlu Perhatian</span>
            <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm border border-red-200">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
        </div>
        <div class="text-2xl font-extrabold text-red-600"><?= count($pelajarBerisiko); ?> Pelajar</div>
        <p class="text-xs text-slate-500 mt-1">Terdeteksi berisiko tertinggal (EWS).</p>
    </div>

    <!-- Booking Mentoring Pending -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Permintaan Mentor</span>
            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center font-bold text-sm border border-amber-200">
                <i class="fa-solid fa-handshake-angle"></i>
            </span>
        </div>
        <div class="text-2xl font-extrabold text-slate-800"><?= $bookingPending; ?> Sesi</div>
        <p class="text-xs text-slate-500 mt-1">Menunggu persetujuan waktu Anda.</p>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    <!-- KELOLA KELAS SAYA (2 Kolom) -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h2 class="text-base font-bold text-slate-800">Daftar Kelas yang Diampu</h2>
                <a href="<?= base_url('pengajar/kelas.php'); ?>" class="text-xs font-bold text-[#0066CC] hover:underline">
                    Kelola Semua Kelas &rarr;
                </a>
            </div>

            <?php if (empty($daftarKelas)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">
                    Anda belum membuat kelas. Klik tombol "+ Buat Kelas" untuk memulai.
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($daftarKelas as $kls): ?>
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                                        Kode: <?= e($kls['kode_kelas']); ?>
                                    </span>
                                    <span class="text-xs text-slate-400"><?= (int)$kls['jml_siswa']; ?> Mahasiswa</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800"><?= e($kls['nama_kelas']); ?></h3>
                                <p class="text-xs text-slate-500 line-clamp-1 mt-0.5"><?= e($kls['deskripsi'] ?: 'Tanpa deskripsi.'); ?></p>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="<?= base_url('pengajar/materi.php?id_kelas=' . $kls['id_kelas']); ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition" title="Kelola Materi">
                                    Materi (<?= (int)$kls['jml_materi']; ?>)
                                </a>
                                <a href="<?= base_url('pengajar/tugas.php?id_kelas=' . $kls['id_kelas']); ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition" title="Kelola Tugas">
                                    Tugas (<?= (int)$kls['jml_tugas']; ?>)
                                </a>
                                <a href="<?= base_url('pengajar/rapor.php?id_kelas=' . $kls['id_kelas']); ?>" class="px-3 py-1.5 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                                    Rapor
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- QUICK WIDGET: EARLY WARNING ALERT (1 Kolom) -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-red-200 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-red-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Peringatan Dini (EWS)</h3>
                </div>
                <a href="<?= base_url('pengajar/pemantauan.php'); ?>" class="text-[11px] font-bold text-red-600 hover:underline">
                    Detail &rarr;
                </a>
            </div>

            <?php if (empty($pelajarBerisiko)): ?>
                <div class="text-center py-6 text-slate-400 text-xs">
                    <i class="fa-solid fa-circle-check text-2xl text-emerald-500 mb-2 block"></i>
                    Semua mahasiswa berada dalam performa akademik yang baik!
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($pelajarBerisiko as $pb): ?>
                        <div class="p-3 rounded-xl bg-red-50/60 border border-red-200 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800"><?= e($pb['nama']); ?></span>
                                <span class="text-[10px] text-slate-500"><?= e($pb['nama_kelas']); ?></span>
                            </div>
                            <div class="text-[11px] text-red-700 space-y-0.5">
                                <?php if ($pb['telat_count'] >= $rule['max_telat_tugas']): ?>
                                    <div>&bull; Terlambat kumpul tugas: <strong><?= (int)$pb['telat_count']; ?>x</strong></div>
                                <?php endif; ?>
                                <?php if ($pb['avg_nilai'] !== null && $pb['avg_nilai'] < $rule['min_rata_nilai']): ?>
                                    <div>&bull; Rata-rata nilai rendah: <strong><?= round($pb['avg_nilai'], 1); ?></strong></div>
                                <?php endif; ?>
                                <?php if ($pb['hari_inaktif'] >= $rule['max_hari_tidak_login']): ?>
                                    <div>&bull; Tidak login selama: <strong><?= (int)$pb['hari_inaktif']; ?> hari</strong></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- QUICK BOOKING LINK -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm space-y-2">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jadwal Sesi Mentoring</h4>
            <p class="text-xs text-slate-500">Terdapat <?= $bookingPending; ?> pengajuan sesi mentoring yang menunggu konfirmasi Anda.</p>
            <a href="<?= base_url('pengajar/booking.php'); ?>" class="inline-block w-full text-center py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                Buka Kelola Mentoring
            </a>
        </div>
    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
