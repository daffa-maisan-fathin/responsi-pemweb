<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Pemantauan & Peringatan Dini — Questudy';
$userId = $_SESSION['id_user'];

// Ambil aturan ambang batas dari admin
$stmtRule = $conn->query("SELECT * FROM peringatan_dini_aturan LIMIT 1");
$rule = $stmtRule->fetch() ?: ['max_telat_tugas' => 2, 'min_rata_nilai' => 65, 'max_hari_tidak_login' => 5];

$filterKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

// Handle Simpan Catatan Tindak Lanjut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_tindak_lanjut') {
    $targetPelajar = (int)$_POST['id_pelajar'];
    $targetKelas = (int)$_POST['id_kelas'];
    $catatanTL = trim($_POST['catatan_tindak_lanjut'] ?? '');

    if (!empty($catatanTL)) {
        $stmtTL = $conn->prepare("
            INSERT INTO tindak_lanjut (id_kelas, id_pengajar, id_pelajar, catatan_tindak_lanjut, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtTL->execute([$targetKelas, $userId, $targetPelajar, $catatanTL]);
        setFlash('success', 'Catatan tindak lanjut untuk mahasiswa berhasil disimpan!');
    }
    header("Location: " . base_url('pengajar/pemantauan.php' . ($filterKelas ? '?id_kelas=' . $filterKelas : '')));
    exit();
}

// Ambil daftar kelas yang diampu pengajar ini untuk opsi filter
$stmtMyClasses = $conn->prepare("SELECT id_kelas, nama_kelas, kode_kelas FROM kelas WHERE id_pengajar = ? ORDER BY nama_kelas ASC");
$stmtMyClasses->execute([$userId]);
$myClasses = $stmtMyClasses->fetchAll();

// Query pemindaian mahasiswa berisiko (Early Warning Scan)
$sqlScan = "
    SELECT u.id_user, u.nama, u.email, u.foto_profil, u.last_login, u.created_at,
           k.id_kelas, k.nama_kelas, k.kode_kelas,
           (
               SELECT COUNT(*) 
               FROM tugas t 
               LEFT JOIN pengumpulan_tugas pt ON t.id_tugas = pt.id_tugas AND pt.id_pelajar = u.id_user
               WHERE t.id_kelas = k.id_kelas 
               AND (pt.status = 'terlambat' OR (pt.id_kumpul IS NULL AND t.tenggat_waktu < NOW()))
           ) AS jml_masalah_tugas,
           (
               SELECT AVG(pt2.nilai)
               FROM pengumpulan_tugas pt2
               JOIN tugas t2 ON pt2.id_tugas = t2.id_tugas
               WHERE pt2.id_pelajar = u.id_user AND t2.id_kelas = k.id_kelas AND pt2.nilai IS NOT NULL
           ) AS rata_nilai_tugas,
           DATEDIFF(NOW(), COALESCE(u.last_login, u.created_at)) AS hari_tidak_login,
           (
               SELECT catatan_tindak_lanjut 
               FROM tindak_lanjut tl 
               WHERE tl.id_pelajar = u.id_user AND tl.id_kelas = k.id_kelas 
               ORDER BY tl.created_at DESC LIMIT 1
           ) AS catatan_terakhir,
           (
               SELECT created_at 
               FROM tindak_lanjut tl 
               WHERE tl.id_pelajar = u.id_user AND tl.id_kelas = k.id_kelas 
               ORDER BY tl.created_at DESC LIMIT 1
           ) AS tgl_catatan_terakhir
    FROM anggota_kelas ak
    JOIN kelas k ON ak.id_kelas = k.id_kelas
    JOIN users u ON ak.id_pelajar = u.id_user
    WHERE k.id_pengajar = ?
";
$paramsScan = [$userId];

if ($filterKelas > 0) {
    $sqlScan .= " AND k.id_kelas = ?";
    $paramsScan[] = $filterKelas;
}

$stmtScan = $conn->prepare($sqlScan);
$stmtScan->execute($paramsScan);
$semuaSiswa = $stmtScan->fetchAll();

// Filter siswa yang masuk kategori "Perlu Perhatian"
$siswaPerluPerhatian = [];
foreach ($semuaSiswa as $mhs) {
    $alasan = [];

    // Indikator 1: Masalah Keterlambatan / Belum Mengumpulkan Tugas
    if ($mhs['jml_masalah_tugas'] >= $rule['max_telat_tugas']) {
        $alasan[] = [
            'tipe' => 'tugas',
            'label' => $mhs['jml_masalah_tugas'] . ' tugas terlambat/belum kumpul',
            'detail' => 'Melampaui batas toleransi keterlambatan (' . $rule['max_telat_tugas'] . ' tugas)'
        ];
    }

    // Indikator 2: Nilai rata-rata rendah
    if ($mhs['rata_nilai_tugas'] !== null && $mhs['rata_nilai_tugas'] < $rule['min_rata_nilai']) {
        $alasan[] = [
            'tipe' => 'nilai',
            'label' => 'Rata-rata nilai: ' . round($mhs['rata_nilai_tugas'], 1),
            'detail' => 'Di bawah ambang batas kelulusan (' . $rule['min_rata_nilai'] . ' poin)'
        ];
    }

    // Indikator 3: Tidak aktif login
    if ($mhs['hari_tidak_login'] >= $rule['max_hari_tidak_login']) {
        $alasan[] = [
            'tipe' => 'inaktif',
            'label' => 'Tidak aktif ' . $mhs['hari_tidak_login'] . ' hari',
            'detail' => 'Melampaui batas toleransi login (' . $rule['max_hari_tidak_login'] . ' hari)'
        ];
    }

    if (!empty($alasan)) {
        $mhs['alasan_peringatan'] = $alasan;
        $siswaPerluPerhatian[] = $mhs;
    }
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 09) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Pemantauan & Peringatan Dini</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 flex items-center gap-2">
                <span>Pemantauan Akademik (Early Warning System)</span>
                <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold border border-red-200">SDGs 4 Inclusive</span>
            </h1>
            <p class="text-xs text-slate-500">Sistem pendeteksi otomatis siswa berisiko tertinggal demi menjamin hak pendidikan merata dan berkualitas.</p>
        </div>
        <!-- Filter Kelas Dropdown -->
        <form action="" method="GET" class="flex items-center gap-2">
            <select name="id_kelas" onchange="this.form.submit()" class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0066CC] shadow-sm">
                <option value="0">Semua Kelas yang Diampu</option>
                <?php foreach ($myClasses as $mc): ?>
                    <option value="<?= $mc['id_kelas']; ?>" <?= ($filterKelas === $mc['id_kelas']) ? 'selected' : ''; ?>>
                        <?= e($mc['nama_kelas']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- KARTU AMBANG BATAS SISTEM (RULE THRESHOLD INFO) -->
<div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ambang Batas Peringatan Dini (Ditetapkan Admin):</h3>
            <p class="text-xs text-slate-500">Mahasiswa otomatis ditandai "Perlu Perhatian" bila memenuhi salah satu kriteria berikut:</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
            <span class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 border border-red-200">
                <i class="fa-solid fa-list-check mr-1 text-red-500"></i> Max Telat Tugas: <?= (int)$rule['max_telat_tugas']; ?>x
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">
                <i class="fa-solid fa-chart-simple mr-1 text-amber-500"></i> Min Rata-rata Nilai: <?= (int)$rule['min_rata_nilai']; ?>
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-orange-50 text-orange-800 border border-orange-200">
                <i class="fa-solid fa-user-clock mr-1 text-orange-500"></i> Max Tidak Login: <?= (int)$rule['max_hari_tidak_login']; ?> Hari
            </span>
        </div>
    </div>
</div>

<!-- TABEL DAFTAR PELAJAR "PERLU PERHATIAN" -->
<div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden space-y-4">
    <div class="p-6 bg-red-50/50 border-b border-red-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
            <h2 class="text-base font-bold text-red-950">Daftar Mahasiswa Perlu Perhatian</h2>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-black bg-red-100 text-red-700 border border-red-200">
            <?= count($siswaPerluPerhatian); ?> Mahasiswa Terdeteksi
        </span>
    </div>

    <?php if (empty($siswaPerluPerhatian)): ?>
        <div class="p-12 text-center text-slate-400">
            <i class="fa-solid fa-shield-heart text-4xl text-emerald-500 mb-3 block"></i>
            <h3 class="text-base font-bold text-slate-700">Kondisi Belajar Optimal</h3>
            <p class="text-xs text-slate-400 mt-1">Tidak ada mahasiswa yang melampaui ambang batas peringatan dini di kelas ini.</p>
        </div>
    <?php else: ?>
        <div class="p-6 space-y-4">
            <?php foreach ($siswaPerluPerhatian as $idx => $mhs): ?>
                <div class="p-5 rounded-xl border border-red-200 bg-white hover:bg-red-50/20 transition shadow-sm space-y-4">
                    
                    <!-- Header Baris Siswa -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <img src="<?= base_url('uploads/profil/' . e($mhs['foto_profil'])); ?>" class="w-11 h-11 rounded-xl object-cover border border-slate-200" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($mhs['nama']); ?>&background=EF4444&color=fff';">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <span><?= e($mhs['nama']); ?></span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red-100 text-red-700">
                                        Perlu Perhatian
                                    </span>
                                </h3>
                                <p class="text-xs text-slate-400"><?= e($mhs['email']); ?> &bull; Kelas: <strong class="text-[#0066CC]"><?= e($mhs['nama_kelas']); ?></strong></p>
                            </div>
                        </div>

                        <!-- Badge Indikator Pelanggaran -->
                        <div class="flex flex-wrap items-center gap-2">
                            <?php foreach ($mhs['alasan_peringatan'] as $als): ?>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-800 border border-red-200" title="<?= e($als['detail']); ?>">
                                    <i class="fa-solid fa-circle-exclamation text-red-500 mr-1"></i>
                                    <?= e($als['label']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Riwayat & Form Tindak Lanjut Pengajar -->
                    <div class="pt-3 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        
                        <!-- Catatan Sebelumnya -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Catatan Bimbingan / Tindak Lanjut Terakhir:</span>
                            <?php if (!empty($mhs['catatan_terakhir'])): ?>
                                <p class="text-slate-700 font-medium italic">"<?= e($mhs['catatan_terakhir']); ?>"</p>
                                <span class="text-[10px] text-slate-400 block mt-1">Dicatat pada: <?= date('d M Y, H:i', strtotime($mhs['tgl_catatan_terakhir'])); ?></span>
                            <?php else: ?>
                                <p class="text-slate-400 italic">Belum ada catatan tindak lanjut untuk siswa ini.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Form Input Tindak Lanjut Baru -->
                        <form action="" method="POST" class="flex flex-col justify-between space-y-2">
                            <input type="hidden" name="action" value="simpan_tindak_lanjut">
                            <input type="hidden" name="id_pelajar" value="<?= $mhs['id_user']; ?>">
                            <input type="hidden" name="id_kelas" value="<?= $mhs['id_kelas']; ?>">

                            <textarea name="catatan_tindak_lanjut" rows="2" required placeholder="Tuliskan langkah bimbingan (misal: Dijadwalkan remedial, dikirimi email asistensi, atau panggilan mentoring)..." class="w-full p-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-red-400 transition"></textarea>
                            
                            <div class="text-right">
                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition shadow-sm">
                                    Simpan Catatan Tindak Lanjut
                                </button>
                            </div>
                        </form>

                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
