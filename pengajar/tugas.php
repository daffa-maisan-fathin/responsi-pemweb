<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Penilaian Tugas Pelajar — Questudy';
$userId = $_SESSION['id_user'];

$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;
$idTugasAktif = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil semua kelas yang diampu pengajar
$stmtK = $conn->prepare("SELECT id_kelas, nama_kelas, kode_kelas FROM kelas WHERE id_pengajar = ? ORDER BY nama_kelas ASC");
$stmtK->execute([$userId]);
$myClasses = $stmtK->fetchAll();

if ($idKelas === 0 && !empty($myClasses)) {
    $idKelas = (int)$myClasses[0]['id_kelas'];
}

$errorTugas = '';

// Handle Buat Tugas Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_tugas') {
    $targetKelas = (int)$_POST['id_kelas'];
    $judul = trim($_POST['judul'] ?? '');
    $petunjuk = trim($_POST['petunjuk'] ?? '');
    $tenggat = $_POST['tenggat_waktu'] ?? '';

    if (empty($judul) || empty($petunjuk) || empty($tenggat)) {
        $errorTugas = 'Seluruh kolom tugas wajib diisi!';
    } else {
        $stmtIns = $conn->prepare("INSERT INTO tugas (id_kelas, judul, petunjuk, tenggat_waktu, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmtIns->execute([$targetKelas, $judul, $petunjuk, $tenggat]);

        setFlash('success', 'Tugas baru berhasil dipublikasikan ke kelas!');
        header("Location: " . base_url('pengajar/tugas.php?id_kelas=' . $targetKelas));
        exit();
    }
}

// Handle Hapus Tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_tugas') {
    $idDel = (int)$_POST['id_tugas'];
    $stmtDel = $conn->prepare("
        DELETE t FROM tugas t 
        JOIN kelas k ON t.id_kelas = k.id_kelas 
        WHERE t.id_tugas = ? AND k.id_pengajar = ?
    ");
    $stmtDel->execute([$idDel, $userId]);
    setFlash('info', 'Tugas berhasil dihapus.');
    header("Location: " . base_url('pengajar/tugas.php?id_kelas=' . $idKelas));
    exit();
}

// Handle Simpan Nilai & Komentar Tutor (Screen 10)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'beri_nilai') {
    $idKumpul = (int)$_POST['id_kumpul'];
    $idTugas = (int)$_POST['id_tugas'];
    $nilai = (int)($_POST['nilai'] ?? 0);
    $komentar = trim($_POST['komentar_pengajar'] ?? '');

    $nilai = max(0, min(100, $nilai)); // Clamp 0-100

    $stmtScore = $conn->prepare("
        UPDATE pengumpulan_tugas pt
        JOIN tugas t ON pt.id_tugas = t.id_tugas
        JOIN kelas k ON t.id_kelas = k.id_kelas
        SET pt.nilai = ?, pt.komentar_pengajar = ?
        WHERE pt.id_kumpul = ? AND k.id_pengajar = ?
    ");
    $stmtScore->execute([$nilai, $komentar ?: null, $idKumpul, $userId]);

    setFlash('success', 'Nilai dan feedback pengajar berhasil disimpan!');
    header("Location: " . base_url('pengajar/tugas.php?id_kelas=' . $idKelas . '&id=' . $idTugas));
    exit();
}

