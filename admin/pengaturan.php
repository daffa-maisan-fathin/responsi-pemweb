<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('admin');

$pageTitle = 'Kuota dan Batas — Questudy';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan') {
    $stmtUp = $conn->prepare("UPDATE pengaturan_sistem SET nilai = ? WHERE kunci = ?");
    foreach (($_POST['nilai'] ?? []) as $kunci => $nilai) {
        $nilai = (int)$nilai;
        if ($nilai >= 1 && $nilai <= 1000) {
            $stmtUp->execute([$nilai, $kunci]);
        }
    }
    setFlash('success', 'Pengaturan kuota dan batas berhasil disimpan.');
    header("Location: " . base_url('admin/pengaturan.php'));
    exit();
}

$daftar = $conn->query("SELECT * FROM pengaturan_sistem ORDER BY kunci ASC")->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-800">Kuota dan Batas</h1>
    <p class="text-sm text-slate-500">Atur batas agar platform tidak kewalahan. Perubahan langsung berlaku.</p>
</div>

<form action="" method="POST" class="bg-white rounded-xl border border-[#E2E8F0] p-5 max-w-2xl space-y-4">
    <input type="hidden" name="action" value="simpan">
    <?php foreach ($daftar as $p): ?>
        <div class="flex items-center justify-between gap-4">
            <label class="text-sm text-slate-700" for="p_<?= e($p['kunci']); ?>"><?= e($p['keterangan']); ?></label>
            <input id="p_<?= e($p['kunci']); ?>" type="number" min="1" max="1000" name="nilai[<?= e($p['kunci']); ?>]" value="<?= (int)$p['nilai']; ?>" class="w-24 px-3 py-2 text-sm rounded-xl border border-slate-300 text-right">
        </div>
    <?php endforeach; ?>
    <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold transition">Simpan</button>
</form>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
