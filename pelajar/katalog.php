<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Katalog Kelas — Questudy';
$userId = $_SESSION['id_user'];

// Proses gabung kelas dari katalog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'gabung') {
    $hasil = gabungKelas($conn, $userId, (int)($_POST['id_kelas'] ?? 0));
    setFlash($hasil['ok'] ? 'success' : 'error', $hasil['pesan']);
    header("Location: " . base_url('pelajar/katalog.php'));
    exit();
}

$q = trim($_GET['q'] ?? '');
$kategori = $_GET['kategori'] ?? '';
$tingkat = $_GET['tingkat'] ?? '';

$sql = "
    SELECT k.*, u.nama AS nama_tutor, u.keahlian AS keahlian_tutor,
        (SELECT COUNT(*) FROM anggota_kelas a WHERE a.id_kelas = k.id_kelas) AS terisi,
        (SELECT COUNT(*) FROM anggota_kelas a2 WHERE a2.id_kelas = k.id_kelas AND a2.id_pelajar = ?) AS sudah_gabung,
        (SELECT ROUND(AVG(b.rating), 1) FROM booking_tutor b WHERE b.id_tutor = k.id_pengajar AND b.rating IS NOT NULL) AS rating_tutor
    FROM kelas k
    JOIN users u ON k.id_pengajar = u.id_user
    WHERE u.status_akun = 'aktif'
";
$params = [$userId];
if ($q !== '') {
    $sql .= " AND (k.nama_kelas LIKE ? OR u.nama LIKE ? OR u.keahlian LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($kategori !== '' && in_array($kategori, daftarKategori())) {
    $sql .= " AND k.kategori = ?";
    $params[] = $kategori;
}
if (in_array($tingkat, ['pemula', 'menengah', 'lanjut'])) {
    $sql .= " AND k.tingkat = ?";
    $params[] = $tingkat;
}
$sql .= " ORDER BY k.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$daftarKelas = $stmt->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-800">Katalog Kelas</h1>
    <p class="text-sm text-slate-500">Temukan kelas keterampilan dari tutor, lalu gabung langsung.</p>
</div>

<form action="" method="GET" class="bg-white rounded-xl border border-[#E2E8F0] p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
    <input type="text" name="q" value="<?= e($q); ?>" placeholder="Cari kelas, tutor, atau keahlian" class="md:col-span-2 px-3.5 py-2.5 text-sm rounded-xl border border-slate-300">
    <select name="kategori" class="px-3 py-2.5 text-sm rounded-xl border border-slate-300">
        <option value="">Semua kategori</option>
        <?php foreach (daftarKategori() as $kat): ?>
            <option value="<?= e($kat); ?>" <?= ($kategori === $kat) ? 'selected' : ''; ?>><?= e($kat); ?></option>
        <?php endforeach; ?>
    </select>
    <div class="flex gap-2">
        <select name="tingkat" class="flex-1 px-3 py-2.5 text-sm rounded-xl border border-slate-300">
            <option value="">Semua tingkat</option>
            <option value="pemula" <?= ($tingkat === 'pemula') ? 'selected' : ''; ?>>Pemula</option>
            <option value="menengah" <?= ($tingkat === 'menengah') ? 'selected' : ''; ?>>Menengah</option>
            <option value="lanjut" <?= ($tingkat === 'lanjut') ? 'selected' : ''; ?>>Lanjut</option>
        </select>
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold transition">Cari</button>
    </div>
</form>

<?php if (empty($daftarKelas)): ?>
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-10 text-center text-sm text-slate-500">
        Belum ada kelas yang cocok. Coba ubah kata kunci atau filter.
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($daftarKelas as $k):
            $penuh = ((int)$k['terisi'] >= (int)$k['kuota']);
            $persen = min(100, round(((int)$k['terisi'] / max(1, (int)$k['kuota'])) * 100));
        ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 flex flex-col">
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0066CC] text-xs font-bold"><?= e($k['kategori']); ?></span>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold"><?= ucfirst(e($k['tingkat'])); ?></span>
                </div>
                <h2 class="text-base font-bold text-slate-800"><?= e($k['nama_kelas']); ?></h2>
                <p class="text-sm text-slate-500 mt-1">
                    Tutor: <?= e($k['nama_tutor']); ?>
                    <?php if ($k['rating_tutor']): ?> · rating <?= e($k['rating_tutor']); ?><?php endif; ?>
                </p>
                <p class="text-sm text-slate-600 mt-3 flex-1"><?= e(mb_strimwidth((string)$k['deskripsi'], 0, 110, '...')); ?></p>

                <div class="mt-4">
                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                        <span>Peserta</span><span><?= (int)$k['terisi']; ?> / <?= (int)$k['kuota']; ?></span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full bg-[#0066CC]" style="width: <?= $persen; ?>%"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <?php if ((int)$k['sudah_gabung'] > 0): ?>
                        <a href="<?= base_url('pelajar/kelas.php'); ?>" class="block text-center py-2.5 rounded-xl bg-slate-100 text-slate-600 text-sm font-bold">Sudah bergabung</a>
                    <?php elseif ($penuh): ?>
                        <span class="block text-center py-2.5 rounded-xl bg-slate-100 text-slate-400 text-sm font-bold">Kelas penuh</span>
                    <?php else: ?>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="gabung">
                            <input type="hidden" name="id_kelas" value="<?= (int)$k['id_kelas']; ?>">
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold transition">Gabung kelas</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
