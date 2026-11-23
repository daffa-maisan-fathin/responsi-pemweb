<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Kelola Kuis & Bank Soal — Questudy';
$userId = $_SESSION['id_user'];

$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;
$idKuisAktif = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil semua kelas pengajar
$stmtK = $conn->prepare("SELECT id_kelas, nama_kelas, kode_kelas FROM kelas WHERE id_pengajar = ? ORDER BY nama_kelas ASC");
$stmtK->execute([$userId]);
$myClasses = $stmtK->fetchAll();

if ($idKelas === 0 && !empty($myClasses)) {
    $idKelas = (int)$myClasses[0]['id_kelas'];
}

$errorKuis = '';

// Handle Tambah Kuis Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_kuis') {
    $targetKelas = (int)$_POST['id_kelas'];
    $judul = trim($_POST['judul'] ?? '');
    $durasi = (int)($_POST['durasi_menit'] ?? 15);
    $maksCoba = (int)($_POST['maks_percobaan'] ?? 2);
    $tenggat = $_POST['tenggat_waktu'] ?? '';

    if (empty($judul) || empty($tenggat)) {
        $errorKuis = 'Judul kuis dan tenggat waktu wajib diisi!';
    } else {
        $stmtIns = $conn->prepare("
            INSERT INTO kuis (id_kelas, judul, durasi_menit, maks_percobaan, tenggat_waktu, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtIns->execute([$targetKelas, $judul, $durasi, $maksCoba, $tenggat]);

        setFlash('success', 'Kuis baru berhasil dibuat! Silakan tambahkan butir soal di bawah.');
        header("Location: " . base_url('pengajar/kuis.php?id_kelas=' . $targetKelas));
        exit();
    }
}

// Handle Hapus Kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_kuis') {
    $idDel = (int)$_POST['id_kuis'];
    $stmtDel = $conn->prepare("
        DELETE q FROM kuis q 
        JOIN kelas k ON q.id_kelas = k.id_kelas 
        WHERE q.id_kuis = ? AND k.id_pengajar = ?
    ");
    $stmtDel->execute([$idDel, $userId]);
    setFlash('info', 'Kuis berhasil dihapus.');
    header("Location: " . base_url('pengajar/kuis.php?id_kelas=' . $idKelas));
    exit();
}

// Handle Tambah Butir Soal Kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_soal') {
    $targetKuis = (int)$_POST['id_kuis'];
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $opsiA = trim($_POST['opsi_a'] ?? '');
    $opsiB = trim($_POST['opsi_b'] ?? '');
    $opsiC = trim($_POST['opsi_c'] ?? '');
    $opsiD = trim($_POST['opsi_d'] ?? '');
    $kunci = strtolower($_POST['kunci_jawaban'] ?? 'a');

    if (empty($pertanyaan) || empty($opsiA) || empty($opsiB) || empty($opsiC) || empty($opsiD)) {
        setFlash('error', 'Pertanyaan dan seluruh 4 pilihan jawaban wajib diisi!');
    } else {
        $stmtSoalIns = $conn->prepare("
            INSERT INTO soal_kuis (id_kuis, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtSoalIns->execute([$targetKuis, $pertanyaan, $opsiA, $opsiB, $opsiC, $opsiD, $kunci]);
        setFlash('success', 'Soal kuis baru berhasil ditambahkan ke bank soal!');
    }
    header("Location: " . base_url('pengajar/kuis.php?id_kelas=' . $idKelas . '&id=' . $targetKuis));
    exit();
}

// Handle Hapus Soal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_soal') {
    $idSoalDel = (int)$_POST['id_soal'];
    $idKuisRedir = (int)$_POST['id_kuis'];
    $stmtHapusS = $conn->prepare("
        DELETE s FROM soal_kuis s 
        JOIN kuis q ON s.id_kuis = q.id_kuis 
        JOIN kelas k ON q.id_kelas = k.id_kelas 
        WHERE s.id_soal = ? AND k.id_pengajar = ?
    ");
    $stmtHapusS->execute([$idSoalDel, $userId]);
    setFlash('info', 'Butir soal berhasil dihapus.');
    header("Location: " . base_url('pengajar/kuis.php?id_kelas=' . $idKelas . '&id=' . $idKuisRedir));
    exit();
}

// Ambil daftar kuis di kelas terpilih
$daftarKuis = [];
if ($idKelas > 0) {
    $stmtQ = $conn->prepare("
        SELECT q.*,
            (SELECT COUNT(*) FROM soal_kuis s WHERE s.id_kuis = q.id_kuis) AS total_soal,
            (SELECT COUNT(DISTINCT hk.id_pelajar) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis) AS siswa_mengerjakan
        FROM kuis q
        WHERE q.id_kelas = ?
        ORDER BY q.tenggat_waktu ASC
    ");
    $stmtQ->execute([$idKelas]);
    $daftarKuis = $stmtQ->fetchAll();
}

// Tentukan kuis aktif
$kuisAktif = null;
$soalList = [];
$hasilList = [];

if (!empty($daftarKuis)) {
    if ($idKuisAktif > 0) {
        foreach ($daftarKuis as $dq) {
            if ($dq['id_kuis'] === $idKuisAktif) {
                $kuisAktif = $dq;
                break;
            }
        }
    }
    if (!$kuisAktif) {
        $kuisAktif = $daftarKuis[0];
        $idKuisAktif = $kuisAktif['id_kuis'];
    }

    // Ambil daftar soal
    $stmtSList = $conn->prepare("SELECT * FROM soal_kuis WHERE id_kuis = ? ORDER BY id_soal ASC");
    $stmtSList->execute([$idKuisAktif]);
    $soalList = $stmtSList->fetchAll();

    // Ambil rekap pengerjaan mahasiswa
    $stmtHList = $conn->prepare("
        SELECT hk.*, u.nama, u.email, u.foto_profil
        FROM hasil_kuis hk
        JOIN users u ON hk.id_pelajar = u.id_user
        WHERE hk.id_kuis = ?
        ORDER BY hk.skor DESC, hk.waktu_selesai ASC
    ");
    $stmtHList->execute([$idKuisAktif]);
    $hasilList = $stmtHList->fetchAll();
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Kelola Kuis Online</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Manajemen Kuis & Bank Soal Pilihan Ganda</h1>
            <p class="text-xs text-slate-500">Konfigurasi batas durasi menit, batas percobaan, input soal, dan pantau skor mahasiswa.</p>
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

    <!-- SISI KIRI (1 Kolom): FORM BUAT KUIS & DAFTAR KUIS -->
    <div class="space-y-5">
        
        <!-- Form Buat Kuis Baru -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-circle-question text-amber-500"></i>
                <span>Buat Kuis Baru</span>
            </h3>

            <?php if (!empty($errorKuis)): ?>
                <div class="p-2.5 rounded-lg bg-red-50 text-red-700 text-xs border border-red-200">
                    <?= e($errorKuis); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="tambah_kuis">
                <input type="hidden" name="id_kelas" value="<?= $idKelas; ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Kuis</label>
                    <input type="text" name="judul" required placeholder="Contoh: Kuis 02 — PHP PDO & Security" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Durasi (Menit)</label>
                        <input type="number" name="durasi_menit" value="15" min="1" max="180" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Maks Percobaan</label>
                        <input type="number" name="maks_percobaan" value="2" min="1" max="5" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Waktu Ditutup</label>
                    <input type="datetime-local" name="tenggat_waktu" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                    Simpan & Rancang Soal
                </button>
            </form>
        </div>

        <!-- Daftar Kuis Kelas -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm space-y-3">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-100">
                Kuis di Kelas Ini (<?= count($daftarKuis); ?>)
            </h4>

            <?php if (empty($daftarKuis)): ?>
                <p class="text-xs text-slate-400 py-4 text-center">Belum ada kuis yang dibuat.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($daftarKuis as $q): ?>
                        <?php $isAct = ($kuisAktif && $kuisAktif['id_kuis'] === $q['id_kuis']); ?>
                        <div class="p-3 rounded-xl border transition flex items-center justify-between gap-2 <?= $isAct ? 'border-[#0066CC] bg-blue-50/70 shadow-sm' : 'border-slate-100 bg-slate-50/60 hover:bg-slate-50'; ?>">
                            <a href="<?= base_url('pengajar/kuis.php?id_kelas=' . $idKelas . '&id=' . $q['id_kuis']); ?>" class="flex-1 truncate">
                                <h5 class="text-xs font-bold text-slate-800 line-clamp-1"><?= e($q['judul']); ?></h5>
                                <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                    <span><?= (int)$q['durasi_menit']; ?>m &bull; <?= (int)$q['total_soal']; ?> Soal</span>
                                    <span class="font-bold text-[#0066CC]"><?= (int)$q['siswa_mengerjakan']; ?> Mahasiswa</span>
                                </div>
                            </a>
                            <form action="" method="POST" onsubmit="return confirm('Hapus kuis ini beserta bank soal dan hasil ujiannya?');">
                                <input type="hidden" name="action" value="hapus_kuis">
                                <input type="hidden" name="id_kuis" value="<?= $q['id_kuis']; ?>">
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

    <!-- SISI KANAN (2 Kolom): BANK SOAL & REKAP HASIL -->
    <div class="lg:col-span-2 space-y-6">

        <?php if (!$kuisAktif): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                <i class="fa-solid fa-feather-pointed text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Pilih Kuis Online</h3>
                <p class="text-xs text-slate-400 mt-1">Pilih kuis di samping untuk merancang butir bank soal pilihan ganda.</p>
            </div>
        <?php else: ?>

            <!-- KARTU FORM TAMBAH SOAL PILIHAN GANDA -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                            <?= (int)$kuisAktif['durasi_menit']; ?> Menit &bull; Maks <?= (int)$kuisAktif['maks_percobaan']; ?>x Coba
                        </span>
                        <h2 class="text-lg font-extrabold text-slate-800 mt-1"><?= e($kuisAktif['judul']); ?></h2>
                    </div>
                    <span class="text-xs font-bold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border">
                        Total Soal: <strong class="text-[#0066CC]"><?= count($soalList); ?></strong>
                    </span>
                </div>

                <!-- Form Tambah Soal -->
                <form action="" method="POST" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <input type="hidden" name="action" value="tambah_soal">
                    <input type="hidden" name="id_kuis" value="<?= $kuisAktif['id_kuis']; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pertanyaan Soal</label>
                        <textarea name="pertanyaan" rows="2" required placeholder="Tuliskan teks pertanyaan soal..." class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-0.5">Pilihan A</label>
                            <input type="text" name="opsi_a" required placeholder="Jawaban A" class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-0.5">Pilihan B</label>
                            <input type="text" name="opsi_b" required placeholder="Jawaban B" class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-0.5">Pilihan C</label>
                            <input type="text" name="opsi_c" required placeholder="Jawaban C" class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-0.5">Pilihan D</label>
                            <input type="text" name="opsi_d" required placeholder="Jawaban D" class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-700">Kunci Jawaban Benar:</label>
                            <select name="kunci_jawaban" class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC]">
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                            + Tambah Butir Soal
                        </button>
                    </div>
                </form>
            </div>

            <!-- DAFTAR BUTIR SOAL KUIS -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center justify-between">
                    <span>Daftar Butir Bank Soal (<?= count($soalList); ?>)</span>
                </h3>

                <?php if (empty($soalList)): ?>
                    <p class="text-xs text-slate-400 py-6 text-center italic">Belum ada butir soal pada kuis ini.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($soalList as $no => $s): ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-2">
                                        <span class="w-6 h-6 rounded bg-[#0066CC] text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                                            <?= $no + 1; ?>
                                        </span>
                                        <span class="font-bold text-slate-800 leading-relaxed"><?= e($s['pertanyaan']); ?></span>
                                    </div>
                                    <form action="" method="POST" onsubmit="return confirm('Hapus butir soal ini?');">
                                        <input type="hidden" name="action" value="hapus_soal">
                                        <input type="hidden" name="id_soal" value="<?= $s['id_soal']; ?>">
                                        <input type="hidden" name="id_kuis" value="<?= $kuisAktif['id_kuis']; ?>">
                                        <button type="submit" class="text-slate-400 hover:text-red-500 p-1">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pl-8 text-[11px]">
                                    <div class="p-1.5 rounded <?= ($s['kunci_jawaban'] === 'a') ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'text-slate-600'; ?>">
                                        A. <?= e($s['opsi_a']); ?>
                                    </div>
                                    <div class="p-1.5 rounded <?= ($s['kunci_jawaban'] === 'b') ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'text-slate-600'; ?>">
                                        B. <?= e($s['opsi_b']); ?>
                                    </div>
                                    <div class="p-1.5 rounded <?= ($s['kunci_jawaban'] === 'c') ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'text-slate-600'; ?>">
                                        C. <?= e($s['opsi_c']); ?>
                                    </div>
                                    <div class="p-1.5 rounded <?= ($s['kunci_jawaban'] === 'd') ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'text-slate-600'; ?>">
                                        D. <?= e($s['opsi_d']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- REKAP HASIL PENGERJAAN MAHASISWA -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100">
                    Rekap Nilai Pengerjaan Mahasiswa (<?= count($hasilList); ?>)
                </h3>

                <?php if (empty($hasilList)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center italic">Belum ada mahasiswa yang menyelesaikan kuis ini.</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Nama Mahasiswa</th>
                                    <th class="p-3">Percobaan</th>
                                    <th class="p-3">Waktu Selesai</th>
                                    <th class="p-3 text-right">Skor Kuis</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($hasilList as $hl): ?>
                                    <tr>
                                        <td class="p-3 font-semibold text-slate-800">
                                            <?= e($hl['nama']); ?>
                                        </td>
                                        <td class="p-3 text-slate-600">Ke-<?= (int)$hl['percobaan_ke']; ?></td>
                                        <td class="p-3 text-slate-400"><?= date('d M Y, H:i', strtotime($hl['waktu_selesai'])); ?></td>
                                        <td class="p-3 text-right font-black text-sm <?= ($hl['skor'] >= 80) ? 'text-emerald-600' : (($hl['skor'] >= 60) ? 'text-blue-600' : 'text-red-500'); ?>">
                                            <?= (int)$hl['skor']; ?> / 100
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
