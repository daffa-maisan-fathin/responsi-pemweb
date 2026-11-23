<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('admin');

$pageTitle = 'Aturan & Moderasi — Questudy';
$userId = $_SESSION['id_user'];

// Handle Update Aturan Gamifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_gamifikasi') {
    $xpValues = $_POST['xp'] ?? []; // [id_aturan => xp_didapat]

    $stmtUpG = $conn->prepare("UPDATE gamifikasi_aturan SET xp_didapat = ? WHERE id_aturan = ?");
    foreach ($xpValues as $idA => $xpVal) {
        $stmtUpG->execute([(int)$xpVal, (int)$idA]);
    }
    setFlash('success', 'Pengaturan bobot poin XP gamifikasi berhasil diperbarui!');
    header("Location: " . base_url('admin/aturan_moderasi.php'));
    exit();
}

// Handle Update Aturan Ambang Batas Peringatan Dini
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_peringatan') {
    $maxTelat = (int)($_POST['max_telat_tugas'] ?? 2);
    $minNilai = (int)($_POST['min_rata_nilai'] ?? 65);
    $maxInaktif = (int)($_POST['max_hari_tidak_login'] ?? 5);

    $stmtUpP = $conn->prepare("
        UPDATE peringatan_dini_aturan 
        SET max_telat_tugas = ?, min_rata_nilai = ?, max_hari_tidak_login = ? 
        WHERE id_rule = 1
    ");
    $stmtUpP->execute([$maxTelat, $minNilai, $maxInaktif]);
    setFlash('success', 'Ambang batas Early Warning System berhasil disesuaikan!');
    header("Location: " . base_url('admin/aturan_moderasi.php'));
    exit();
}

// Handle Moderasi: Hapus Materi Pelanggaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_materi_moderasi') {
    $idMateri = (int)$_POST['id_materi'];
    $stmtDelM = $conn->prepare("DELETE FROM materi WHERE id_materi = ?");
    $stmtDelM->execute([$idMateri]);
    setFlash('info', 'Materi berhasil dihapus oleh kebijakan moderasi.');
    header("Location: " . base_url('admin/aturan_moderasi.php'));
    exit();
}

// Handle Moderasi: Hapus Komentar Forum Pelanggaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_forum_moderasi') {
    $idForum = (int)$_POST['id_forum'];
    $stmtDelF = $conn->prepare("DELETE FROM forum_materi WHERE id_forum = ?");
    $stmtDelF->execute([$idForum]);
    setFlash('info', 'Komentar forum berhasil dihapus oleh kebijakan moderasi.');
    header("Location: " . base_url('admin/aturan_moderasi.php'));
    exit();
}

// Ambil Aturan Gamifikasi
$stmtG = $conn->query("SELECT * FROM gamifikasi_aturan ORDER BY id_aturan ASC");
$aturanGamifikasi = $stmtG->fetchAll();

// Ambil Aturan Peringatan Dini
$stmtP = $conn->query("SELECT * FROM peringatan_dini_aturan LIMIT 1");
$rulePeringatan = $stmtP->fetch() ?: ['max_telat_tugas' => 2, 'min_rata_nilai' => 65, 'max_hari_tidak_login' => 5];

