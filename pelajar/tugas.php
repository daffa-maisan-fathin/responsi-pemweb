<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Tugas Kuliah — Questudy';
$userId = $_SESSION['id_user'];

$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;
$idTugasAktif = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$errorSubmit = '';

// Handle Pengumpulan / Edit Jawaban Tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'kumpul_tugas') {
    $tugasId = (int)$_POST['id_tugas'];
    $jawabanTeks = trim($_POST['jawaban_teks'] ?? '');

    // Validasi kepemilikan tugas
    $stmtCekT = $conn->prepare("
        SELECT t.* 
        FROM tugas t 
        JOIN anggota_kelas ak ON t.id_kelas = ak.id_kelas 
        WHERE t.id_tugas = ? AND ak.id_pelajar = ?
    ");
    $stmtCekT->execute([$tugasId, $userId]);
    $tugas = $stmtCekT->fetch();

    if (!$tugas) {
        $errorSubmit = 'Tugas tidak ditemukan atau Anda tidak terdaftar di kelas tersebut!';
    } else {
        // Cek pengumpulan yang sudah ada
        $stmtExisting = $conn->prepare("SELECT * FROM pengumpulan_tugas WHERE id_tugas = ? AND id_pelajar = ?");
        $stmtExisting->execute([$tugasId, $userId]);
        $existing = $stmtExisting->fetch();

        $fileJawaban = $existing ? $existing['file_jawaban'] : null;

        // Cek unggahan file baru
        if (isset($_FILES['file_jawaban']) && $_FILES['file_jawaban']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['file_jawaban']['tmp_name'];
            $origName = $_FILES['file_jawaban']['name'];
            $fileSize = $_FILES['file_jawaban']['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            $allowed = ['pdf', 'zip', 'rar', 'docx', 'png', 'jpg', 'jpeg'];
            if (!in_array($ext, $allowed)) {
                $errorSubmit = 'Format file tidak didukung! Ekstensi yang diizinkan: PDF, ZIP, RAR, DOCX, PNG, JPG.';
            } elseif ($fileSize > 10 * 1024 * 1024) {
                $errorSubmit = 'Ukuran berkas maksimal adalah 10MB!';
            } else {
                $newFileName = 'tugas_' . $tugasId . '_u' . $userId . '_' . time() . '.' . $ext;
                $destDir = BASE_PATH . '/uploads/tugas/';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }
                if (move_uploaded_file($tmpFile, $destDir . $newFileName)) {
                    // Hapus file lama jika ada
                    if ($fileJawaban && file_exists($destDir . $fileJawaban)) {
                        @unlink($destDir . $fileJawaban);
                    }
                    $fileJawaban = $newFileName;
                } else {
                    $errorSubmit = 'Gagal menyimpan file jawaban ke server!';
                }
            }
        }

        if (empty($errorSubmit)) {
            // Tentukan status keterlambatan
            $isLate = (time() > strtotime($tugas['tenggat_waktu']));
            $statusKumpul = $isLate ? 'terlambat' : 'tepat_waktu';

            if ($existing) {
                // Update jawaban yang sudah ada
                $stmtUp = $conn->prepare("
                    UPDATE pengumpulan_tugas 
                    SET jawaban_teks = ?, file_jawaban = ?, waktu_kumpul = NOW(), status = ? 
                    WHERE id_kumpul = ?
                ");
                $stmtUp->execute([$jawabanTeks, $fileJawaban, $statusKumpul, $existing['id_kumpul']]);
                setFlash('success', 'Jawaban tugas Anda berhasil diperbarui!');
            } else {
                // Pengumpulan baru
                $stmtIns = $conn->prepare("
                    INSERT INTO pengumpulan_tugas (id_tugas, id_pelajar, jawaban_teks, file_jawaban, waktu_kumpul, status) 
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $stmtIns->execute([$tugasId, $userId, $jawabanTeks, $fileJawaban, $statusKumpul]);

                // Reward XP jika tepat waktu
                if ($statusKumpul === 'tepat_waktu') {
                    $rw = tambahXP($conn, $userId, 'kumpul_tugas');
                    $msg = 'Tugas berhasil dikumpulkan tepat waktu! +50 XP ditambahkan.';
                    if (!empty($rw['badges_baru'])) {
                        $msg .= ' 🏆 Anda membuka badge baru: ' . implode(', ', $rw['badges_baru']) . '!';
                    }
                    setFlash('success', $msg);
                } else {
                    setFlash('warning', 'Tugas berhasil dikumpulkan (Status: Terlambat). Tidak memperoleh bonus XP tepat waktu.');
                }
            }
            header("Location: " . base_url('pelajar/tugas.php?id=' . $tugasId));
            exit();
        }
    }
}

// Ambil daftar tugas yang relevan
$sqlTugas = "
    SELECT t.*, k.nama_kelas, k.kode_kelas,
           pt.id_kumpul, pt.jawaban_teks, pt.file_jawaban, pt.waktu_kumpul, pt.status AS status_kumpul, pt.nilai, pt.komentar_pengajar
    FROM tugas t
    JOIN kelas k ON t.id_kelas = k.id_kelas
    JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    LEFT JOIN pengumpulan_tugas pt ON t.id_tugas = pt.id_tugas AND pt.id_pelajar = ?
    WHERE ak.id_pelajar = ?
";
$paramsTugas = [$userId, $userId];

if ($idKelas > 0) {
    $sqlTugas .= " AND t.id_kelas = ?";
    $paramsTugas[] = $idKelas;
}

$sqlTugas .= " ORDER BY t.tenggat_waktu ASC";
$stmtT = $conn->prepare($sqlTugas);
$stmtT->execute($paramsTugas);
$semuaTugas = $stmtT->fetchAll();

// Tentukan tugas yang sedang dibuka detailnya
$tugasDetail = null;
if (!empty($semuaTugas)) {
    if ($idTugasAktif > 0) {
        foreach ($semuaTugas as $st) {
            if ($st['id_tugas'] === $idTugasAktif) {
                $tugasDetail = $st;
                break;
            }
        }
    }
    if (!$tugasDetail) {
        $tugasDetail = $semuaTugas[0];
    }
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Tugas Kuliah</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Manajemen Pengumpulan Tugas</h1>
            <p class="text-xs text-slate-500">Kumpulkan tugas tepat waktu untuk mendapatkan +50 XP dan menjaga catatan akademik Anda.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- LIST TUGAS (1 Kolom) -->
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Tugas</span>
                <span class="text-[11px] font-bold text-[#0066CC] bg-blue-50 px-2 py-0.5 rounded">
                    <?= count($semuaTugas); ?> Tugas
                </span>
            </div>

            <?php if (empty($semuaTugas)): ?>
                <p class="text-xs text-slate-400 text-center py-6">Tidak ada tugas pada kelas yang diikuti.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($semuaTugas as $item): ?>
                        <?php 
                            $isSelected = ($tugasDetail && $tugasDetail['id_tugas'] === $item['id_tugas']);
                            $sudahSubmit = !empty($item['status_kumpul']);
                            $isOverdue = (time() > strtotime($item['tenggat_waktu']));
                        ?>
                        <a href="<?= base_url('pelajar/tugas.php?id=' . $item['id_tugas'] . ($idKelas ? '&id_kelas=' . $idKelas : '')); ?>" class="block p-3 rounded-xl border transition <?= $isSelected ? 'border-blue-400 bg-blue-50/60 shadow-sm' : 'border-slate-100 bg-slate-50/60 hover:bg-slate-50'; ?>">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-[#0066CC] truncate max-w-[140px]">
                                    <?= e($item['nama_kelas']); ?>
                                </span>
                                <?php if ($sudahSubmit): ?>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= ($item['status_kumpul'] === 'tepat_waktu') ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= ($item['status_kumpul'] === 'tepat_waktu') ? 'Tepat Waktu' : 'Terlambat'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?= $isOverdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'; ?>">
                                        <?= $isOverdue ? 'Terlewat' : 'Belum Kumpul'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h4 class="text-xs font-bold text-slate-800 line-clamp-1"><?= e($item['judul']); ?></h4>

                            <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                <span>Tenggat: <?= date('d M, H:i', strtotime($item['tenggat_waktu'])); ?></span>
                                <?php if ($item['nilai'] !== null): ?>
                                    <span class="font-bold text-emerald-600">Nilai: <?= (int)$item['nilai']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DETAIL TUGAS & FORM PENGUMPULAN (2 Kolom) -->
    <div class="lg:col-span-2 space-y-6">

        <?php if (!$tugasDetail): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                <i class="fa-solid fa-clipboard-list text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Pilih Tugas</h3>
                <p class="text-xs text-slate-400 mt-1">Pilih tugas pada daftar untuk membaca petunjuk dan mengumpulkan jawaban.</p>
            </div>
        <?php else: ?>
            <?php 
                $isLate = (time() > strtotime($tugasDetail['tenggat_waktu']));
                $sudah = !empty($tugasDetail['status_kumpul']);
            ?>

            <!-- KARTU PETUNJUK TUGAS -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold text-[#0066CC] bg-blue-100 px-2 py-0.5 rounded">
                            <?= e($tugasDetail['nama_kelas']); ?>
                        </span>
                        <h2 class="text-lg font-extrabold text-slate-800 mt-1"><?= e($tugasDetail['judul']); ?></h2>
                    </div>

                    <div class="text-left sm:text-right">
                        <div class="text-[11px] text-slate-400">Batas Waktu:</div>
                        <div class="text-xs font-bold <?= $isLate ? 'text-red-600' : 'text-slate-700'; ?>">
                            <i class="fa-regular fa-clock mr-1"></i>
                            <?= date('d M Y, H:i', strtotime($tugasDetail['tenggat_waktu'])); ?> WIB
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider">Petunjuk Pengerjaan:</h4>
                    <div class="prose max-w-none text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100 whitespace-pre-line">
                        <?= e($tugasDetail['petunjuk']); ?>
                    </div>
                </div>
            </div>

            <!-- STATUS PENILAIAN JIKA SUDAH DINILAI -->
            <?php if ($sudah && $tugasDetail['nilai'] !== null): ?>
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-award text-emerald-600 text-lg"></i>
                            <h3 class="text-sm font-bold text-emerald-900">Hasil Penilaian Pengajar</h3>
                        </div>
                        <span class="text-lg font-extrabold text-emerald-700 bg-white px-3 py-1 rounded-xl border border-emerald-300">
                            <?= (int)$tugasDetail['nilai']; ?> / 100
                        </span>
                    </div>
                    <?php if (!empty($tugasDetail['komentar_pengajar'])): ?>
                        <div class="text-xs text-emerald-800 bg-white/70 p-3 rounded-lg border border-emerald-200/60 mt-2">
                            <strong>Catatan Dosen/Tutor:</strong> <?= e($tugasDetail['komentar_pengajar']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- FORM PENGUMPULAN / EDIT JAWABAN -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-800">
                        <?= $sudah ? 'Perbarui Jawaban Tugas' : 'Form Pengumpulan Tugas'; ?>
                    </h3>
                    <?php if ($sudah): ?>
                        <span class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> Sudah Dikumpulkan
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errorSubmit)): ?>
                    <div class="p-3 rounded-lg bg-red-50 text-red-700 text-xs flex items-center gap-2 border border-red-200">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($errorSubmit); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($isLate && !$sudah): ?>
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                        <span>Perhatian: Tenggat waktu telah berakhir. Tugas tetap dapat dikumpulkan namun akan ditandai sebagai <strong>Terlambat</strong>.</span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="kumpul_tugas">
                    <input type="hidden" name="id_tugas" value="<?= $tugasDetail['id_tugas']; ?>">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi / Jawaban Teks / Tautan Repository</label>
                        <textarea name="jawaban_teks" rows="4" placeholder="Tuliskan uraian jawaban singkat, link github, atau catatan pengumpulan..." class="w-full p-3 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"><?= e($tugasDetail['jawaban_teks'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unggah Berkas Tugas (PDF, ZIP, DOCX)</label>
                        <input type="file" name="file_jawaban" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0066CC] hover:file:bg-blue-100 cursor-pointer">
                        <?php if (!empty($tugasDetail['file_jawaban'])): ?>
                            <div class="mt-2 text-xs text-slate-500 flex items-center gap-2">
                                <span>Berkas saat ini:</span>
                                <a href="<?= base_url('uploads/tugas/' . e($tugasDetail['file_jawaban'])); ?>" download class="font-bold text-[#0066CC] hover:underline flex items-center gap-1">
                                    <i class="fa-solid fa-download"></i> <?= e($tugasDetail['file_jawaban']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-2 text-right">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-md shadow-blue-500/20">
                            <?= $sudah ? 'Simpan Perubahan Jawaban' : 'Kirim Jawaban Tugas'; ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
