<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Kuis Online — Questudy';
$userId = $_SESSION['id_user'];

$idKuis = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $_GET['mode'] ?? 'list'; // 'list' atau 'kerjakan'

// Handle Submit Jawaban Kuis (Auto-grading)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_kuis') {
    $targetKuisId = (int)$_POST['id_kuis'];
    $jawabanUser = $_POST['jawaban'] ?? []; // format: [id_soal => 'a'|'b'|'c'|'d']

    // Validasi kuota percobaan
    $stmtK = $conn->prepare("
        SELECT q.*, 
            (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS jml_percobaan
        FROM kuis q
        JOIN anggota_kelas ak ON q.id_kelas = ak.id_kelas
        WHERE q.id_kuis = ? AND ak.id_pelajar = ?
    ");
    $stmtK->execute([$userId, $targetKuisId, $userId]);
    $kuis = $stmtK->fetch();

    if ($kuis && $kuis['jml_percobaan'] < $kuis['maks_percobaan']) {
        // Ambil semua soal dan kunci jawaban
        $stmtSoal = $conn->prepare("SELECT id_soal, kunci_jawaban FROM soal_kuis WHERE id_kuis = ?");
        $stmtSoal->execute([$targetKuisId]);
        $soalList = $stmtSoal->fetchAll();

        $totalSoal = count($soalList);
        $benar = 0;

        foreach ($soalList as $s) {
            $idS = $s['id_soal'];
            if (isset($jawabanUser[$idS]) && strtolower($jawabanUser[$idS]) === strtolower($s['kunci_jawaban'])) {
                $benar++;
            }
        }

        $skor = ($totalSoal > 0) ? round(($benar / $totalSoal) * 100) : 0;
        $percobaanKe = (int)$kuis['jml_percobaan'] + 1;

        // Simpan hasil kuis
        $stmtSave = $conn->prepare("
            INSERT INTO hasil_kuis (id_kuis, id_pelajar, percobaan_ke, skor, waktu_selesai)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtSave->execute([$targetKuisId, $userId, $percobaanKe, $skor]);

        $oldLevel = $_SESSION['level'] ?? 1;
        $rw = null;
        if ($skor === 100) {
            $rw = tambahXP($conn, $userId, 'kuis_sempurna');
            $msg = "Kuis selesai! Skor Sempurna: 100/100! Bonus +40 XP!";
        } else {
            $rw = tambahXP($conn, $userId, 'selesai_materi');
            $msg = "Kuis selesai! Skor Anda: $skor/100 (Percobaan ke-$percobaanKe). +15 XP!";
        }

        if (!empty($rw['badges_baru'])) {
            $msg .= " Badge baru: " . implode(', ', $rw['badges_baru']);
        }

        $_SESSION['celebration'] = [
            'title' => ($skor >= 80) ? 'Luar Biasa, Kuis Tuntas!' : 'Kuis Berhasil Diselesaikan!',
            'message' => 'Kamu menyelesaikan kuis dengan ' . $benar . ' dari ' . $totalSoal . ' soal benar.',
            'skor' => $skor,
            'xp' => $rw['xp_didapat'] ?? (($skor === 100) ? 40 : 15),
            'level_baru' => (!empty($rw['level']) && $rw['level'] > $oldLevel) ? $rw['level'] : null,
            'badges' => $rw['badges_baru'] ?? []
        ];

        setFlash('success', $msg);
        header("Location: " . base_url('pelajar/kuis.php?id=' . $targetKuisId));
        exit();
    } else {
        setFlash('error', 'Percobaan kuis telah mencapai batas maksimal atau kuis tidak valid.');
        header("Location: " . base_url('pelajar/kuis.php'));
        exit();
    }
}

// Jika ada ID kuis yang dibuka
$kuisDetail = null;
$riwayatPercobaan = [];
$soalDaftar = [];

if ($idKuis > 0) {
    $stmtDetail = $conn->prepare("
        SELECT q.*, k.nama_kelas,
            (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS jml_percobaan
        FROM kuis q
        JOIN kelas k ON q.id_kelas = k.id_kelas
        JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
        WHERE q.id_kuis = ? AND ak.id_pelajar = ?
    ");
    $stmtDetail->execute([$userId, $idKuis, $userId]);
    $kuisDetail = $stmtDetail->fetch();

    if ($kuisDetail) {
        // Ambil riwayat hasil kuis
        $stmtHasil = $conn->prepare("SELECT * FROM hasil_kuis WHERE id_kuis = ? AND id_pelajar = ? ORDER BY percobaan_ke ASC");
        $stmtHasil->execute([$idKuis, $userId]);
        $riwayatPercobaan = $stmtHasil->fetchAll();

        // Jika mode pengerjaan kuis
        if ($mode === 'kerjakan' && $kuisDetail['jml_percobaan'] < $kuisDetail['maks_percobaan']) {
            $stmtS = $conn->prepare("SELECT * FROM soal_kuis WHERE id_kuis = ? ORDER BY id_soal ASC");
            $stmtS->execute([$idKuis]);
            $soalDaftar = $stmtS->fetchAll();
        }
    }
}

// Ambil semua kuis di kelas-kelas yang diikuti
$stmtSemuaKuis = $conn->prepare("
    SELECT q.*, k.nama_kelas,
        (SELECT MAX(skor) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS skor_terbaik,
        (SELECT COUNT(*) FROM hasil_kuis hk WHERE hk.id_kuis = q.id_kuis AND hk.id_pelajar = ?) AS total_percobaan
    FROM kuis q
    JOIN kelas k ON q.id_kelas = k.id_kelas
    JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    WHERE ak.id_pelajar = ?
    ORDER BY q.tenggat_waktu ASC
");
$stmtSemuaKuis->execute([$userId, $userId, $userId]);
$daftarKuisSemua = $stmtSemuaKuis->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Kuis Online</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Evaluasi Pemahaman & Kuis</h1>
            <p class="text-xs text-slate-500">Jawab pertanyaan pilihan ganda sesuai timer. Raih skor 100 untuk bonus +40 XP.</p>
        </div>
        <div>
            <a href="<?= base_url('pelajar/rapor.php'); ?>" class="px-4 py-2 rounded-xl bg-white border border-[#E2E8F0] hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-award text-amber-500"></i>
                <span>Lihat Rapor Akademik</span>
            </a>
        </div>
    </div>
</div>

<?php if ($mode === 'kerjakan' && $kuisDetail && !empty($soalDaftar)): ?>
    <!-- ================= MODE PENGERJAAN KUIS DENGAN TIMER ================= -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-6 sm:p-8 space-y-6">
        <!-- Floating Timer Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-900 text-white sticky top-20 z-30 shadow-lg">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-300"><?= e($kuisDetail['nama_kelas']); ?></span>
                <h2 class="text-base font-bold"><?= e($kuisDetail['judul']); ?></h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Sisa Waktu</div>
                    <div id="quizTimer" class="text-xl font-mono font-black text-amber-400 tracking-wider">
                        --:--
                    </div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-stopwatch animate-pulse"></i>
                </div>
            </div>
        </div>

        <form id="quizForm" action="" method="POST" class="space-y-8">
            <input type="hidden" name="action" value="submit_kuis">
            <input type="hidden" name="id_kuis" value="<?= $kuisDetail['id_kuis']; ?>">

            <div class="space-y-6">
                <?php foreach ($soalDaftar as $no => $soal): ?>
                    <div class="p-5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-[#0066CC] text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                                <?= $no + 1; ?>
                            </span>
                            <div class="text-sm font-semibold text-slate-800 leading-relaxed pt-0.5">
                                <?= e($soal['pertanyaan']); ?>
                            </div>
                        </div>

                        <!-- Opsi Jawaban (A, B, C, D) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pl-10">
                            <?php foreach (['a', 'b', 'c', 'd'] as $opsiKey): ?>
                                <?php $teksOpsi = $soal['opsi_' . $opsiKey]; ?>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-400 cursor-pointer bg-white transition has-[:checked]:border-[#0066CC] has-[:checked]:bg-blue-50/60 shadow-sm">
                                    <input type="radio" name="jawaban[<?= $soal['id_soal']; ?>]" value="<?= $opsiKey; ?>" class="text-[#0066CC] focus:ring-[#0066CC]" required>
                                    <div class="text-xs text-slate-700">
                                        <span class="font-bold uppercase mr-1"><?= $opsiKey; ?>.</span> <?= e($teksOpsi); ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">Pastikan seluruh soal telah terjawab sebelum menekan tombol submit.</span>
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan dan mengirim jawaban kuis ini?');" class="px-6 py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-md shadow-blue-500/20">
                    Kirim Jawaban Kuis
                </button>
            </div>
        </form>
    </div>

    <!-- Countdown Timer Script dengan Auto-Submit -->
    <script>
        const durasiMenit = <?= (int)$kuisDetail['durasi_menit']; ?>;
        let sisaDetik = durasiMenit * 60;
        const timerElem = document.getElementById('quizTimer');
        const formElem = document.getElementById('quizForm');

        function updateTimer() {
            const menit = Math.floor(sisaDetik / 60);
            const detik = sisaDetik % 60;

            timerElem.textContent = 
                (menit < 10 ? '0' : '') + menit + ':' + 
                (detik < 10 ? '0' : '') + detik;

            if (sisaDetik <= 60) {
                timerElem.classList.remove('text-amber-400');
                timerElem.classList.add('text-red-500', 'animate-pulse');
            }

            if (sisaDetik <= 0) {
                clearInterval(timerInterval);
                alert('Waktu pengerjaan kuis telah habis! Jawaban Anda akan dikirimkan secara otomatis.');
                formElem.submit();
            }
            sisaDetik--;
        }

        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
    </script>

<?php else: ?>
    <!-- ================= DAFTAR KUIS & DETAIL PERCOBAAN ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- DAFTAR SEMUA KUIS (1 Kolom) -->
        <div class="lg:col-span-1 space-y-3">
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">
                    Daftar Kuis Tersedia
                </h3>

                <?php if (empty($daftarKuisSemua)): ?>
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada kuis pada kelas Anda.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($daftarKuisSemua as $item): ?>
                            <?php 
                                $isSel = ($kuisDetail && $kuisDetail['id_kuis'] === $item['id_kuis']);
                                $maxReached = ($item['total_percobaan'] >= $item['maks_percobaan']);
                            ?>
                            <a href="<?= base_url('pelajar/kuis.php?id=' . $item['id_kuis']); ?>" class="block p-3 rounded-xl border transition <?= $isSel ? 'border-blue-400 bg-blue-50/60 shadow-sm' : 'border-slate-100 bg-slate-50/60 hover:bg-slate-50'; ?>">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-[#0066CC] truncate max-w-[130px]">
                                        <?= e($item['nama_kelas']); ?>
                                    </span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= $maxReached ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                                        <?= (int)$item['total_percobaan']; ?>/<?= (int)$item['maks_percobaan']; ?> Percobaan
                                    </span>
                                </div>
                                <h4 class="text-xs font-bold text-slate-800 line-clamp-1"><?= e($item['judul']); ?></h4>
                                <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                    <span><i class="fa-solid fa-stopwatch mr-1"></i><?= (int)$item['durasi_menit']; ?> Menit</span>
                                    <?php if ($item['skor_terbaik'] !== null): ?>
                                        <span class="font-extrabold text-[#0066CC]">Skor: <?= (int)$item['skor_terbaik']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DETAIL KUIS TERPILIH & RIWAYAT PERCOBAAN (2 Kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <?php if (!$kuisDetail): ?>
                <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                    <i class="fa-solid fa-circle-question text-4xl text-slate-300 mb-3 block"></i>
                    <h3 class="text-base font-bold text-slate-700">Pilih Kuis Online</h3>
                    <p class="text-xs text-slate-400 mt-1">Pilih salah satu kuis dari daftar di sisi kiri untuk melihat aturan pengerjaan dan riwayat nilai.</p>
                </div>
            <?php else: ?>
                <?php 
                    $bisaMengerjakan = ($kuisDetail['jml_percobaan'] < $kuisDetail['maks_percobaan']);
                ?>

                <!-- Kartu Info Kuis -->
                <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div>
                            <span class="text-[10px] font-bold text-[#0066CC] bg-blue-100 px-2 py-0.5 rounded">
                                <?= e($kuisDetail['nama_kelas']); ?>
                            </span>
                            <h2 class="text-lg font-extrabold text-slate-800 mt-1"><?= e($kuisDetail['judul']); ?></h2>
                        </div>
                        <div>
                            <?php if ($bisaMengerjakan): ?>
                                <a href="<?= base_url('pelajar/kuis.php?id=' . $kuisDetail['id_kuis'] . '&mode=kerjakan'); ?>" onclick="return confirm('Mulai kuis sekarang? Timer waktu akan segera berjalan begitu halaman kuis dibuka!');" class="px-5 py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-md shadow-blue-500/20 inline-flex items-center gap-2">
                                    <i class="fa-solid fa-play"></i>
                                    <span><?= ($kuisDetail['jml_percobaan'] > 0) ? 'Ulangi Kuis' : 'Mulai Kerjakan Kuis'; ?></span>
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 rounded-xl bg-slate-100 text-slate-500 text-xs font-bold border border-slate-200">
                                    Batas Percobaan Habis
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ketentuan Kuis -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Durasi Waktu</span>
                            <span class="text-sm font-extrabold text-slate-800"><?= (int)$kuisDetail['durasi_menit']; ?> Menit</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Batas Percobaan</span>
                            <span class="text-sm font-extrabold text-slate-800"><?= (int)$kuisDetail['maks_percobaan']; ?> Kali</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Percobaan Terpakai</span>
                            <span class="text-sm font-extrabold text-[#0066CC]"><?= (int)$kuisDetail['jml_percobaan']; ?> Kali</span>
                        </div>
                    </div>

                    <!-- Tabel Riwayat Percobaan -->
                    <div class="pt-2">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Riwayat Percobaan Anda:</h4>
                        <?php if (empty($riwayatPercobaan)): ?>
                            <p class="text-xs text-slate-400 italic">Anda belum pernah mengerjakan kuis ini.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                        <tr>
                                            <th class="p-3">Percobaan Ke-</th>
                                            <th class="p-3">Waktu Selesai</th>
                                            <th class="p-3 text-right">Skor</th>
                                            <th class="p-3 text-center">Status Mutu</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ($riwayatPercobaan as $rp): ?>
                                            <tr>
                                                <td class="p-3 font-semibold text-slate-800">Percobaan <?= (int)$rp['percobaan_ke']; ?></td>
                                                <td class="p-3 text-slate-500"><?= date('d M Y, H:i', strtotime($rp['waktu_selesai'])); ?></td>
                                                <td class="p-3 text-right font-extrabold text-sm <?= ($rp['skor'] >= 80) ? 'text-emerald-600' : (($rp['skor'] >= 60) ? 'text-blue-600' : 'text-red-500'); ?>">
                                                    <?= (int)$rp['skor']; ?> / 100
                                                </td>
                                                <td class="p-3 text-center">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($rp['skor'] >= 80) ? 'bg-emerald-100 text-emerald-800' : (($rp['skor'] >= 60) ? 'bg-blue-100 text-[#0066CC]' : 'bg-red-100 text-red-700'); ?>">
                                                        <?= ($rp['skor'] >= 80) ? 'Sangat Baik' : (($rp['skor'] >= 60) ? 'Cukup' : 'Perlu Belajar'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>

    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
