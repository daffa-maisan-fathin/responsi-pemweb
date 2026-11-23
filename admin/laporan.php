<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('admin');

$pageTitle = 'Laporan Pengguna — Questudy';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['action'] ?? '';
    $idL = (int)($_POST['id_laporan'] ?? 0);

    $stmtGet = $conn->prepare("SELECT id_terlapor FROM laporan WHERE id_laporan = ?");
    $stmtGet->execute([$idL]);
    $lap = $stmtGet->fetch();

    if ($lap) {
        if ($aksi === 'proses') {
            $conn->prepare("UPDATE laporan SET status = 'diproses' WHERE id_laporan = ?")->execute([$idL]);
            setFlash('info', 'Laporan ditandai sedang diproses.');
        } elseif ($aksi === 'peringatan') {
            $conn->prepare("UPDATE laporan SET status = 'selesai', tindakan = 'Peringatan diberikan' WHERE id_laporan = ?")->execute([$idL]);
            setFlash('success', 'Laporan selesai dengan tindakan peringatan.');
        } elseif ($aksi === 'tangguhkan' && $lap['id_terlapor']) {
            $conn->prepare("UPDATE users SET status_akun = 'nonaktif' WHERE id_user = ? AND role <> 'admin'")->execute([$lap['id_terlapor']]);
            $conn->prepare("UPDATE laporan SET status = 'selesai', tindakan = 'Akun ditangguhkan' WHERE id_laporan = ?")->execute([$idL]);
            setFlash('success', 'Akun terlapor ditangguhkan dan laporan diselesaikan.');
        } elseif ($aksi === 'abaikan') {
            $conn->prepare("UPDATE laporan SET status = 'selesai', tindakan = 'Tidak ada pelanggaran' WHERE id_laporan = ?")->execute([$idL]);
            setFlash('info', 'Laporan ditutup tanpa tindakan.');
        }
    }
    header("Location: " . base_url('admin/laporan.php'));
    exit();
}

$daftar = $conn->query("
    SELECT l.*, p.nama AS nama_pelapor, t.nama AS nama_terlapor, t.status_akun AS status_terlapor
    FROM laporan l
    JOIN users p ON l.id_pelapor = p.id_user
    LEFT JOIN users t ON l.id_terlapor = t.id_user
    ORDER BY FIELD(l.status, 'baru', 'diproses', 'selesai'), l.created_at DESC
")->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-800">Laporan Pengguna</h1>
    <p class="text-sm text-slate-500">Tinjau laporan, lalu beri peringatan, tangguhkan akun, atau tutup laporan.</p>
</div>

<div class="space-y-4">
    <?php if (empty($daftar)): ?>
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-10 text-center text-sm text-slate-500">Belum ada laporan masuk.</div>
    <?php endif; ?>
    <?php foreach ($daftar as $l): ?>
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-sm font-bold text-slate-800">
                    <?= ucfirst(e($l['jenis'])); ?><?= $l['nama_terlapor'] ? ' — ' . e($l['nama_terlapor']) : ''; ?>
                    <?php if ($l['status_terlapor'] === 'nonaktif'): ?><span class="text-xs font-semibold text-red-600">(akun ditangguhkan)</span><?php endif; ?>
                </div>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full <?= $l['status'] === 'selesai' ? 'bg-emerald-100 text-emerald-800' : ($l['status'] === 'diproses' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800'); ?>"><?= ucfirst(e($l['status'])); ?></span>
            </div>
            <p class="text-sm text-slate-600 mt-2"><?= e($l['alasan']); ?></p>
            <p class="text-xs text-slate-500 mt-1">Dilaporkan oleh <?= e($l['nama_pelapor']); ?> · <?= e($l['created_at']); ?><?= $l['tindakan'] ? ' · Tindakan: ' . e($l['tindakan']) : ''; ?></p>

            <?php if ($l['status'] !== 'selesai'): ?>
                <form action="" method="POST" class="flex flex-wrap gap-2 mt-3">
                    <input type="hidden" name="id_laporan" value="<?= (int)$l['id_laporan']; ?>">
                    <?php if ($l['status'] === 'baru'): ?>
                        <button name="action" value="proses" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">Tandai diproses</button>
                    <?php endif; ?>
                    <button name="action" value="peringatan" class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold">Beri peringatan</button>
                    <?php if ($l['id_terlapor']): ?>
                        <button name="action" value="tangguhkan" onclick="return confirm('Tangguhkan akun terlapor?');" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold">Tangguhkan akun</button>
                    <?php endif; ?>
                    <button name="action" value="abaikan" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">Tutup tanpa tindakan</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