// Ambil Daftar Materi untuk Panel Moderasi
$stmtMatMod = $conn->query("
    SELECT m.id_materi, m.judul, m.tipe, m.created_at, k.nama_kelas, u.nama AS nama_pengajar
    FROM materi m
    JOIN kelas k ON m.id_kelas = k.id_kelas
    JOIN users u ON k.id_pengajar = u.id_user
    ORDER BY m.created_at DESC
    LIMIT 10
");
$daftarMateriModerasi = $stmtMatMod->fetchAll();

// Ambil Daftar Komentar Forum untuk Panel Moderasi
$stmtForMod = $conn->query("
    SELECT fm.id_forum, fm.isi_komentar, fm.created_at, u.nama AS nama_pengirim, u.role, m.judul AS judul_materi, k.nama_kelas
    FROM forum_materi fm
    JOIN users u ON fm.id_user = u.id_user
    JOIN materi m ON fm.id_materi = m.id_materi
    JOIN kelas k ON m.id_kelas = k.id_kelas
    ORDER BY fm.created_at DESC
    LIMIT 10
");
$daftarForumModerasi = $stmtForMod->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 14) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('admin/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Aturan & Moderasi</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Konfigurasi Aturan & Moderasi Konten</h1>
            <p class="text-xs text-slate-500">Sesuaikan formula XP gamifikasi, batas sensitivitas Early Warning, dan pantau konten perkuliahan.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- FORM 1: ATURAN GAMIFIKASI (XP) (Screen 14) -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <i class="fa-solid fa-trophy text-amber-500 text-lg"></i>
            <div>
                <h2 class="text-base font-bold text-slate-800">Formula Poin Gamifikasi (XP)</h2>
                <p class="text-xs text-slate-400">Atur besaran XP yang diperoleh pelajar pada tiap aksi.</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="update_gamifikasi">

            <div class="space-y-2.5">
                <?php foreach ($aturanGamifikasi as $at): ?>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block"><?= e($at['keterangan']); ?></span>
                            <span class="text-[10px] text-slate-400 font-mono"><?= e($at['aksi']); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="number" name="xp[<?= $at['id_aturan']; ?>]" value="<?= (int)$at['xp_didapat']; ?>" min="1" max="500" required class="w-20 px-2.5 py-1 text-xs font-bold text-center rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC]">
                            <span class="text-xs font-bold text-amber-600">XP</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pt-3 text-right">
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                    Simpan Nilai XP
                </button>
            </div>
        </form>
    </div>

    <!-- FORM 2: AMBANG BATAS EARLY WARNING SYSTEM (Screen 14) -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
            <i class="fa-solid fa-triangle-exclamation text-red-500 text-lg"></i>
            <div>
                <h2 class="text-base font-bold text-slate-800">Ambang Batas Peringatan Dini (EWS)</h2>
                <p class="text-xs text-slate-400">Kriteria otomatis untuk menandai mahasiswa "Perlu Perhatian".</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_peringatan">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Maksimal Toleransi Keterlambatan Tugas</label>
                <div class="relative">
                    <input type="number" name="max_telat_tugas" value="<?= (int)$rulePeringatan['max_telat_tugas']; ?>" min="1" max="10" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-slate-400">Tugas Terlambat</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Siswa akan ditandai jika jumlah tugas terlambat mencapai atau melebihi angka ini.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Ambang Batas Nilai Rata-rata Minimum</label>
                <div class="relative">
                    <input type="number" name="min_rata_nilai" value="<?= (int)$rulePeringatan['min_rata_nilai']; ?>" min="10" max="100" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-slate-400">Poin (0–100)</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Siswa akan ditandai jika nilai rata-rata tugas berada di bawah nilai batas ini.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Maksimal Hari Tidak Login (Inaktif)</label>
                <div class="relative">
                    <input type="number" name="max_hari_tidak_login" value="<?= (int)$rulePeringatan['max_hari_tidak_login']; ?>" min="1" max="30" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-slate-400">Hari Berturut-turut</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Siswa akan ditandai jika tidak ada rekaman aktivitas login selama hari tersebut.</p>
            </div>

            <div class="pt-3 text-right">
                <button type="submit" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition shadow-sm">
                    Simpan Ambang Batas
                </button>
            </div>
        </form>
    </div>

</div>

<!-- PANEL MODERASI KONTEN (Screen 14) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">

    <!-- MODERASI MATERI -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden space-y-3">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex items-center justify-between">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-shield text-[#0066CC]"></i> Moderasi Materi Kuliah</span>
            <span class="text-[10px] text-slate-400">10 materi terbaru</span>
        </div>

        <div class="p-4 space-y-2.5">
            <?php foreach ($daftarMateriModerasi as $m): ?>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs flex items-center justify-between gap-3">
                    <div class="truncate">
                        <span class="font-bold text-slate-800 block truncate"><?= e($m['judul']); ?></span>
                        <div class="text-[10px] text-slate-400 mt-0.5">
                            Kelas: <strong><?= e($m['nama_kelas']); ?></strong> &bull; Oleh <?= e($m['nama_pengajar']); ?>
                        </div>
                    </div>
                    <form action="" method="POST" onsubmit="return confirm('Hapus materi ini demi kepatuhan kebijakan?');">
                        <input type="hidden" name="action" value="hapus_materi_moderasi">
                        <input type="hidden" name="id_materi" value="<?= $m['id_materi']; ?>">
                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-[10px] border border-red-200">
                            Hapus
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- MODERASI FORUM DISKUSI -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden space-y-3">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex items-center justify-between">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-comment-slash text-[#0066CC]"></i> Moderasi Komentar Forum</span>
            <span class="text-[10px] text-slate-400">10 komentar terbaru</span>
        </div>

        <div class="p-4 space-y-2.5">
            <?php foreach ($daftarForumModerasi as $fm): ?>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5 text-[10px]">
                            <span class="font-bold text-slate-700"><?= e($fm['nama_pengirim']); ?></span>
                            <span class="px-1.5 py-0.2 rounded font-semibold <?= ($fm['role'] === 'pengajar') ? 'bg-blue-100 text-[#0066CC]' : 'bg-slate-200 text-slate-600'; ?>">
                                <?= ucfirst($fm['role']); ?>
                            </span>
                            <span class="text-slate-400">&bull; <?= e($fm['judul_materi']); ?></span>
                        </div>
                        <p class="text-slate-700 line-clamp-2 italic">"<?= e($fm['isi_komentar']); ?>"</p>
                    </div>
                    <form action="" method="POST" onsubmit="return confirm('Hapus komentar forum ini?');">
                        <input type="hidden" name="action" value="hapus_forum_moderasi">
                        <input type="hidden" name="id_forum" value="<?= $fm['id_forum']; ?>">
                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-[10px] border border-red-200 flex-shrink-0">
                            Hapus
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
