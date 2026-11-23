<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar', 'pengajar');

$pageTitle = 'Laporkan Masalah — Questudy';
$userId = $_SESSION['id_user'];
$errorLaporan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kirim_laporan') {
    $jenis = $_POST['jenis'] ?? '';
    $idTerlapor = (int)($_POST['id_terlapor'] ?? 0);
    $alasan = trim($_POST['alasan'] ?? '');

    if (!in_array($jenis, ['tutor', 'pelajar', 'kelas', 'komentar', 'lainnya'])) {
        $errorLaporan = 'Jenis laporan tidak valid!';
    } elseif (strlen($alasan) < 15) {
        $errorLaporan = 'Jelaskan masalahnya minimal 15 karakter!';
    } elseif ($idTerlapor === $userId) {
        $errorLaporan = 'Anda tidak dapat melaporkan diri sendiri!';
    } else {
        $stmt = $conn->prepare("INSERT INTO laporan (id_pelapor, id_terlapor, jenis, alasan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $idTerlapor ?: null, $jenis, $alasan]);
        setFlash('success', 'Laporan terkirim. Administrator akan meninjaunya.');
        header("Location: " . base_url('laporan.php'));
        exit();
    }
}

$stmtU = $conn->prepare("SELECT id_user, nama, role FROM users WHERE id_user <> ? AND role <> 'admin' AND status_akun = 'aktif' ORDER BY nama ASC LIMIT 300");
$stmtU->execute([$userId]);
$daftarUser = $stmtU->fetchAll();

$stmtR = $conn->prepare("
    SELECT l.*, u.nama AS nama_terlapor
    FROM laporan l
    LEFT JOIN users u ON l.id_terlapor = u.id_user
    WHERE l.id_pelapor = ?
    ORDER BY l.created_at DESC
");
$stmtR->execute([$userId]);
$riwayat = $stmtR->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-800">Laporkan Masalah</h1>
    <p class="text-sm text-slate-500">Laporkan perilaku atau konten yang tidak sesuai agar bisa ditinjau administrator.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <form action="" method="POST" class="lg:col-span-2 bg-white rounded-xl border border-[#E2E8F0] p-5 space-y-4 self-start">
        <input type="hidden" name="action" value="kirim_laporan">
        <?php if ($errorLaporan): ?>
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700"><?= e($errorLaporan); ?></div>
        <?php endif; ?>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Jenis laporan</label>
            <select name="jenis" class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-300">
                <option value="tutor">Tutor</option>
                <option value="pelajar">Pelajar</option>
                <option value="kelas">Kelas atau materi</option>
                <option value="komentar">Komentar di forum</option>
                <option value="lainnya">Lainnya</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Pengguna yang dilaporkan (jika ada)</label>
            <select name="id_terlapor" class="w-full px-3 py-2.5 text-sm rounded-xl border border-slate-300">
                <option value="0">Tidak menyebut pengguna</option>
                <?php foreach ($daftarUser as $u): ?>
                    <option value="<?= (int)$u['id_user']; ?>"><?= e($u['nama']); ?> (<?= $u['role'] === 'pengajar' ? 'Tutor' : 'Pelajar'; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Apa yang terjadi?</label>
            <textarea name="alasan" rows="4" required class="w-full p-3 text-sm rounded-xl border border-slate-300" placeholder="Ceritakan kejadiannya secara singkat dan jelas"></textarea>
        </div>
        <button type="submit" class="w-full py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold transition">Kirim laporan</button>
    </form>

    <div class="lg:col-span-3 bg-white rounded-xl border border-[#E2E8F0] p-5">
        <h2 class="text-base font-bold text-slate-800 mb-3">Riwayat laporan saya</h2>
        <?php if (empty($riwayat)): ?>
            <p class="text-sm text-slate-500">Belum ada laporan yang kamu kirim.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($riwayat as $r): ?>
                    <div class="py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-800"><?= ucfirst(e($r['jenis'])); ?><?= $r['nama_terlapor'] ? ' — ' . e($r['nama_terlapor']) : ''; ?></span>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full <?= $r['status'] === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'; ?>"><?= ucfirst(e($r['status'])); ?></span>
                        </div>
                        <p class="text-sm text-slate-600 mt-1"><?= e($r['alasan']); ?></p>
                        <?php if ($r['tindakan']): ?><p class="text-xs text-slate-500 mt-1">Tindakan: <?= e($r['tindakan']); ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
