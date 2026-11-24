<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Booking Tutor Sebaya & Mentoring — Questudy';
$userId = $_SESSION['id_user'];

$errorBooking = '';

// Handle Submit Booking Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'booking_tutor') {
    $idTutor = (int)($_POST['id_tutor'] ?? 0);
    $idKelas = !empty($_POST['id_kelas']) ? (int)$_POST['id_kelas'] : null;
    $tanggalSesi = $_POST['tanggal_sesi'] ?? '';
    $jamSesi = $_POST['jam_sesi'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($idTutor <= 0 || empty($tanggalSesi) || empty($jamSesi)) {
        $errorBooking = 'Tutor, tanggal sesi, dan jam sesi wajib dipilih!';
    } elseif ($tanggalSesi < date('Y-m-d') || ($tanggalSesi === date('Y-m-d') && $jamSesi <= date('H:i'))) {
        $errorBooking = 'Waktu sesi harus berada di masa depan!';
    } else {
        $maksPending = getPengaturan($conn, 'maks_booking_pending', 2);
        $maksMingguan = getPengaturan($conn, 'maks_booking_mingguan', 10);

        $stmtP = $conn->prepare("SELECT COUNT(*) FROM booking_tutor WHERE id_mahasiswa = ? AND status = 'pending'");
        $stmtP->execute([$userId]);
        $jumlahPending = (int)$stmtP->fetchColumn();

        $stmtT = $conn->prepare("SELECT COUNT(*) FROM booking_tutor WHERE id_tutor = ? AND tanggal_sesi = ? AND jam_sesi = ? AND status IN ('pending','disetujui')");
        $stmtT->execute([$idTutor, $tanggalSesi, $jamSesi]);
        $bentrokTutor = (int)$stmtT->fetchColumn() > 0;

        $stmtS = $conn->prepare("SELECT COUNT(*) FROM booking_tutor WHERE id_mahasiswa = ? AND tanggal_sesi = ? AND jam_sesi = ? AND status IN ('pending','disetujui')");
        $stmtS->execute([$userId, $tanggalSesi, $jamSesi]);
        $bentrokSendiri = (int)$stmtS->fetchColumn() > 0;

        $stmtM = $conn->prepare("SELECT COUNT(*) FROM booking_tutor WHERE id_tutor = ? AND YEARWEEK(tanggal_sesi, 1) = YEARWEEK(?, 1) AND status IN ('pending','disetujui','selesai')");
        $stmtM->execute([$idTutor, $tanggalSesi]);
        $jumlahMingguan = (int)$stmtM->fetchColumn();

        if ($jumlahPending >= $maksPending) {
            $errorBooking = 'Anda masih memiliki ' . $jumlahPending . ' booking yang menunggu konfirmasi (maksimal ' . $maksPending . ').';
        } elseif ($bentrokTutor) {
            $errorBooking = 'Tutor sudah memiliki sesi pada tanggal dan jam tersebut. Pilih waktu lain.';
        } elseif ($bentrokSendiri) {
            $errorBooking = 'Anda sudah memiliki sesi pada tanggal dan jam tersebut.';
        } elseif ($jumlahMingguan >= $maksMingguan) {
            $errorBooking = 'Jadwal tutor pada minggu tersebut sudah penuh. Pilih minggu lain.';
        } else {
        $stmtIns = $conn->prepare("
            INSERT INTO booking_tutor (id_mahasiswa, id_tutor, id_kelas, tanggal_sesi, jam_sesi, catatan, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmtIns->execute([$userId, $idTutor, $idKelas, $tanggalSesi, $jamSesi, $catatan ?: null]);

        setFlash('success', 'Permintaan booking mentoring berhasil diajukan! Menunggu konfirmasi dari Tutor.');
        header("Location: " . base_url('pelajar/booking_tutor.php'));
        exit();
        }
    }
}

// Handle Pembatalan Booking oleh Pelajar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batal_booking') {
    $idB = (int)($_POST['id_booking'] ?? 0);
    $alasan = trim($_POST['alasan_batal'] ?? '');
    $stmtGet = $conn->prepare("SELECT tanggal_sesi, jam_sesi, status FROM booking_tutor WHERE id_booking = ? AND id_mahasiswa = ?");
    $stmtGet->execute([$idB, $userId]);
    $bk = $stmtGet->fetch();
    $batasJam = getPengaturan($conn, 'batas_batal_jam', 2);

    if (!$bk || !in_array($bk['status'], ['pending', 'disetujui'])) {
        setFlash('error', 'Booking tidak ditemukan atau tidak bisa dibatalkan.');
    } elseif (strtotime($bk['tanggal_sesi'] . ' ' . $bk['jam_sesi']) - time() < $batasJam * 3600) {
        setFlash('error', 'Pembatalan hanya bisa dilakukan paling lambat ' . $batasJam . ' jam sebelum sesi.');
    } else {
        $stmtBatal = $conn->prepare("UPDATE booking_tutor SET status = 'dibatalkan', alasan_batal = ? WHERE id_booking = ? AND id_mahasiswa = ?");
        $stmtBatal->execute([$alasan ?: 'Dibatalkan oleh pelajar', $idB, $userId]);
        setFlash('info', 'Booking berhasil dibatalkan.');
    }
    header("Location: " . base_url('pelajar/booking_tutor.php'));
    exit();
}

// Ambil daftar Tutor aktif
$stmtTutor = $conn->query("SELECT id_user, nama, keahlian, foto_profil, (SELECT ROUND(AVG(rating), 1) FROM booking_tutor bt WHERE bt.id_tutor = users.id_user AND bt.rating IS NOT NULL) AS rata_rating FROM users WHERE role = 'pengajar' AND status_akun = 'aktif' ORDER BY nama ASC");
$daftarTutor = $stmtTutor->fetchAll();

// Ambil daftar kelas yang diikuti pelajar
$stmtKelas = $conn->prepare("
    SELECT k.id_kelas, k.nama_kelas, k.kode_kelas 
    FROM anggota_kelas ak 
    JOIN kelas k ON ak.id_kelas = k.id_kelas 
    WHERE ak.id_pelajar = ? 
    ORDER BY k.nama_kelas ASC
");
$stmtKelas->execute([$userId]);
$daftarKelas = $stmtKelas->fetchAll();

// Ambil riwayat booking sesi pelajar
$stmtRiwayat = $conn->prepare("
    SELECT b.*, u.nama AS nama_tutor, u.foto_profil AS foto_tutor, u.keahlian AS keahlian_tutor, k.nama_kelas
    FROM booking_tutor b
    JOIN users u ON b.id_tutor = u.id_user
    LEFT JOIN kelas k ON b.id_kelas = k.id_kelas
    WHERE b.id_mahasiswa = ?
    ORDER BY b.tanggal_sesi DESC, b.jam_sesi DESC
");
$stmtRiwayat->execute([$userId]);
$riwayatBooking = $stmtRiwayat->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 01) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Booking Tutor</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Booking Sesi Mentoring Privat</h1>
            <p class="text-xs text-slate-500">Jadwalkan konsultasi akademik daring 1-on-1 bersama Tutor atau Tutor terpercaya.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- FORM AJUKAN BOOKING (1 Kolom) -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm h-fit">
        <h2 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
            <i class="fa-solid fa-calendar-plus text-[#0066CC]"></i>
            <span>Form Pengajuan Sesi</span>
        </h2>
        <p class="text-xs text-slate-500 mb-4">Pilih pengajar dan waktu belajar yang sesuai dengan agenda Anda.</p>

        <?php if (!empty($errorBooking)): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-xs flex items-center gap-2 border border-red-200">
                <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                <span><?= e($errorBooking); ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="booking_tutor">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Tutor</label>
                <select name="id_tutor" required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    <option value="">-- Pilih Tutor Tersedia --</option>
                    <?php foreach ($daftarTutor as $t): ?>
                        <option value="<?= $t['id_user']; ?>"><?= e($t['nama']); ?> — <?= e($t['keahlian'] ?? 'Tutor'); ?><?= $t['rata_rating'] ? ' (rating ' . $t['rata_rating'] . ')' : ''; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Topik Mata Pelajaran / Kelas (Opsional)</label>
                <select name="id_kelas" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    <option value="">-- Umum / Non-Kelas --</option>
                    <?php foreach ($daftarKelas as $k): ?>
                        <option value="<?= $k['id_kelas']; ?>"><?= e($k['nama_kelas']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Sesi</label>
                    <input type="date" name="tanggal_sesi" min="<?= date('Y-m-d'); ?>" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Sesi</label>
                    <input type="time" name="jam_sesi" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kendala Belajar / Rincian Topik</label>
                <textarea name="catatan" rows="3" placeholder="Contoh: Butuh bantuan memahami normalisasi 3NF dan konfigurasi PDO..." class="w-full p-3 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white font-bold text-xs shadow-md shadow-blue-500/20 transition">
                Ajukan Jadwal Sesi
            </button>
        </form>
    </div>

    <!-- TABEL RIWAYAT BOOKING (2 Kolom) -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-sm font-bold text-slate-800">Riwayat Pengajuan Sesi Mentoring</h3>
                <span class="text-xs font-bold text-slate-500"><?= count($riwayatBooking); ?> Sesi</span>
            </div>

            <?php if (empty($riwayatBooking)): ?>
                <div class="text-center py-12 text-slate-400">
                    <i class="fa-solid fa-calendar-xmark text-4xl mb-2 block"></i>
                    <p class="text-xs">Belum ada riwayat booking sesi mentoring.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($riwayatBooking as $rb): ?>
                        <?php 
                            $statusBadge = [
                                'pending'   => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'label' => 'Menunggu Konfirmasi'],
                                'disetujui' => ['bg' => 'bg-blue-100',  'text' => 'text-[#0066CC]',  'label' => 'Disetujui'],
                                'selesai'   => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Selesai'],
                                'dibatalkan'=> ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'label' => 'Dibatalkan'],
                            ][$rb['status']] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'label' => $rb['status']];
                        ?>
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-slate-50 transition space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <img src="<?= base_url('uploads/profil/' . e($rb['foto_tutor'])); ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($rb['nama_tutor']); ?>&background=0066CC&color=fff';">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800"><?= e($rb['nama_tutor']); ?></h4>
                                        <p class="text-[11px] text-slate-400"><?= e($rb['nama_kelas'] ?: 'Konsultasi Mandiri'); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $statusBadge['bg']; ?> <?= $statusBadge['text']; ?>">
                                        <?= $statusBadge['label']; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="text-xs text-slate-600 bg-white p-3 rounded-lg border border-slate-100">
                                <div class="font-semibold text-slate-700">Jadwal: <span class="text-[#0066CC]"><?= date('d M Y', strtotime($rb['tanggal_sesi'])); ?> pukul <?= date('H:i', strtotime($rb['jam_sesi'])); ?> WIB</span></div>
                                <?php if (!empty($rb['catatan'])): ?>
                                    <p class="mt-1 text-slate-500 italic">"<?= e($rb['catatan']); ?>"</p>
                                <?php endif; ?>
                            </div>

                            <!-- Link Meeting & Aksi Rating -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                <div>
                                    <?php if ($rb['status'] === 'disetujui' && !empty($rb['link_meeting'])): ?>
                                        <a href="<?= e($rb['link_meeting']); ?>" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm animate-pulse">
                                            <span class="w-2 h-2 rounded-full bg-white"></span>
                                            <i class="fa-solid fa-video"></i>
                                            <span>Masuk Sesi Mentoring</span>
                                        </a>
                                    <?php elseif ($rb['status'] === 'disetujui'): ?>
                                        <span class="text-[11px] text-slate-400 italic">Tautan meeting akan segera diperbarui tutor</span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <?php if ($rb['status'] === 'selesai'): ?>
                                        <?php if ($rb['rating'] !== null): ?>
                                            <div class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 px-3 py-1 rounded-lg text-xs font-bold text-amber-600">
                                                <span>Rating Anda:</span>
                                                <span class="flex text-amber-500">
                                                    <?php for ($i=1; $i<=5; $i++): ?>
                                                        <i class="fa-solid fa-star <?= ($i <= $rb['rating']) ? 'text-amber-400' : 'text-slate-200'; ?>"></i>
                                                    <?php endfor; ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <a href="<?= base_url('pelajar/rating_ulasan.php?id=' . $rb['id_booking']); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#F59E0B] hover:bg-amber-600 text-white text-xs font-bold transition shadow-sm">
                                                <i class="fa-solid fa-star"></i>
                                                <span>Beri Rating & Ulasan (+30 XP)</span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (in_array($rb['status'], ['pending', 'disetujui'])): ?>
                                <form action="" method="POST" onsubmit="return confirm('Batalkan sesi ini?');" class="flex items-center gap-2 pt-2 border-t border-slate-100">
                                    <input type="hidden" name="action" value="batal_booking">
                                    <input type="hidden" name="id_booking" value="<?= (int)$rb['id_booking']; ?>">
                                    <input type="text" name="alasan_batal" placeholder="Alasan pembatalan (opsional)" class="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-300">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold">Batalkan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
