<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Kelola Mentoring & Booking — Questudy';
$userId = $_SESSION['id_user'];

// Handle Setujui Booking & Input Link Meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setujui_booking') {
    $idB = (int)$_POST['id_booking'];
    $link = trim($_POST['link_meeting'] ?? '');

    $stmtInfo = $conn->prepare("SELECT tanggal_sesi, jam_sesi FROM booking_tutor WHERE id_booking = ? AND id_tutor = ?");
    $stmtInfo->execute([$idB, $userId]);
    $info = $stmtInfo->fetch();

    $bentrok = false;
    if ($info) {
        $stmtBt = $conn->prepare("SELECT COUNT(*) FROM booking_tutor WHERE id_tutor = ? AND tanggal_sesi = ? AND jam_sesi = ? AND status = 'disetujui' AND id_booking <> ?");
        $stmtBt->execute([$userId, $info['tanggal_sesi'], $info['jam_sesi'], $idB]);
        $bentrok = (int)$stmtBt->fetchColumn() > 0;
    }

    if (!$info) {
        setFlash('error', 'Data booking tidak ditemukan.');
    } elseif ($bentrok) {
        setFlash('error', 'Jadwal bentrok dengan sesi lain yang sudah disetujui pada waktu yang sama.');
    } else {
        $stmtUp = $conn->prepare("UPDATE booking_tutor SET status = 'disetujui', link_meeting = ? WHERE id_booking = ? AND id_tutor = ? AND status IN ('pending','disetujui')");
        $stmtUp->execute([$link ?: 'https://meet.google.com/qsy-mentoring', $idB, $userId]);
        setFlash('success', 'Sesi mentoring disetujui! Tautan meeting telah dibagikan kepada pelajar.');
    }
    header("Location: " . base_url('pengajar/booking.php'));
    exit();
}

// Handle Tandai Selesai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'selesaikan_booking') {
    $idB = (int)$_POST['id_booking'];
    $stmtSelesai = $conn->prepare("UPDATE booking_tutor SET status = 'selesai' WHERE id_booking = ? AND id_tutor = ? AND status = 'disetujui'");
    $stmtSelesai->execute([$idB, $userId]);
    setFlash('success', 'Sesi mentoring telah ditandai selesai.');
    header("Location: " . base_url('pengajar/booking.php'));
    exit();
}

// Handle Tolak / Batalkan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tolak_booking') {
    $idB = (int)$_POST['id_booking'];
    $stmtBatal = $conn->prepare("UPDATE booking_tutor SET status = 'dibatalkan', alasan_batal = 'Dibatalkan oleh tutor' WHERE id_booking = ? AND id_tutor = ? AND status IN ('pending','disetujui')");
    $stmtBatal->execute([$idB, $userId]);
    setFlash('info', 'Permintaan booking telah dibatalkan.');
    header("Location: " . base_url('pengajar/booking.php'));
    exit();
}