// Ambil daftar tugas di kelas terpilih
$tugasList = [];
if ($idKelas > 0) {
    $stmtT = $conn->prepare("
        SELECT t.*,
            (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.id_tugas = t.id_tugas) AS total_dikumpul,
            (SELECT COUNT(*) FROM anggota_kelas ak WHERE ak.id_kelas = t.id_kelas) AS total_mahasiswa
        FROM tugas t
        WHERE t.id_kelas = ?
        ORDER BY t.tenggat_waktu ASC
    ");
    $stmtT->execute([$idKelas]);
    $tugasList = $stmtT->fetchAll();
}

// Tentukan tugas aktif
$tugasAktif = null;
$pengumpulanList = [];

if (!empty($tugasList)) {
    if ($idTugasAktif > 0) {
        foreach ($tugasList as $tl) {
            if ($tl['id_tugas'] === $idTugasAktif) {
                $tugasAktif = $tl;
                break;
            }
        }
    }
    if (!$tugasAktif) {
        $tugasAktif = $tugasList[0];
        $idTugasAktif = $tugasAktif['id_tugas'];
    }

    // Ambil seluruh pelajar di kelas beserta pengumpulan tugasnya (Screen 10)
    $stmtKumpul = $conn->prepare("
        SELECT u.id_user, u.nama, u.email, u.foto_profil,
               pt.id_kumpul, pt.jawaban_teks, pt.file_jawaban, pt.waktu_kumpul, pt.status AS status_kumpul, pt.nilai, pt.komentar_pengajar
        FROM anggota_kelas ak
        JOIN users u ON ak.id_pelajar = u.id_user
        LEFT JOIN pengumpulan_tugas pt ON pt.id_tugas = ? AND pt.id_pelajar = u.id_user
        WHERE ak.id_kelas = ?
        ORDER BY pt.waktu_kumpul DESC, u.nama ASC
    ");
    $stmtKumpul->execute([$idTugasAktif, $idKelas]);
    $pengumpulanList = $stmtKumpul->fetchAll();
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 10) -->
<div class="space-y-1 mb-6">
    <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Penilaian Tugas</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Penilaian & Evaluasi Tugas</h1>
        </div>
        <div>
            <form action="" method="GET" class="flex items-center gap-2">
                <select name="id_kelas" onchange="this.form.submit()" class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0066CC] shadow-sm">
                    <?php foreach ($myClasses as $mc): ?>
                        <option value="<?= $mc['id_kelas']; ?>" <?= ($idKelas === $mc['id_kelas']) ? 'selected' : ''; ?>>
                            <?= e($mc['nama_kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- SISI KIRI (1 Kolom): FORM TAMBAH & LIST TUGAS -->
    <div class="space-y-5">
        
        <!-- Form Buat Tugas -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus text-[#0066CC]"></i>
                <span>Buat Penugasan Baru</span>
            </h3>

            <?php if (!empty($errorTugas)): ?>
                <div class="p-2.5 rounded-lg bg-red-50 text-red-700 text-xs border border-red-200">
                    <?= e($errorTugas); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="tambah_tugas">
                <input type="hidden" name="id_kelas" value="<?= $idKelas; ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Tugas</label>
                    <input type="text" name="judul" required placeholder="Contoh: Tugas 03 — Query Agregasi & Join" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tenggat Waktu Pengumpulan</label>
                    <input type="datetime-local" name="tenggat_waktu" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Petunjuk & Rincian Tugas</label>
                    <textarea name="petunjuk" rows="3" required placeholder="Jelaskan spesifikasi tugas, format unggahan, dan kriteria penilaian..." class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
                </div>

                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                    Publikasikan Tugas
                </button>
            </form>
        </div>

        <!-- Daftar Tugas di Kelas Ini -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm space-y-3">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-100">
                Daftar Tugas Kelas (<?= count($tugasList); ?>)
            </h4>

            <?php if (empty($tugasList)): ?>
                <p class="text-xs text-slate-400 py-4 text-center">Belum ada tugas di kelas ini.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($tugasList as $t): ?>
                        <?php $isAct = ($tugasAktif && $tugasAktif['id_tugas'] === $t['id_tugas']); ?>
                        <div class="p-3 rounded-xl border transition flex items-center justify-between gap-2 <?= $isAct ? 'border-[#0066CC] bg-blue-50/70 shadow-sm' : 'border-slate-100 bg-slate-50/60 hover:bg-slate-50'; ?>">
                            <a href="<?= base_url('pengajar/tugas.php?id_kelas=' . $idKelas . '&id=' . $t['id_tugas']); ?>" class="flex-1 truncate">
                                <h5 class="text-xs font-bold text-slate-800 line-clamp-1"><?= e($t['judul']); ?></h5>
                                <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                    <span>Tenggat: <?= date('d M, H:i', strtotime($t['tenggat_waktu'])); ?></span>
                                    <span class="font-bold text-[#0066CC]"><?= (int)$t['total_dikumpul']; ?>/<?= (int)$t['total_mahasiswa']; ?> Kumpul</span>
                                </div>
                            </a>
                            <form action="" method="POST" onsubmit="return confirm('Hapus tugas ini beserta seluruh pengumpulan pelajar?');">
                                <input type="hidden" name="action" value="hapus_tugas">
                                <input type="hidden" name="id_tugas" value="<?= $t['id_tugas']; ?>">
                                <button type="submit" class="text-slate-400 hover:text-red-500 p-1">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- SISI KANAN (2 Kolom): TABEL PENILAIAN TUGAS (Screen 10) -->
    <div class="lg:col-span-2 space-y-6">

        <?php if (!$tugasAktif): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                <i class="fa-solid fa-clipboard-check text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Pilih Tugas untuk Dinilai</h3>
                <p class="text-xs text-slate-400 mt-1">Pilih salah satu tugas di samping untuk memeriksa pengumpulan pelajar dan menginput nilai.</p>
            </div>
        <?php else: ?>

            <!-- Header Info Tugas Aktif -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                            Tenggat: <?= date('d M Y, H:i', strtotime($tugasAktif['tenggat_waktu'])); ?> WIB
                        </span>
                        <h2 class="text-lg font-extrabold text-slate-800 mt-1"><?= e($tugasAktif['judul']); ?></h2>
                    </div>
                    <div class="text-xs font-bold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                        Total Mengumpulkan: <span class="text-[#0066CC] font-black"><?= (int)$tugasAktif['total_dikumpul']; ?> / <?= (int)$tugasAktif['total_mahasiswa']; ?></span>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed bg-slate-50/70 p-3 rounded-lg border border-slate-100 whitespace-pre-line"><?= e($tugasAktif['petunjuk']); ?></p>
            </div>

            <!-- TABEL PENILAIAN PELAJAR (Screen 10) -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex items-center justify-between">
                    <span>Daftar Pengumpulan Tugas Pelajar</span>
                    <span class="text-[11px] text-slate-500 font-normal">Input nilai skala 0–100 dan komentar pengajar</span>
                </div>

                <?php if (empty($pengumpulanList)): ?>
                    <p class="text-xs text-slate-400 p-8 text-center italic">Tidak ada pelajar terdaftar di kelas ini.</p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($pengumpulanList as $row): ?>
                            <?php 
                                $sudahKumpul = !empty($row['status_kumpul']);
                                $isTerlambat = ($row['status_kumpul'] === 'terlambat');
                            ?>
                            <div class="p-5 hover:bg-slate-50/50 transition space-y-3">
                                
                                <!-- Baris Atas: Profil Pelajar & Status Pengumpulan -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= base_url('uploads/profil/' . e($row['foto_profil'])); ?>" class="w-9 h-9 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['nama']); ?>&background=0066CC&color=fff';">
                                        <div>
                                            <span class="text-xs font-bold text-slate-800"><?= e($row['nama']); ?></span>
                                            <span class="text-[10px] text-slate-400 block"><?= e($row['email']); ?></span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <?php if ($sudahKumpul): ?>
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $isTerlambat ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800'; ?>">
                                                <?= $isTerlambat ? 'Terlambat' : 'Tepat Waktu'; ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400">
                                                (<?= date('d M, H:i', strtotime($row['waktu_kumpul'])); ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                                Belum Mengumpulkan
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Isi Jawaban & Berkas Jika Sudah Mengumpulkan -->
                                <?php if ($sudahKumpul): ?>
                                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-2">
                                        <?php if (!empty($row['jawaban_teks'])): ?>
                                            <div>
                                                <span class="font-bold text-slate-600 block text-[10px] uppercase">Jawaban / Catatan Siswa:</span>
                                                <p class="mt-0.5 whitespace-pre-line"><?= e($row['jawaban_teks']); ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($row['file_jawaban'])): ?>
                                            <div class="flex items-center gap-2 pt-1 border-t border-slate-200">
                                                <span class="font-bold text-slate-600 text-[10px] uppercase">Berkas Unggahan:</span>
                                                <a href="<?= base_url('uploads/tugas/' . e($row['file_jawaban'])); ?>" download class="text-[#0066CC] font-bold hover:underline flex items-center gap-1">
                                                    <i class="fa-solid fa-download"></i> <?= e($row['file_jawaban']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Form Input Nilai & Komentar Tutor -->
                                    <form action="" method="POST" class="flex flex-col sm:flex-row items-center gap-3 pt-1">
                                        <input type="hidden" name="action" value="beri_nilai">
                                        <input type="hidden" name="id_kumpul" value="<?= $row['id_kumpul']; ?>">
                                        <input type="hidden" name="id_tugas" value="<?= $tugasAktif['id_tugas']; ?>">

                                        <div class="w-full sm:w-28">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Nilai (0-100)</label>
                                            <input type="number" name="nilai" min="0" max="100" value="<?= ($row['nilai'] !== null) ? (int)$row['nilai'] : ''; ?>" required placeholder="0–100" class="w-full px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                                        </div>

                                        <div class="w-full sm:flex-1">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Komentar / Feedback Tutor</label>
                                            <input type="text" name="komentar_pengajar" value="<?= e($row['komentar_pengajar'] ?? ''); ?>" placeholder="Catatan evaluasi atau saran perbaikan..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                                        </div>

                                        <div class="w-full sm:w-auto pt-4 sm:pt-4">
                                            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                                                Simpan Nilai
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
