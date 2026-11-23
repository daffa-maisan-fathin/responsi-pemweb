<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Rapor Kelas Otomatis — Questudy';
$userId = $_SESSION['id_user'];

$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

// Ambil semua kelas pengajar
$stmtK = $conn->prepare("SELECT id_kelas, nama_kelas, kode_kelas FROM kelas WHERE id_pengajar = ? ORDER BY nama_kelas ASC");
$stmtK->execute([$userId]);
$myClasses = $stmtK->fetchAll();

if ($idKelas === 0 && !empty($myClasses)) {
    $idKelas = (int)$myClasses[0]['id_kelas'];
}

// Ambil info kelas terpilih
$kelasInfo = null;
if ($idKelas > 0) {
    $stmtKi = $conn->prepare("SELECT * FROM kelas WHERE id_kelas = ? AND id_pengajar = ?");
    $stmtKi->execute([$idKelas, $userId]);
    $kelasInfo = $stmtKi->fetch();
}

// Rekap Rapor Seluruh Pelajar di Kelas Ini
$rekapSiswa = [];

if ($kelasInfo) {
    $stmtMhs = $conn->prepare("
        SELECT u.id_user, u.nama, u.email, u.foto_profil, ak.bergabung_pada
        FROM anggota_kelas ak
        JOIN users u ON ak.id_pelajar = u.id_user
        WHERE ak.id_kelas = ?
        ORDER BY u.nama ASC
    ");
    $stmtMhs->execute([$idKelas]);
    $mhsList = $stmtMhs->fetchAll();

    foreach ($mhsList as $m) {
        $idPelajar = $m['id_user'];

        // Rata-rata tugas
        $stmtTugas = $conn->prepare("
            SELECT AVG(pt.nilai) AS rata_tugas, COUNT(pt.nilai) AS tugas_dinilai
            FROM pengumpulan_tugas pt
            JOIN tugas t ON pt.id_tugas = t.id_tugas
            WHERE pt.id_pelajar = ? AND t.id_kelas = ? AND pt.nilai IS NOT NULL
        ");
        $stmtTugas->execute([$idPelajar, $idKelas]);
        $resTugas = $stmtTugas->fetch();
        $rataTugas = ($resTugas && $resTugas['tugas_dinilai'] > 0) ? round($resTugas['rata_tugas'], 1) : null;

        // Rata-rata kuis (skor tertinggi tiap kuis)
        $stmtKuis = $conn->prepare("
            SELECT AVG(skor_max) AS rata_kuis, COUNT(skor_max) AS kuis_dikerjakan
            FROM (
                SELECT MAX(hk.skor) AS skor_max
                FROM hasil_kuis hk
                JOIN kuis q ON hk.id_kuis = q.id_kuis
                WHERE hk.id_pelajar = ? AND q.id_kelas = ?
                GROUP BY hk.id_kuis
            ) AS sub
        ");
        $stmtKuis->execute([$idPelajar, $idKelas]);
        $resKuis = $stmtKuis->fetch();
        $rataKuis = ($resKuis && $resKuis['kuis_dikerjakan'] > 0) ? round($resKuis['rata_kuis'], 1) : null;

        // Nilai Akhir Kumulatif
        $nilaiAkhir = null;
        if ($rataTugas !== null && $rataKuis !== null) {
            $nilaiAkhir = round(($rataTugas * 0.5) + ($rataKuis * 0.5), 1);
        } elseif ($rataTugas !== null) {
            $nilaiAkhir = $rataTugas;
        } elseif ($rataKuis !== null) {
            $nilaiAkhir = $rataKuis;
        }

        // Konversi Huruf Mutu
        $huruf = '-';
        $status = 'Belum Dinilai';
        $colorBadge = 'bg-slate-100 text-slate-600';

        if ($nilaiAkhir !== null) {
            if ($nilaiAkhir >= 85) {
                $huruf = 'A';
                $status = 'Sangat Memuaskan';
                $colorBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            } elseif ($nilaiAkhir >= 75) {
                $huruf = 'B';
                $status = 'Memuaskan';
                $colorBadge = 'bg-blue-100 text-blue-800 border-blue-300';
            } elseif ($nilaiAkhir >= 65) {
                $huruf = 'C';
                $status = 'Cukup';
                $colorBadge = 'bg-amber-100 text-amber-800 border-amber-300';
            } elseif ($nilaiAkhir >= 50) {
                $huruf = 'D';
                $status = 'Kurang';
                $colorBadge = 'bg-orange-100 text-orange-800 border-orange-300';
            } else {
                $huruf = 'E';
                $status = 'Remedial';
                $colorBadge = 'bg-red-100 text-red-800 border-red-300';
            }
        }

        $rekapSiswa[] = [
            'user' => $m,
            'rata_tugas' => $rataTugas,
            'rata_kuis' => $rataKuis,
            'nilai_akhir' => $nilaiAkhir,
            'huruf' => $huruf,
            'status' => $status,
            'badge' => $colorBadge
        ];
    }
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Rapor Kelas Otomatis</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Rekapitulasi Nilai & Rapor Kelas</h1>
            <p class="text-xs text-slate-500">Perhitungan otomatis rata-rata tugas (50%), kuis (50%), nilai akhir, dan konversi huruf mutu.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-white border border-[#E2E8F0] hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Rapor Kelas</span>
            </button>
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

<?php if (!$kelasInfo): ?>
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
        <i class="fa-solid fa-chalkboard text-4xl text-slate-300 mb-3 block"></i>
        <h3 class="text-base font-bold text-slate-700">Pilih Kelas</h3>
        <p class="text-xs text-slate-400 mt-1">Silakan pilih kelas Anda untuk melihat tabel rekapitulasi rapor.</p>
    </div>
<?php else: ?>

    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden space-y-4">
        
        <!-- Header Rapor Kelas -->
        <div class="p-6 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                    Kode Kelas: <?= e($kelasInfo['kode_kelas']); ?>
                </span>
                <h2 class="text-lg font-extrabold text-slate-800 mt-1"><?= e($kelasInfo['nama_kelas']); ?></h2>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-white px-3 py-1.5 rounded-lg border">
                Total Pelajar: <strong class="text-[#0066CC]"><?= count($rekapSiswa); ?></strong>
            </span>
        </div>

        <?php if (empty($rekapSiswa)): ?>
            <p class="text-xs text-slate-400 p-8 text-center italic">Belum ada pelajar yang bergabung di kelas ini.</p>
        <?php else: ?>
            <div class="overflow-x-auto p-6 pt-0">
                <table class="w-full text-left text-xs border border-slate-200 rounded-xl overflow-hidden">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3.5">No</th>
                            <th class="p-3.5">Nama Pelajar</th>
                            <th class="p-3.5 text-right">Rata-rata Tugas</th>
                            <th class="p-3.5 text-right">Rata-rata Kuis</th>
                            <th class="p-3.5 text-right">Nilai Akhir</th>
                            <th class="p-3.5 text-center">Huruf Mutu</th>
                            <th class="p-3.5 text-center">Predikat Kelulusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rekapSiswa as $idx => $row): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-3.5 font-bold text-slate-400"><?= $idx + 1; ?></td>
                                <td class="p-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <img src="<?= base_url('uploads/profil/' . e($row['user']['foto_profil'])); ?>" class="w-7 h-7 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['user']['nama']); ?>&background=0066CC&color=fff';">
                                        <div>
                                            <span class="font-bold text-slate-800 block"><?= e($row['user']['nama']); ?></span>
                                            <span class="text-[10px] text-slate-400"><?= e($row['user']['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5 text-right font-semibold text-slate-700">
                                    <?= ($row['rata_tugas'] !== null) ? $row['rata_tugas'] : '-'; ?>
                                </td>
                                <td class="p-3.5 text-right font-semibold text-slate-700">
                                    <?= ($row['rata_kuis'] !== null) ? $row['rata_kuis'] : '-'; ?>
                                </td>
                                <td class="p-3.5 text-right font-extrabold text-sm text-[#0066CC]">
                                    <?= ($row['nilai_akhir'] !== null) ? $row['nilai_akhir'] : '-'; ?>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="w-8 h-8 rounded-lg inline-flex items-center justify-center font-black text-sm border <?= $row['badge']; ?>">
                                        <?= $row['huruf']; ?>
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $row['badge']; ?>">
                                        <?= $row['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

<?php endif; ?>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
