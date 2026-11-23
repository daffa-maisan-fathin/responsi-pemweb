<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Kelas Saya — Questudy';
$userId = $_SESSION['id_user'];

$errorJoin = '';

// Proses Gabung Kelas via Kode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gabung_kelas') {
    $kodeInput = strtoupper(trim($_POST['kode_kelas'] ?? ''));

    if (empty($kodeInput)) {
        $errorJoin = 'Silakan masukkan kode kelas!';
    } else {
        // Cari kelas berdasarkan kode unik
        $stmtFind = $conn->prepare("SELECT id_kelas, nama_kelas FROM kelas WHERE kode_kelas = ? LIMIT 1");
        $stmtFind->execute([$kodeInput]);
        $targetKelas = $stmtFind->fetch();

        if (!$targetKelas) {
            $errorJoin = 'Kode kelas tidak ditemukan! Pastikan kode kelas yang dimasukkan sudah benar.';
        } else {
            $idKelas = $targetKelas['id_kelas'];

            // Cek apakah sudah bergabung
            $stmtCek = $conn->prepare("SELECT id_anggota FROM anggota_kelas WHERE id_kelas = ? AND id_pelajar = ?");
            $stmtCek->execute([$idKelas, $userId]);
            if ($stmtCek->fetch()) {
                $errorJoin = 'Anda sudah terdaftar sebagai anggota di kelas ' . $targetKelas['nama_kelas'] . '.';
            } else {
                $stmtJoin = $conn->prepare("INSERT INTO anggota_kelas (id_kelas, id_pelajar, bergabung_pada) VALUES (?, ?, NOW())");
                $stmtJoin->execute([$idKelas, $userId]);

                setFlash('success', 'Berhasil bergabung ke kelas: ' . $targetKelas['nama_kelas'] . '!');
                header("Location: " . base_url('pelajar/kelas.php'));
                exit();
            }
        }
    }
}

// Pencarian
$q = trim($_GET['q'] ?? '');

// Ambil daftar kelas yang diikuti pelajar
$sql = "
    SELECT k.*, u.nama AS nama_pengajar, u.foto_profil AS foto_pengajar, ak.bergabung_pada,
        (SELECT COUNT(*) FROM materi m WHERE m.id_kelas = k.id_kelas) AS total_materi,
        (SELECT COUNT(*) FROM tugas t WHERE t.id_kelas = k.id_kelas) AS total_tugas,
        (SELECT COUNT(*) FROM kuis q WHERE q.id_kelas = k.id_kelas) AS total_kuis
    FROM anggota_kelas ak
    JOIN kelas k ON ak.id_kelas = k.id_kelas
    JOIN users u ON k.id_pengajar = u.id_user
    WHERE ak.id_pelajar = ?
";
$params = [$userId];

