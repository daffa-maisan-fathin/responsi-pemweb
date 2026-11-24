<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$userId = $_SESSION['id_user'];
$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

if ($idKelas <= 0) {
    setFlash('error', 'Pilih kelas terlebih dahulu untuk melihat sertifikat.');
    header("Location: " . base_url('pelajar/kelas.php'));
    exit();
}

// Ambil data keanggotaan kelas
$stmt = $conn->prepare("
    SELECT k.*, u.nama AS nama_pengajar, u.keahlian AS keahlian_pengajar, ak.bergabung_pada
    FROM anggota_kelas ak
    JOIN kelas k ON ak.id_kelas = k.id_kelas
    JOIN users u ON k.id_pengajar = u.id_user
    WHERE ak.id_pelajar = ? AND ak.id_kelas = ?
    LIMIT 1
");
$stmt->execute([$userId, $idKelas]);
$kelas = $stmt->fetch();

if (!$kelas) {
    setFlash('error', 'Anda belum terdaftar pada kelas ini.');
    header("Location: " . base_url('pelajar/kelas.php'));
    exit();
}

// Hitung nilai akhir untuk predikat sertifikat
$stmtTugas = $conn->prepare("SELECT AVG(nilai) FROM pengumpulan_tugas pt JOIN tugas t ON pt.id_tugas = t.id_tugas WHERE t.id_kelas = ? AND pt.id_pelajar = ? AND pt.nilai IS NOT NULL");
$stmtTugas->execute([$idKelas, $userId]);
$avgTugas = $stmtTugas->fetchColumn();

$stmtKuis = $conn->prepare("SELECT MAX(skor) FROM hasil_kuis hk JOIN kuis q ON hk.id_kuis = q.id_kuis WHERE q.id_kelas = ? AND hk.id_pelajar = ? GROUP BY hk.id_kuis");
$stmtKuis->execute([$idKelas, $userId]);
$scoresKuis = $stmtKuis->fetchAll(PDO::FETCH_COLUMN);
$avgKuis = !empty($scoresKuis) ? (array_sum($scoresKuis) / count($scoresKuis)) : null;

$nilaiAkhir = 85; // default predikat sangat baik
if ($avgTugas !== false && $avgKuis !== null) {
    $nilaiAkhir = round(($avgTugas * 0.5) + ($avgKuis * 0.5), 1);
} elseif ($avgTugas !== false) {
    $nilaiAkhir = round((float)$avgTugas, 1);
} elseif ($avgKuis !== null) {
    $nilaiAkhir = round((float)$avgKuis, 1);
}

$predikat = 'Dengan Predikat: Sangat Memuaskan (Grade A)';
if ($nilaiAkhir < 75) {
    $predikat = 'Dengan Predikat: Memuaskan (Grade B)';
}

$nomorSertifikat = 'QST/' . date('Y') . '/' . str_pad($idKelas, 3, '0', STR_PAD_LEFT) . '/' . str_pad($userId, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Kelulusan — <?= e($kelas['nama_kelas']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Great+Vibes&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }
        .font-serif-title { font-family: 'Cinzel', serif; }
        .font-cursive { font-family: 'Great Vibes', cursive; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .certificate-container { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border: 4px solid #b45309 !important; }
            @page { size: landscape; margin: 0.5cm; }
        }
    </style>
</head>
<body class="p-4 sm:p-8 min-h-screen flex flex-col items-center justify-center text-slate-800">

    <!-- ACTION BAR DI ATAS SERTIFIKAT -->
    <div class="no-print max-w-4xl w-full flex items-center justify-between mb-4 text-white">
        <a href="<?= base_url('pelajar/kelas.php'); ?>" class="inline-flex items-center gap-2 text-sm text-slate-300 hover:text-white transition">
            <i class="fa-solid fa-arrow-left"></i><span>Kembali ke Kelas Saya</span>
        </a>
        <div class="flex items-center gap-3">
            <span class="text-xs text-amber-300 bg-amber-500/20 border border-amber-400/30 px-3 py-1 rounded-full">
                <i class="fa-solid fa-circle-check mr-1"></i>Sertifikat Terverifikasi
            </span>
            <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold text-sm transition shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print"></i><span>Cetak / Unduh PDF</span>
            </button>
        </div>
    </div>

    <!-- FRAME SERTIFIKAT -->
    <div class="certificate-container relative max-w-4xl w-full bg-gradient-to-br from-amber-50/70 via-white to-sky-50/60 rounded-3xl p-8 sm:p-14 shadow-2xl border-8 border-amber-500/80 overflow-hidden">
        
        <!-- Ornamen Garis Ganda Emas -->
        <div class="absolute inset-3 border-2 border-amber-300/80 rounded-2xl pointer-events-none"></div>
        <div class="absolute inset-5 border border-dashed border-amber-400/60 rounded-xl pointer-events-none"></div>

        <!-- Watermark Maskot Paus Questudy -->
        <div class="absolute -right-16 -bottom-16 opacity-[0.04] pointer-events-none text-blue-900 text-[280px]">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>

        <div class="relative z-10 text-center flex flex-col items-center">
            
            <!-- Logo Header -->
            <div class="flex items-center gap-2 mb-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0066CC] to-indigo-600 text-white flex items-center justify-center text-lg shadow-md">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <span class="text-xl font-extrabold tracking-wider text-slate-900 uppercase">QUESTUDY LMS</span>
            </div>

            <!-- Title -->
            <h1 class="font-serif-title text-2xl sm:text-4xl font-extrabold tracking-widest text-amber-700 uppercase mt-2">
                SERTIFIKAT KELULUSAN
            </h1>
            <p class="text-xs uppercase tracking-widest text-slate-500 mt-1 font-semibold">Certificate of Completion & Achievement</p>
            <p class="text-[11px] font-mono text-slate-400 mt-0.5">Nomor: <?= $nomorSertifikat; ?></p>

            <div class="w-24 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent my-5"></div>

            <p class="text-xs text-slate-600 tracking-wide uppercase">Diberikan dengan bangga kepada:</p>

            <!-- Nama Siswa -->
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 mt-2 mb-1 tracking-tight">
                <?= e($_SESSION['nama']); ?>
            </h2>
            <p class="text-xs text-slate-500">ID Peserta Didik: #QST-<?= str_pad($userId, 4, '0', STR_PAD_LEFT); ?></p>

            <p class="max-w-xl text-xs sm:text-sm text-slate-600 mt-5 leading-relaxed">
                Telah berhasil menyelesaikan seluruh rangkaian materi pembelajaran, tugas terstruktur, dan evaluasi kuis dengan pencapaian kompetensi pada program kelas:
            </p>

            <!-- Nama Kelas -->
            <div class="my-3 px-6 py-2.5 rounded-2xl bg-amber-100/60 border border-amber-300/80 inline-block">
                <h3 class="text-base sm:text-xl font-extrabold text-[#0066CC]">
                    <?= e($kelas['nama_kelas']); ?>
                </h3>
                <span class="text-xs font-mono font-bold text-slate-600">Kode: <?= e($kelas['kode_kelas']); ?> · Tingkat: <?= ucfirst(e($kelas['tingkat'])); ?></span>
            </div>

            <p class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                <?= $predikat; ?> (Nilai Akhir: <?= $nilaiAkhir; ?>)
            </p>

            <!-- Footer Tanda Tangan & Tanggal -->
            <div class="w-full grid grid-cols-2 mt-8 pt-6 border-t border-slate-200/80 text-xs">
                <div class="text-center">
                    <p class="text-slate-500">Tutor Pembimbing / Instruktur</p>
                    <div class="h-12 flex items-center justify-center font-cursive text-2xl text-indigo-800">
                        <?= e($kelas['nama_pengajar']); ?>
                    </div>
                    <p class="font-bold text-slate-800 underline"><?= e($kelas['nama_pengajar']); ?></p>
                    <p class="text-[10px] text-slate-400"><?= e($kelas['keahlian_pengajar'] ?: 'Pengajar Terverifikasi'); ?></p>
                </div>
                <div class="text-center">
                    <p class="text-slate-500">Diterbitkan pada <?= date('d F Y'); ?></p>
                    <div class="h-12 flex items-center justify-center">
                        <div class="w-10 h-10 rounded-full border-2 border-amber-500 bg-amber-50 flex items-center justify-center text-amber-600 text-sm shadow-sm">
                            <i class="fa-solid fa-award"></i>
                        </div>
                    </div>
                    <p class="font-bold text-slate-800 underline">Direktur Akademik Questudy</p>
                    <p class="text-[10px] text-slate-400">Verifikasi Resmi Sistem</p>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