// Ambil semua permintaan booking untuk pengajar ini
$stmtBookings = $conn->prepare("
    SELECT b.*, u.nama AS nama_mahasiswa, u.email AS email_mahasiswa, u.foto_profil AS foto_mahasiswa, k.nama_kelas
    FROM booking_tutor b
    JOIN users u ON b.id_mahasiswa = u.id_user
    LEFT JOIN kelas k ON b.id_kelas = k.id_kelas
    WHERE b.id_tutor = ?
    ORDER BY b.tanggal_sesi DESC, b.jam_sesi DESC
");
$stmtBookings->execute([$userId]);
$bookings = $stmtBookings->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Jadwal Mentoring</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Manajemen Sesi Mentoring Privat</h1>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-6 space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h2 class="text-sm font-bold text-slate-800">Daftar Pengajuan Sesi (<?= count($bookings); ?>)</h2>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-12 text-slate-400">
            <i class="fa-solid fa-handshake-angle text-4xl mb-3 block"></i>
            <h3 class="text-sm font-bold text-slate-700">Belum Ada Permintaan Mentoring</h3>
            <p class="text-xs text-slate-400 mt-1">Permintaan konsultasi privat dari pelajar akan ditampilkan di sini.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($bookings as $b): ?>
                <?php 
                    $badgeInfo = [
                        'pending'   => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'label' => 'Pending'],
                        'disetujui' => ['bg' => 'bg-blue-100',  'text' => 'text-[#0066CC]',  'label' => 'Disetujui'],
                        'selesai'   => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Selesai'],
                        'dibatalkan'=> ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'label' => 'Dibatalkan'],
                    ][$b['status']] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'label' => $b['status']];
                ?>
                <div class="p-5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-slate-50 transition space-y-3">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <img src="<?= base_url('uploads/profil/' . e($b['foto_mahasiswa'])); ?>" class="w-10 h-10 rounded-xl object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($b['nama_mahasiswa']); ?>&background=0066CC&color=fff';">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800"><?= e($b['nama_mahasiswa']); ?></h3>
                                <p class="text-xs text-slate-400"><?= e($b['email_mahasiswa']); ?> &bull; Kelas: <strong class="text-slate-600"><?= e($b['nama_kelas'] ?: 'Privat Mandiri'); ?></strong></p>
                            </div>
                        </div>

                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $badgeInfo['bg']; ?> <?= $badgeInfo['text']; ?>">
                            <?= $badgeInfo['label']; ?>
                        </span>
                    </div>

                    <div class="text-xs text-slate-600 bg-white p-3.5 rounded-lg border border-slate-100 space-y-1">
                        <div>Jadwal: <strong class="text-[#0066CC]"><?= date('d M Y', strtotime($b['tanggal_sesi'])); ?> pukul <?= date('H:i', strtotime($b['jam_sesi'])); ?> WIB</strong></div>
                        <?php if (!empty($b['catatan'])): ?>
                            <p class="text-slate-500 italic">"<?= e($b['catatan']); ?>"</p>
                        <?php endif; ?>
                    </div>

                    <!-- Ulasan Pelajar jika sesi sudah selesai -->
                    <?php if ($b['status'] === 'selesai' && $b['rating'] !== null): ?>
                        <div class="bg-amber-50/60 p-3 rounded-lg border border-amber-200 text-xs text-amber-900 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold">Ulasan Siswa:</span>
                                <span class="flex text-amber-500">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <i class="fa-solid fa-star <?= ($i <= $b['rating']) ? 'text-amber-400' : 'text-slate-200'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <p class="italic text-slate-700">"<?= e($b['ulasan']); ?>"</p>
                        </div>
                    <?php endif; ?>

                    <!-- Form Aksi Tutor Sesuai Status -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <div>
                            <?php if (!empty($b['link_meeting'])): ?>
                                <a href="<?= e($b['link_meeting']); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm">
                                    <i class="fa-solid fa-video"></i>
                                    <span>Buka Ruang Meet</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php if ($b['status'] === 'pending'): ?>
                                <!-- Form Persetujuan -->
                                <form action="" method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="action" value="setujui_booking">
                                    <input type="hidden" name="id_booking" value="<?= $b['id_booking']; ?>">
                                    <input type="url" name="link_meeting" placeholder="Link Google Meet (opsional)" class="px-3 py-1.5 text-xs rounded-lg border border-slate-300">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition">
                                        Setujui Jadwal
                                    </button>
                                </form>
                                <form action="" method="POST" onsubmit="return confirm('Tolak permohonan ini?');">
                                    <input type="hidden" name="action" value="tolak_booking">
                                    <input type="hidden" name="id_booking" value="<?= $b['id_booking']; ?>">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold border border-red-200 transition">
                                        Tolak
                                    </button>
                                </form>
                            <?php elseif ($b['status'] === 'disetujui'): ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="selesaikan_booking">
                                    <input type="hidden" name="id_booking" value="<?= $b['id_booking']; ?>">
                                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#10B981] hover:bg-emerald-600 text-white text-xs font-bold transition shadow-sm">
                                        Tandai Selesai Mentoring
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