if (!empty($q)) {
    $sql .= " AND (k.nama_kelas LIKE ? OR k.kode_kelas LIKE ? OR u.nama LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$sql .= " ORDER BY ak.bergabung_pada DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$kelasList = $stmt->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Kelas Saya</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Daftar Kelas Pembelajaran</h1>
            <p class="text-xs text-slate-500">Akses modul perkuliahan, unduh berkas praktikum, dan ikuti forum tanya jawab.</p>
        </div>
    </div>
</div>

<!-- KARTU GABUNG KELAS & FILTER PENCARIAN -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    
    <!-- FORM GABUNG KELAS -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm md:col-span-1">
        <h2 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
            <i class="fa-solid fa-key text-[#0066CC]"></i>
            <span>Gabung Kelas Baru</span>
        </h2>
        <p class="text-xs text-slate-500 mb-3">Masukkan kode unik 6 karakter dari Pengajar/Dosen Anda.</p>

        <?php if (!empty($errorJoin)): ?>
            <div class="mb-3 p-2.5 rounded-lg bg-red-50 text-red-700 text-xs flex items-center gap-2 border border-red-200">
                <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                <span><?= e($errorJoin); ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="flex gap-2">
            <input type="hidden" name="action" value="gabung_kelas">
            <input type="text" name="kode_kelas" placeholder="Contoh: PW2026" required class="w-full px-3 py-2 text-xs font-bold uppercase rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition flex-shrink-0">
                Gabung
            </button>
        </form>
    </div>

    <!-- PENCARIAN KELAS -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm md:col-span-2 flex flex-col justify-center">
        <h2 class="text-sm font-bold text-slate-800 mb-1">Cari Kelas yang Diikuti</h2>
        <p class="text-xs text-slate-500 mb-3">Filter berdasarkan judul mata kuliah, nama pengajar, atau kode kelas.</p>

        <form action="" method="GET" class="flex gap-2">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="q" value="<?= e($q); ?>" placeholder="Ketik kata kunci pencarian..." class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                Cari
            </button>
            <?php if (!empty($q)): ?>
                <a href="<?= base_url('pelajar/kelas.php'); ?>" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition flex items-center">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

</div>

<!-- GRID KARTU KELAS -->
<?php if (empty($kelasList)): ?>
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
        <div class="w-16 h-16 mx-auto rounded-full bg-blue-50 text-[#0066CC] flex items-center justify-center text-2xl mb-4">
            <i class="fa-solid fa-chalkboard"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800 mb-1">Belum Ada Kelas yang Diikuti</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">Anda belum bergabung ke kelas manapun. Masukkan kode kelas yang diberikan oleh tutor atau dosen Anda untuk memulai.</p>
        <span class="text-xs font-bold text-[#0066CC] bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-200 inline-block">
            Coba kode demo: PW2026 atau BD2026
        </span>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($kelasList as $kls): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm hover:shadow-md transition flex flex-col justify-between overflow-hidden">
                <div>
                    <!-- Banner Header Kelas -->
                    <div class="h-20 bg-gradient-to-r from-[#0F294A] to-[#153660] p-4 text-white flex items-start justify-between">
                        <span class="px-2 py-0.5 rounded bg-blue-500/30 text-blue-200 border border-blue-400/30 text-[10px] font-mono font-bold tracking-wider">
                            <?= e($kls['kode_kelas']); ?>
                        </span>
                        <div class="text-[10px] text-slate-300">
                            Gabung: <?= date('d/m/Y', strtotime($kls['bergabung_pada'])); ?>
                        </div>
                    </div>

                    <div class="p-5 space-y-3">
                        <h3 class="text-base font-bold text-slate-800 line-clamp-1" title="<?= e($kls['nama_kelas']); ?>">
                            <?= e($kls['nama_kelas']); ?>
                        </h3>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            <?= e($kls['deskripsi'] ?: 'Tidak ada deskripsi tambahan untuk kelas ini.'); ?>
                        </p>

                        <!-- Pengajar -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                            <img src="<?= base_url('uploads/profil/' . e($kls['foto_pengajar'])); ?>" class="w-7 h-7 rounded-full object-cover border border-slate-200" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($kls['nama_pengajar']); ?>&background=0066CC&color=fff';">
                            <div class="text-xs font-semibold text-slate-700 truncate">
                                <?= e($kls['nama_pengajar']); ?>
                            </div>
                        </div>

                        <!-- Badge Konten Kelas -->
                        <div class="grid grid-cols-3 gap-2 pt-2 text-center text-[11px] font-semibold text-slate-600 bg-slate-50 p-2 rounded-lg">
                            <div>
                                <span class="block text-slate-800 font-bold"><?= (int)$kls['total_materi']; ?></span>
                                <span class="text-[10px] text-slate-400">Materi</span>
                            </div>
                            <div>
                                <span class="block text-slate-800 font-bold"><?= (int)$kls['total_tugas']; ?></span>
                                <span class="text-[10px] text-slate-400">Tugas</span>
                            </div>
                            <div>
                                <span class="block text-slate-800 font-bold"><?= (int)$kls['total_kuis']; ?></span>
                                <span class="text-[10px] text-slate-400">Kuis</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Aksi -->
                <div class="p-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-2">
                    <a href="<?= base_url('pelajar/materi.php?id_kelas=' . $kls['id_kelas']); ?>" class="flex-1 text-center py-2 px-3 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white font-bold text-xs transition shadow-sm">
                        Buka Materi
                    </a>
                    <a href="<?= base_url('pelajar/tugas.php?id_kelas=' . $kls['id_kelas']); ?>" class="py-2 px-3 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition" title="Lihat Tugas Kelas">
                        <i class="fa-solid fa-list-check"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
