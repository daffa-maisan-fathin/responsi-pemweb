<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Rapor Akademik Pribadi — Questudy';
$userId = $_SESSION['id_user'];

// Ambil semua kelas yang diikuti beserta seluruh nilai tugas & kuis
$stmtKelas = $conn->prepare("
    SELECT k.*, u.nama AS nama_pengajar
    FROM anggota_kelas ak
    JOIN kelas k ON ak.id_kelas = k.id_kelas
    JOIN users u ON k.id_pengajar = u.id_user
    WHERE ak.id_pelajar = ?
    ORDER BY k.nama_kelas ASC
");
$stmtKelas->execute([$userId]);
$daftarKelas = $stmtKelas->fetchAll();

// Struktur penampung rapor per kelas
$raporData = [];

foreach ($daftarKelas as $kls) {
    $idK = $kls['id_kelas'];

    // Ambil nilai tugas kelas ini
    $stmtTugas = $conn->prepare("
        SELECT t.judul, pt.nilai, pt.status, pt.waktu_kumpul
        FROM tugas t
        LEFT JOIN pengumpulan_tugas pt ON t.id_tugas = pt.id_tugas AND pt.id_pelajar = ?
        WHERE t.id_kelas = ?
    ");
    $stmtTugas->execute([$userId, $idK]);
    $tugasList = $stmtTugas->fetchAll();

    $sumTugas = 0;
    $countTugasDinilai = 0;
    foreach ($tugasList as $t) {
        if ($t['nilai'] !== null) {
            $sumTugas += (int)$t['nilai'];
            $countTugasDinilai++;
        }
    }
    $rataTugas = ($countTugasDinilai > 0) ? round($sumTugas / $countTugasDinilai, 1) : null;

    // Ambil nilai kuis kelas ini (ambil skor tertinggi per kuis)
    $stmtKuis = $conn->prepare("
        SELECT q.judul,
            (SELECT MAX(skor) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS skor_terbaik
        FROM kuis q
        WHERE q.id_kelas = ?
    ");
    $stmtKuis->execute([$userId, $idK]);
    $kuisList = $stmtKuis->fetchAll();

    $sumKuis = 0;
    $countKuisDikerjakan = 0;
    foreach ($kuisList as $k) {
        if ($k['skor_terbaik'] !== null) {
            $sumKuis += (int)$k['skor_terbaik'];
            $countKuisDikerjakan++;
        }
    }
    $rataKuis = ($countKuisDikerjakan > 0) ? round($sumKuis / $countKuisDikerjakan, 1) : null;

    // Rata-rata Akhir (50% Tugas, 50% Kuis jika dua-duanya ada)
    $nilaiAkhir = null;
    if ($rataTugas !== null && $rataKuis !== null) {
        $nilaiAkhir = round(($rataTugas * 0.5) + ($rataKuis * 0.5), 1);
    } elseif ($rataTugas !== null) {
        $nilaiAkhir = $rataTugas;
    } elseif ($rataKuis !== null) {
        $nilaiAkhir = $rataKuis;
    }

    // Huruf Mutu
    $huruf = '-';
    $statusKelulusan = 'Belum Dinilai';
    $colorClass = 'text-slate-400';

    if ($nilaiAkhir !== null) {
        if ($nilaiAkhir >= 85) {
            $huruf = 'A';
            $statusKelulusan = 'Sangat Memuaskan';
            $colorClass = 'text-emerald-600 bg-emerald-50 border-emerald-200';
        } elseif ($nilaiAkhir >= 75) {
            $huruf = 'B';
            $statusKelulusan = 'Memuaskan';
            $colorClass = 'text-blue-600 bg-blue-50 border-blue-200';
        } elseif ($nilaiAkhir >= 65) {
            $huruf = 'C';
            $statusKelulusan = 'Cukup';
            $colorClass = 'text-amber-600 bg-amber-50 border-amber-200';
        } elseif ($nilaiAkhir >= 50) {
            $huruf = 'D';
            $statusKelulusan = 'Kurang';
            $colorClass = 'text-orange-600 bg-orange-50 border-orange-200';
        } else {
            $huruf = 'E';
            $statusKelulusan = 'Perlu Remedial';
            $colorClass = 'text-red-600 bg-red-50 border-red-200';
        }
    }

    $raporData[] = [
        'kelas' => $kls,
        'tugas' => $tugasList,
        'kuis' => $kuisList,
        'rata_tugas' => $rataTugas,
        'rata_kuis' => $rataKuis,
        'nilai_akhir' => $nilaiAkhir,
        'huruf' => $huruf,
        'status' => $statusKelulusan,
        'color_class' => $colorClass
    ];
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Rapor Nilai Pribadi</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Rapor Pencapaian Akademik</h1>
            <p class="text-xs text-slate-500">Transparansi evaluasi belajar, rekap nilai tugas, kuis, serta konversi huruf mutu.</p>
        </div>
        <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-white border border-[#E2E8F0] hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Rapor</span>
        </button>
    </div>
</div>

<?php if (empty($raporData)): ?>
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
        <i class="fa-solid fa-graduation-cap text-4xl text-slate-300 mb-3 block"></i>
        <h3 class="text-base font-bold text-slate-700">Belum Ada Data Akademik</h3>
        <p class="text-xs text-slate-400 mt-1">Anda belum bergabung ke kelas manapun.</p>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($raporData as $rp): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
                <!-- Header Kelas & Nilai Huruf Mutu -->
                <div class="p-6 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                                <?= e($rp['kelas']['kode_kelas']); ?>
                            </span>
                            <span class="text-xs text-slate-500">Tutor: <strong><?= e($rp['kelas']['nama_pengajar']); ?></strong></span>
                        </div>
                        <h2 class="text-lg font-extrabold text-slate-800"><?= e($rp['kelas']['nama_kelas']); ?></h2>
                    </div>

                    <!-- Badge Huruf Mutu & Nilai Akhir -->
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Rata-rata Akhir</span>
                            <span class="text-xl font-extrabold text-slate-800">
                                <?= ($rp['nilai_akhir'] !== null) ? $rp['nilai_akhir'] : '-'; ?>
                            </span>
                        </div>
                        <div class="min-w-[4.5rem] max-w-[8.5rem] px-3 py-2 rounded-2xl border flex flex-col items-center justify-center text-center <?= $rp['color_class']; ?> shadow-sm">
                            <span class="text-2xl font-black leading-none"><?= $rp['huruf']; ?></span>
                            <span class="text-xs font-bold mt-1 leading-tight"><?= $rp['status']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Detail Nilai Tugas & Kuis -->
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Kolom Tugas -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-list-check text-[#0066CC]"></i>
                                <span>Nilai Tugas</span>
                            </span>
                            <span class="text-xs font-bold text-slate-600">
                                Rata-rata: <strong class="text-[#0066CC]"><?= ($rp['rata_tugas'] !== null) ? $rp['rata_tugas'] : '-'; ?></strong>
                            </span>
                        </div>

                        <?php if (empty($rp['tugas'])): ?>
                            <p class="text-xs text-slate-400 italic">Belum ada tugas di kelas ini.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($rp['tugas'] as $t): ?>
                                    <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-slate-50">
                                        <div class="truncate max-w-[200px]">
                                            <span class="font-medium text-slate-800 block truncate"><?= e($t['judul']); ?></span>
                                            <span class="text-[10px] text-slate-400"><?= $t['status'] ? ucfirst(str_replace('_', ' ', $t['status'])) : 'Belum mengumpulkan'; ?></span>
                                        </div>
                                        <div>
                                            <?php if ($t['nilai'] !== null): ?>
                                                <span class="font-extrabold text-slate-800 bg-white px-2.5 py-1 rounded border border-slate-200"><?= (int)$t['nilai']; ?></span>
                                            <?php else: ?>
                                                <span class="text-[11px] text-slate-400 italic">Belum dinilai</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Kolom Kuis -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-question text-amber-500"></i>
                                <span>Nilai Kuis Online</span>
                            </span>
                            <span class="text-xs font-bold text-slate-600">
                                Rata-rata: <strong class="text-amber-600"><?= ($rp['rata_kuis'] !== null) ? $rp['rata_kuis'] : '-'; ?></strong>
                            </span>
                        </div>

                        <?php if (empty($rp['kuis'])): ?>
                            <p class="text-xs text-slate-400 italic">Belum ada kuis di kelas ini.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($rp['kuis'] as $k): ?>
                                    <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-slate-50">
                                        <span class="font-medium text-slate-800 truncate max-w-[200px]"><?= e($k['judul']); ?></span>
                                        <div>
                                            <?php if ($k['skor_terbaik'] !== null): ?>
                                                <span class="font-extrabold text-slate-800 bg-white px-2.5 py-1 rounded border border-slate-200"><?= (int)$k['skor_terbaik']; ?></span>
                                            <?php else: ?>
                                                <span class="text-[11px] text-slate-400 italic">Belum dikerjakan</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
